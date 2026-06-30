<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CtaCteRepo
{
    public function listarConSaldo(string $q = ''): array
    {
        $q = trim($q);
        $params = [];
        $having = 'HAVING saldo != 0';

        $where = '';
        if ($q !== '') {
            $where = 'WHERE (w.name LIKE :like OR w.email LIKE :like OR w.phone LIKE :like)';
            $params[':like'] = '%' . $q . '%';
            $having = ''; // don't filter by balance when searching
        }

        $sql = "
            SELECT w.id, w.name, w.email, w.phone,
                   COALESCE(SUM(CASE WHEN m.tipo = 'debito' THEN m.monto_cents ELSE 0 END), 0) AS debitos,
                   COALESCE(SUM(CASE WHEN m.tipo = 'credito' THEN m.monto_cents ELSE 0 END), 0) AS creditos,
                   COALESCE(SUM(CASE WHEN m.tipo = 'debito' THEN m.monto_cents ELSE -m.monto_cents END), 0) AS saldo_cents,
                   MAX(m.created_at) AS ultimo_mov
            FROM ctacte_movimientos m
            INNER JOIN web_users w ON w.id = m.cliente_id
            {$where}
            GROUP BY w.id, w.name, w.email, w.phone
            {$having}
            ORDER BY saldo_cents DESC, w.name ASC
            LIMIT 100
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function movimientos(int $clienteId, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $st = Db::pdo()->prepare('
            SELECT m.*, a.nombre AS created_by_nombre
            FROM ctacte_movimientos m
            LEFT JOIN admin_users a ON a.id = m.created_by
            WHERE m.cliente_id = :c
            ORDER BY m.id DESC
            LIMIT ' . $limit
        );
        $st->execute([':c' => $clienteId]);
        return $st->fetchAll();
    }

    public function saldoActual(int $clienteId): int
    {
        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(CASE WHEN tipo = 'debito' THEN monto_cents ELSE -monto_cents END), 0)
            FROM ctacte_movimientos WHERE cliente_id = :c
        ");
        $st->execute([':c' => $clienteId]);
        return (int)$st->fetchColumn();
    }

    public function ultimoSaldo(int $clienteId): int
    {
        $st = Db::pdo()->prepare('
            SELECT saldo_after_cents FROM ctacte_movimientos
            WHERE cliente_id = :c ORDER BY id DESC LIMIT 1
        ');
        $st->execute([':c' => $clienteId]);
        $val = $st->fetchColumn();
        return $val !== false ? (int)$val : 0;
    }

    public function agregarMovimiento(string $tipo, string $origen, ?int $origenId, int $clienteId, ?int $idclien, int $montoCents, string $concepto, int $createdBy): int
    {
        $saldoActual = $this->ultimoSaldo($clienteId);
        $saldoAfter = $tipo === 'debito' ? $saldoActual + $montoCents : $saldoActual - $montoCents;

        $st = Db::pdo()->prepare('
            INSERT INTO ctacte_movimientos (cliente_id, idclien, tipo, origen, origen_id, monto_cents, saldo_after_cents, concepto, created_by, created_at)
            VALUES (:cliente_id, :idclien, :tipo, :origen, :origen_id, :monto, :saldo_after, :concepto, :created_by, NOW())
        ');
        $st->execute([
            ':cliente_id' => $clienteId,
            ':idclien' => $idclien,
            ':tipo' => $tipo,
            ':origen' => $origen,
            ':origen_id' => $origenId,
            ':monto' => $montoCents,
            ':saldo_after' => $saldoAfter,
            ':concepto' => $concepto,
            ':created_by' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function anularMovimientosPorOrigen(string $origen, int $origenId): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT * FROM ctacte_movimientos WHERE origen = :o AND origen_id = :oid ORDER BY id ASC');
        $st->execute([':o' => $origen, ':oid' => $origenId]);
        $movs = $st->fetchAll();

        if (!$movs) return;

        $pdo->beginTransaction();
        try {
            // Delete the movements (they'll be re-created as credits if needed)
            $del = $pdo->prepare('DELETE FROM ctacte_movimientos WHERE origen = :o AND origen_id = :oid');
            $del->execute([':o' => $origen, ':oid' => $origenId]);

            // Recalculate all subsequent balances for this client
            $clienteId = (int)$movs[0]['cliente_id'];
            $this->recalcularSaldos($clienteId);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function recalcularSaldos(int $clienteId): void
    {
        $movs = Db::pdo()->prepare('
            SELECT id, tipo, monto_cents FROM ctacte_movimientos
            WHERE cliente_id = :c ORDER BY id ASC
        ');
        $movs->execute([':c' => $clienteId]);
        $saldo = 0;
        $upd = Db::pdo()->prepare('UPDATE ctacte_movimientos SET saldo_after_cents = :s WHERE id = :id');
        foreach ($movs as $m) {
            if ($m['tipo'] === 'debito') {
                $saldo += (int)$m['monto_cents'];
            } else {
                $saldo -= (int)$m['monto_cents'];
            }
            $upd->execute([':s' => $saldo, ':id' => $m['id']]);
        }
    }
}
