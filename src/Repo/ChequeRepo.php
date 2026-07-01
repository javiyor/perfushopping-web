<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ChequeRepo
{
    public function search(string $tipo = '', string $estado = '', string $q = '', int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $params = [];
        $where = [];

        if ($tipo !== '') {
            $where[] = 'c.tipo = :tipo';
            $params[':tipo'] = $tipo;
        }
        if ($estado !== '') {
            $where[] = 'c.estado = :estado';
            $params[':estado'] = $estado;
        }
        if (trim($q) !== '') {
            $where[] = '(c.titular LIKE :like OR c.banco_emisor LIKE :like2 OR c.numero_cheque LIKE :like3)';
            $params[':like'] = '%' . $q . '%';
            $params[':like2'] = '%' . $q . '%';
            $params[':like3'] = '%' . $q . '%';
        }

        $sql = '
            SELECT c.*, a.nombre AS created_by_nombre, b.banco AS cuenta_banco
            FROM cheques c
            LEFT JOIN admin_users a ON a.id = c.created_by
            LEFT JOIN banco_cuentas b ON b.id = c.banco_cuenta_id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY c.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT c.*, a.nombre AS created_by_nombre, b.banco AS cuenta_banco
            FROM cheques c
            LEFT JOIN admin_users a ON a.id = c.created_by
            LEFT JOIN banco_cuentas b ON b.id = c.banco_cuenta_id
            WHERE c.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function movimientos(int $chequeId): array
    {
        $st = Db::pdo()->prepare('
            SELECT m.*, a.nombre AS created_by_nombre
            FROM cheque_movimientos m
            LEFT JOIN admin_users a ON a.id = m.created_by
            WHERE m.cheque_id = :c ORDER BY m.id ASC
        ');
        $st->execute([':c' => $chequeId]);
        return $st->fetchAll();
    }

    public function create(array $data, int $createdBy): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO cheques (tipo, estado, banco_emisor, numero_cheque, titular, cuit_titular, monto_cents, fecha_emision, fecha_vencimiento, banco_cuenta_id, concepto, created_by, created_at, updated_at)
            VALUES (:tipo, :estado, :banco, :numero, :titular, :cuit, :monto, :fecha_emi, :fecha_ven, :banco_cta, :concepto, :cb, NOW(), NOW())
        ');
        $st->execute([
            ':tipo' => $data['tipo'],
            ':estado' => $data['estado'] ?? 'en_cartera',
            ':banco' => $data['banco_emisor'] ?? null,
            ':numero' => $data['numero_cheque'] ?? null,
            ':titular' => $data['titular'] ?? null,
            ':cuit' => $data['cuit_titular'] ?? null,
            ':monto' => (int)($data['monto_cents'] ?? 0),
            ':fecha_emi' => $data['fecha_emision'] ?? date('Y-m-d'),
            ':fecha_ven' => $data['fecha_vencimiento'] ?? null,
            ':banco_cta' => $data['banco_cuenta_id'] ?? null,
            ':concepto' => $data['concepto'] ?? null,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function updateEstado(int $id, string $estado): void
    {
        $st = Db::pdo()->prepare('UPDATE cheques SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function agregarMovimiento(int $chequeId, string $tipo, ?string $origen = null, ?int $origenId = null, string $observaciones = '', int $createdBy = 0): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO cheque_movimientos (cheque_id, tipo, origen, origen_id, observaciones, created_by, created_at)
            VALUES (:cid, :tipo, :origen, :oid, :obs, :cb, NOW())
        ');
        $st->execute([
            ':cid' => $chequeId,
            ':tipo' => $tipo,
            ':origen' => $origen,
            ':oid' => $origenId,
            ':obs' => $observaciones,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }
}
