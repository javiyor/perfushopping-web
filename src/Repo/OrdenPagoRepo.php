<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class OrdenPagoRepo
{
    public function search(string $q = '', string $estado = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $params = [];
        $where = [];

        if (trim($q) !== '') {
            $where[] = '(o.codigo LIKE :like OR o.proveedor_nombre LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'o.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = '
            SELECT o.*, a.nombre AS created_by_nombre
            FROM ordenes_pago o
            LEFT JOIN admin_users a ON a.id = o.created_by
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY o.created_at DESC, o.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT o.*, a.nombre AS created_by_nombre
            FROM ordenes_pago o
            LEFT JOIN admin_users a ON a.id = o.created_by
            WHERE o.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function pagos(int $ordenPagoId): array
    {
        $st = Db::pdo()->prepare('
            SELECT op.*, c.tipo AS cheque_tipo, c.banco_emisor, c.numero_cheque, c.estado AS cheque_estado, c.titular AS cheque_titular
            FROM orden_pago_pagos op
            LEFT JOIN cheques c ON c.id = op.cheque_id
            WHERE op.orden_pago_id = :o ORDER BY op.id ASC
        ');
        $st->execute([':o' => $ordenPagoId]);
        return $st->fetchAll();
    }

    public function nextCodigo(): string
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM ordenes_pago WHERE YEAR(created_at) = YEAR(CURDATE())");
        $count = (int)$st->fetchColumn();
        return 'OP-' . date('Y') . '-' . str_pad((string)($count + 1), 5, '0', STR_PAD_LEFT);
    }

    public function findProveedores(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $st = Db::pdo()->prepare('
            SELECT idprovee, codprove, razon, cuit FROM proveedo
            WHERE razon LIKE :like OR codprove LIKE :like OR cuit LIKE :like
            ORDER BY razon ASC LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function create(array $data, array $pagos, int $createdBy): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO ordenes_pago (codigo, proveedor_id, proveedor_nombre, fecha, monto_cents, estado, concepto, created_by, created_at, updated_at)
                VALUES (:codigo, :prov_id, :prov_nombre, :fecha, :monto, :estado, :concepto, :cb, NOW(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':prov_id' => $data['proveedor_id'],
                ':prov_nombre' => $data['proveedor_nombre'],
                ':fecha' => $data['fecha'],
                ':monto' => (int)($data['monto_cents'] ?? 0),
                ':estado' => $data['estado'] ?? 'pendiente',
                ':concepto' => $data['concepto'] ?? null,
                ':cb' => $createdBy,
            ]);
            $id = (int)$pdo->lastInsertId();

            $stp = $pdo->prepare('
                INSERT INTO orden_pago_pagos (orden_pago_id, forma_pago, cheque_id, monto_cents)
                VALUES (:oid, :forma, :chq, :monto)
            ');
            foreach ($pagos as $p) {
                $stp->execute([
                    ':oid' => $id,
                    ':forma' => $p['forma_pago'],
                    ':chq' => $p['cheque_id'] ?? null,
                    ':monto' => (int)($p['monto_cents'] ?? 0),
                ]);
            }

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function updateEstado(int $id, string $estado): void
    {
        $st = Db::pdo()->prepare('UPDATE ordenes_pago SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $pdo = Db::pdo();
        $pdo->prepare('DELETE FROM orden_pago_pagos WHERE orden_pago_id = :i')->execute([':i' => $id]);
        $pdo->prepare('DELETE FROM ordenes_pago WHERE id = :i LIMIT 1')->execute([':i' => $id]);
    }
}
