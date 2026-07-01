<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CtaCteProveedorRepo
{
    public function listarConSaldo(string $q = ''): array
    {
        $q = trim($q);
        $params = [];
        $having = 'HAVING saldo_cents != 0';
        $where = '';

        if ($q !== '') {
            $where = 'WHERE m.proveedor_nombre LIKE :like';
            $params[':like'] = '%' . $q . '%';
            $having = '';
        }

        $sql = "
            SELECT m.proveedor_id, m.proveedor_nombre,
                   COALESCE(SUM(CASE WHEN m.tipo = 'debito' THEN m.monto_cents ELSE 0 END), 0) AS debitos,
                   COALESCE(SUM(CASE WHEN m.tipo = 'credito' THEN m.monto_cents ELSE 0 END), 0) AS creditos,
                   COALESCE(SUM(CASE WHEN m.tipo = 'debito' THEN m.monto_cents ELSE -m.monto_cents END), 0) AS saldo_cents,
                   MAX(m.created_at) AS ultimo_mov
            FROM ctacte_proveedor_movimientos m
            {$where}
            GROUP BY m.proveedor_id, m.proveedor_nombre
            {$having}
            ORDER BY saldo_cents DESC, m.proveedor_nombre ASC
            LIMIT 100
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function movimientos(?int $proveedorId = null, string $q = '', int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $params = [];
        $where = [];

        if ($proveedorId !== null) {
            $where[] = 'm.proveedor_id = :pid';
            $params[':pid'] = $proveedorId;
        }
        if (trim($q) !== '') {
            $where[] = '(m.proveedor_nombre LIKE :like OR m.concepto LIKE :like2)';
            $params[':like'] = '%' . $q . '%';
            $params[':like2'] = '%' . $q . '%';
        }

        $sql = '
            SELECT m.*, a.nombre AS created_by_nombre
            FROM ctacte_proveedor_movimientos m
            LEFT JOIN admin_users a ON a.id = m.created_by
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY m.id DESC LIMIT ' . $limit;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function saldoActual(?int $proveedorId = null): int
    {
        $params = [];
        $where = '';
        if ($proveedorId !== null) {
            $where = ' WHERE proveedor_id = :pid';
            $params[':pid'] = $proveedorId;
        }

        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(CASE WHEN tipo = 'debito' THEN monto_cents ELSE -monto_cents END), 0)
            FROM ctacte_proveedor_movimientos{$where}
        ");
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    public function ultimoSaldo(?int $proveedorId = null, string $proveedorNombre = ''): int
    {
        $params = [];
        $where = '';
        if ($proveedorId !== null) {
            $where = ' WHERE proveedor_id = :pid';
            $params[':pid'] = $proveedorId;
        } elseif ($proveedorNombre !== '') {
            $where = ' WHERE proveedor_nombre = :pn';
            $params[':pn'] = $proveedorNombre;
        }

        $st = Db::pdo()->prepare("
            SELECT saldo_after_cents FROM ctacte_proveedor_movimientos{$where} ORDER BY id DESC LIMIT 1
        ");
        $st->execute($params);
        $val = $st->fetchColumn();
        return $val !== false ? (int)$val : 0;
    }

    public function agregarMovimiento(
        string $tipo,
        string $origen,
        ?int $origenId,
        ?int $proveedorId,
        string $proveedorNombre,
        int $montoCents,
        string $concepto,
        int $createdBy
    ): int {
        $saldoActual = $this->ultimoSaldo($proveedorId, $proveedorNombre);
        $saldoAfter = $tipo === 'debito' ? $saldoActual + $montoCents : $saldoActual - $montoCents;

        $st = Db::pdo()->prepare('
            INSERT INTO ctacte_proveedor_movimientos (proveedor_id, proveedor_nombre, tipo, origen, origen_id, monto_cents, saldo_after_cents, concepto, created_by, created_at)
            VALUES (:pid, :pn, :tipo, :origen, :oid, :monto, :saldo_after, :concepto, :cb, NOW())
        ');
        $st->execute([
            ':pid' => $proveedorId,
            ':pn' => $proveedorNombre,
            ':tipo' => $tipo,
            ':origen' => $origen,
            ':oid' => $origenId,
            ':monto' => $montoCents,
            ':saldo_after' => $saldoAfter,
            ':concepto' => $concepto,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }
}
