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
        $having = 'HAVING saldo_cents != 0';
        $where = 'WHERE (m.cliente_id IS NOT NULL OR m.idclien IS NOT NULL)';
        if ($q !== '') {
            $where .= ' AND (w.name LIKE :like1 OR c.razon LIKE :like2 OR c.cuit LIKE :like3 OR w.email LIKE :like4 OR w.phone LIKE :like5 OR c.mail LIKE :like6 OR c.tele LIKE :like7)';
            for ($i = 1; $i <= 7; $i++) {
                $params[':like' . $i] = '%' . $q . '%';
            }
            $having = ''; // don't filter by balance when searching
        }

        $accountKey = "CASE WHEN m.cliente_id IS NOT NULL THEN CONCAT('web:', m.cliente_id) ELSE CONCAT('erp:', m.idclien) END";
        $sql = "
            SELECT COALESCE(MAX(w.id), 0) AS id, MAX(m.idclien) AS idclien,
                   COALESCE(MAX(w.name), MAX(c.razon), CONCAT('Cliente ERP #', MAX(m.idclien))) AS name,
                   COALESCE(MAX(w.email), MAX(c.mail)) AS email,
                   COALESCE(MAX(w.phone), MAX(c.tele)) AS phone,
                   COALESCE(SUM(CASE WHEN m.tipo = 'debito' THEN m.monto_cents ELSE 0 END), 0) AS debitos,
                   COALESCE(SUM(CASE WHEN m.tipo = 'credito' THEN m.monto_cents ELSE 0 END), 0) AS creditos,
                   COALESCE(SUM(CASE WHEN m.tipo = 'debito' THEN m.monto_cents ELSE -m.monto_cents END), 0) AS saldo_cents,
                   MAX(m.created_at) AS ultimo_mov
            FROM ctacte_movimientos m
            LEFT JOIN web_users w ON w.id = m.cliente_id
            LEFT JOIN clientes c ON c.idclien = m.idclien
            {$where}
            GROUP BY {$accountKey}
            {$having}
            ORDER BY saldo_cents DESC, name ASC
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

    public function saldoActualErp(int $idclien): int
    {
        $st = Db::pdo()->prepare("SELECT COALESCE(SUM(CASE WHEN tipo = 'debito' THEN monto_cents ELSE -monto_cents END), 0)
            FROM ctacte_movimientos WHERE cliente_id IS NULL AND idclien = :c");
        $st->execute([':c' => $idclien]);
        return (int)$st->fetchColumn();
    }

    public function ultimoSaldo(int $clienteId): int
    {
        $pdo = Db::pdo();
        $lock = $pdo->inTransaction() ? ' FOR UPDATE' : '';
        $st = $pdo->prepare('
            SELECT saldo_after_cents FROM ctacte_movimientos
            WHERE cliente_id = :c ORDER BY id DESC LIMIT 1' . $lock);
        $st->execute([':c' => $clienteId]);
        $val = $st->fetchColumn();
        return $val !== false ? (int)$val : 0;
    }

    public function ultimoSaldoErp(int $idclien): int
    {
        $pdo = Db::pdo();
        $lock = $pdo->inTransaction() ? ' FOR UPDATE' : '';
        $st = $pdo->prepare('SELECT saldo_after_cents FROM ctacte_movimientos WHERE cliente_id IS NULL AND idclien = :c ORDER BY id DESC LIMIT 1' . $lock);
        $st->execute([':c' => $idclien]);
        $val = $st->fetchColumn();
        return $val !== false ? (int)$val : 0;
    }

    public function agregarMovimiento(string $tipo, string $origen, ?int $origenId, ?int $clienteId, ?int $idclien, int $montoCents, string $concepto, int $createdBy): int
    {
        if (($clienteId ?? 0) > 0) {
            $saldoActual = $this->ultimoSaldo((int)$clienteId);
        } elseif (($idclien ?? 0) > 0) {
            $saldoActual = $this->ultimoSaldoErp((int)$idclien);
        } else {
            throw new \InvalidArgumentException('El movimiento de cuenta corriente requiere un cliente web o ERP.');
        }
        $saldoAfter = $tipo === 'debito' ? $saldoActual + $montoCents : $saldoActual - $montoCents;

        $st = Db::pdo()->prepare('
            INSERT INTO ctacte_movimientos (cliente_id, idclien, tipo, origen, origen_id, monto_cents, saldo_after_cents, concepto, created_by, created_at)
            VALUES (:cliente_id, :idclien, :tipo, :origen, :origen_id, :monto, :saldo_after, :concepto, :created_by, NOW())
        ');
        $st->execute([
            ':cliente_id' => ($clienteId ?? 0) > 0 ? $clienteId : null,
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
        $lock = $pdo->inTransaction() ? ' FOR UPDATE' : '';
        $st = $pdo->prepare('SELECT * FROM ctacte_movimientos WHERE origen = :o AND origen_id = :oid ORDER BY id ASC' . $lock);
        $st->execute([':o' => $origen, ':oid' => $origenId]);
        $movs = $st->fetchAll();

        if (!$movs) return;

        $ownsTransaction = !$pdo->inTransaction();
        if ($ownsTransaction) {
            $pdo->beginTransaction();
        }
        try {
            // Delete the movements (they'll be re-created as credits if needed)
            $del = $pdo->prepare('DELETE FROM ctacte_movimientos WHERE origen = :o AND origen_id = :oid');
            $del->execute([':o' => $origen, ':oid' => $origenId]);

            // Recalculate all subsequent balances for this client
            $clienteId = (int)($movs[0]['cliente_id'] ?? 0);
            if ($clienteId > 0) {
                $this->recalcularSaldos($clienteId);
            } else {
                $idclien = (int)($movs[0]['idclien'] ?? 0);
                if ($idclien > 0) {
                    $this->recalcularSaldosErp($idclien);
                }
            }

            if ($ownsTransaction) {
                $pdo->commit();
            }
        } catch (\Throwable $e) {
            if ($ownsTransaction && $pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    public function recalcularSaldos(int $clienteId): void
    {
        $pdo = Db::pdo();
        $lock = $pdo->inTransaction() ? ' FOR UPDATE' : '';
        $movs = $pdo->prepare('
            SELECT id, tipo, monto_cents FROM ctacte_movimientos
            WHERE cliente_id = :c ORDER BY id ASC' . $lock . '
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

    private function recalcularSaldosErp(int $idclien): void
    {
        $pdo = Db::pdo();
        $lock = $pdo->inTransaction() ? ' FOR UPDATE' : '';
        $movs = $pdo->prepare("SELECT id, tipo, monto_cents FROM ctacte_movimientos WHERE cliente_id IS NULL AND idclien = :c ORDER BY id ASC" . $lock);
        $movs->execute([':c' => $idclien]);
        $saldo = 0;
        $upd = $pdo->prepare('UPDATE ctacte_movimientos SET saldo_after_cents = :s WHERE id = :id');
        foreach ($movs as $m) {
            $saldo += $m['tipo'] === 'debito' ? (int)$m['monto_cents'] : -(int)$m['monto_cents'];
            $upd->execute([':s' => $saldo, ':id' => $m['id']]);
        }
    }

    public function movimientosErp(int $idclien, int $limit = 100): array
    {
        $limit = max(1, min(500, $limit));
        $st = Db::pdo()->prepare('SELECT m.*, a.nombre AS created_by_nombre FROM ctacte_movimientos m LEFT JOIN admin_users a ON a.id = m.created_by WHERE m.cliente_id IS NULL AND m.idclien = :c ORDER BY m.id DESC LIMIT ' . $limit);
        $st->execute([':c' => $idclien]);
        return $st->fetchAll();
    }

    /** Backfill idempotente de facturas emitidas a cuenta corriente del cliente ERP. */
    public function sincronizarFacturasErp(int $idclien, int $adminId): int
    {
        if ($idclien <= 0) {
            return 0;
        }
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("SELECT f.id, f.codigo, f.tipo_comprobante, f.total_cents
                FROM facturas f
                WHERE f.idclien = :c AND f.estado = 'emitida'
                  AND (f.forma_pago = 'cuenta_corriente'
                       OR EXISTS (SELECT 1 FROM factura_pagos fp WHERE fp.factura_id = f.id AND fp.forma_pago = 'cuenta_corriente'))
                ORDER BY f.id ASC FOR UPDATE");
            $st->execute([':c' => $idclien]);
            $facturas = $st->fetchAll();
            $montoCta = $pdo->prepare("SELECT COALESCE(SUM(monto_cents), 0) FROM factura_pagos WHERE factura_id = :f AND forma_pago = 'cuenta_corriente'");
            $existeMov = $pdo->prepare("SELECT COUNT(*) FROM ctacte_movimientos WHERE origen = 'factura' AND origen_id = :f");
            $insertadas = 0;
            foreach ($facturas as $factura) {
                $facturaId = (int)$factura['id'];
                $existeMov->execute([':f' => $facturaId]);
                if ((int)$existeMov->fetchColumn() > 0) {
                    continue;
                }
                $montoCta->execute([':f' => $facturaId]);
                $monto = (int)$montoCta->fetchColumn();
                if ($monto <= 0) {
                    $monto = (int)($factura['total_cents'] ?? 0);
                }
                if ($monto <= 0) {
                    continue;
                }
                $this->agregarMovimiento(
                    (string)($factura['tipo_comprobante'] ?? '') === 'NC' ? 'credito' : 'debito',
                    'factura',
                    $facturaId,
                    null,
                    $idclien,
                    $monto,
                    ((string)($factura['tipo_comprobante'] ?? '') === 'NC' ? 'NC ' : 'Factura ') . (string)($factura['codigo'] ?? '') . ' — cuenta corriente',
                    $adminId
                );
                $insertadas++;
            }
            $pdo->commit();
            return $insertadas;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
