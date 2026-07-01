<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ReciboRepo
{
    public function search(string $q = '', string $estado = '', int $limit = 60): array
    {
        $limit = max(1, min(200, $limit));
        $q = trim($q);
        $estado = trim($estado);
        $params = [];
        $where = [];

        if ($q !== '') {
            $where[] = '(r.codigo LIKE :like OR r.cliente_nombre LIKE :like OR r.cliente_cuit LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'r.estado = :estado';
            $params[':estado'] = $estado;
        }

        $sql = '
            SELECT r.*, a.nombre AS created_by_nombre,
                   GROUP_CONCAT(CONCAT(rp.factura_id, \'|\', rp.monto_cents) SEPARATOR \';\') AS pagos_info
            FROM recibos r
            LEFT JOIN admin_users a ON a.id = r.created_by
            LEFT JOIN recibo_pagos rp ON rp.recibo_id = r.id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY r.id ORDER BY r.created_at DESC, r.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT r.*, a.nombre AS created_by_nombre
            FROM recibos r
            LEFT JOIN admin_users a ON a.id = r.created_by
            WHERE r.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function pagos(int $reciboId): array
    {
        $st = Db::pdo()->prepare('
            SELECT rp.*, f.codigo AS factura_codigo, f.total_cents AS factura_total,
                   c.banco_emisor AS cheque_banco, c.numero_cheque, c.titular AS cheque_titular, c.fecha_vencimiento AS cheque_vto
            FROM recibo_pagos rp
            LEFT JOIN facturas f ON f.id = rp.factura_id
            LEFT JOIN cheques c ON c.id = rp.cheque_id
            WHERE rp.recibo_id = :r
            ORDER BY rp.id ASC
        ');
        $st->execute([':r' => $reciboId]);
        return $st->fetchAll();
    }

    public function nextCodigo(string $tipo = 'REC-B'): string
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM recibos WHERE YEAR(created_at) = YEAR(CURDATE())");
        $count = (int)$st->fetchColumn();
        return 'REC-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $pagos): int
    {
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare('
                INSERT INTO recibos (codigo, tipo_comprobante, cliente_id, idclien, cliente_nombre, cliente_cuit, cliente_direc, cliente_condicion_iva, fecha, monto_cents, forma_pago, concepto, estado, notas, created_by, created_at, updated_at)
                VALUES (:codigo, :tipo, :cliente_id, :idclien, :cliente_nombre, :cliente_cuit, :cliente_direc, :cliente_condicion_iva, :fecha, :monto, :forma_pago, :concepto, :estado, :notas, :created_by, NOW(), NOW())
            ');
            $st->execute([
                ':codigo' => $data['codigo'],
                ':tipo' => $data['tipo_comprobante'],
                ':cliente_id' => $data['cliente_id'],
                ':idclien' => $data['idclien'],
                ':cliente_nombre' => $data['cliente_nombre'],
                ':cliente_cuit' => $data['cliente_cuit'],
                ':cliente_direc' => $data['cliente_direc'],
                ':cliente_condicion_iva' => $data['cliente_condicion_iva'],
                ':fecha' => $data['fecha'],
                ':monto' => $data['monto_cents'],
                ':forma_pago' => $data['forma_pago'],
                ':concepto' => $data['concepto'],
                ':estado' => $data['estado'] ?? 'emitido',
                ':notas' => $data['notas'],
                ':created_by' => $data['created_by'],
            ]);
            $id = (int)$pdo->lastInsertId();

            $stp = $pdo->prepare('INSERT INTO recibo_pagos (recibo_id, factura_id, forma_pago, cheque_id, monto_cents) VALUES (:rid, :fid, :forma, :chq, :monto)');
            foreach ($pagos as $pg) {
                $stp->execute([
                    ':rid' => $id,
                    ':fid' => $pg['factura_id'],
                    ':forma' => $pg['forma_pago'] ?? null,
                    ':chq' => $pg['cheque_id'] ?? null,
                    ':monto' => $pg['monto_cents'],
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
        $st = Db::pdo()->prepare('UPDATE recibos SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM recibos WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
    }

    public function findClienteWeb(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $st = Db::pdo()->prepare('
            SELECT w.id, w.email, w.name, w.phone, w.address, w.city, w.customer_category, w.wholesale_status,
                   c.idclien, c.razon, c.cuit, c.direc, c.condicion_iva
            FROM web_users w
            LEFT JOIN clientes c ON c.idclien = w.cliente_id
            WHERE w.name LIKE :like OR w.email LIKE :like OR w.phone LIKE :like OR c.razon LIKE :like2 OR c.cuit LIKE :like2
            ORDER BY w.name ASC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%', ':like2' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function findFacturasPendientes(int $clienteId): array
    {
        $st = Db::pdo()->prepare('
            SELECT f.id, f.codigo, f.tipo_comprobante, f.total_cents, f.fecha,
                   COALESCE(SUM(rp.monto_cents), 0) AS pagado_cents
            FROM facturas f
            LEFT JOIN recibo_pagos rp ON rp.factura_id = f.id AND rp.recibo_id IN (SELECT id FROM recibos WHERE estado = \'emitido\')
            WHERE f.cliente_id = :c AND f.estado = \'emitida\'
            GROUP BY f.id
            HAVING (f.total_cents - COALESCE(SUM(rp.monto_cents), 0)) > 0
            ORDER BY f.fecha ASC
        ');
        $st->execute([':c' => $clienteId]);
        return $st->fetchAll();
    }

    public function findClienteErpByWebId(int $webUserId): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT c.*
            FROM clientes c
            INNER JOIN web_users w ON w.cliente_id = c.idclien
            WHERE w.id = :id LIMIT 1
        ');
        $st->execute([':id' => $webUserId]);
        return $st->fetch() ?: null;
    }
}
