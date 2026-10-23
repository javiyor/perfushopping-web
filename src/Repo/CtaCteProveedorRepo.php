<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\OrdenPagoRepo;
use Perfushopping\Web\Support\Plazo;

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
            ORDER BY m.proveedor_nombre ASC
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

    /** @param array<int,int> $proveedorIds @return array<int,array{vencido_cents:int,credito_pendiente_cents:int,vencido_neto_cents:int}> */
    public function resumenVencidoPorProveedor(array $proveedorIds): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $proveedorIds), static fn(int $id): bool => $id > 0)));
        if (!$ids) return [];
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $summary = [];
        foreach ($ids as $id) $summary[$id] = ['vencido_cents' => 0, 'credito_pendiente_cents' => 0, 'vencido_neto_cents' => 0];

        $sql = "
            SELECT fc.id, fc.idprovee, fc.fecha, fc.imp_total, fc.plazo_cuotas, fc.plazo_dias, fc.tipo,
                   COALESCE(asig.total, 0) AS asignado_cents
            FROM factura_compra fc
            LEFT JOIN (
                SELECT opc.factura_compra_id, SUM(opc.monto_cents) AS total
                FROM orden_pago_compras opc
                INNER JOIN ordenes_pago o ON o.id=opc.orden_pago_id AND o.estado='pagada'
                GROUP BY opc.factura_compra_id
            ) asig ON asig.factura_compra_id = fc.id
            WHERE fc.idprovee IN ({$marks}) AND COALESCE(fc.estado, '') <> 'anulada'
        ";
        $st = Db::pdo()->prepare($sql);
        $st->execute($ids);
        $today = date('Y-m-d');
        foreach ($st->fetchAll() ?: [] as $row) {
            if (CompraRepo::esNotaCredito((string)($row['tipo'] ?? ''))) continue;
            $providerId = (int)$row['idprovee'];
            if (!isset($summary[$providerId])) continue;
            $totalCents = max(0, (int)round((float)($row['imp_total'] ?? 0) * 100));
            $assignedCents = max(0, min($totalCents, (int)($row['asignado_cents'] ?? 0)));
            $schedule = Plazo::cronograma($row);
            $summary[$providerId]['vencido_cents'] += $this->vencidoPorCronograma($schedule, $assignedCents, $today);
        }

        $credits = Db::pdo()->prepare("SELECT m.proveedor_id, SUM(m.monto_cents)
            FROM ctacte_proveedor_movimientos m
            LEFT JOIN ordenes_pago o ON o.id=m.origen_id AND m.origen='op'
            WHERE m.tipo='credito' AND m.proveedor_id IN ({$marks})
              AND ((m.origen='op' AND o.estado='pagada') OR m.origen IN ('compra', 'ajuste'))
            GROUP BY m.proveedor_id");
        $credits->execute($ids);
        $creditByProvider = [];
        foreach ($credits->fetchAll(\PDO::FETCH_NUM) ?: [] as $row) $creditByProvider[(int)$row[0]] = (int)$row[1];

        $assigned = Db::pdo()->prepare("SELECT o.proveedor_id, SUM(opc.monto_cents) AS total, fc.tipo FROM orden_pago_compras opc INNER JOIN ordenes_pago o ON o.id=opc.orden_pago_id AND o.estado='pagada' INNER JOIN factura_compra fc ON fc.id=opc.factura_compra_id AND COALESCE(fc.estado, '') <> 'anulada' WHERE o.proveedor_id IN ({$marks}) GROUP BY o.proveedor_id, fc.tipo");
        $assigned->execute($ids);
        $assignedByProvider = [];
        foreach ($assigned->fetchAll() ?: [] as $row) {
            if (CompraRepo::esNotaCredito((string)($row['tipo'] ?? ''))) continue;
            $id = (int)$row['proveedor_id'];
            $assignedByProvider[$id] = ($assignedByProvider[$id] ?? 0) + (int)$row['total'];
        }
        foreach ((new OrdenPagoRepo())->ajustesAsignadosPorProveedor($ids) as $id => $amount) {
            $assignedByProvider[$id] = ($assignedByProvider[$id] ?? 0) + $amount;
        }

        foreach ($summary as $id => &$values) {
            $values['credito_pendiente_cents'] = max(0, ($creditByProvider[$id] ?? 0) - ($assignedByProvider[$id] ?? 0));
            $values['vencido_neto_cents'] = max(0, $values['vencido_cents'] - $values['credito_pendiente_cents']);
        }
        unset($values);
        return $summary;
    }

    /** @param array<int,array{fecha:?string,monto:float}> $schedule */
    private function vencidoPorCronograma(array $schedule, int $assignedCents, string $today): int
    {
        $vencido = 0;
        $saldoAsignado = max(0, $assignedCents);
        foreach ($schedule as $installment) {
            $amount = max(0, (int)round((float)($installment['monto'] ?? 0) * 100));
            $paid = min($amount, $saldoAsignado);
            $saldoAsignado -= $paid;
            $dueDate = (string)($installment['fecha'] ?? '');
            if ($dueDate !== '' && $dueDate < $today) $vencido += $amount - $paid;
        }
        return $vencido;
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

    public function anularMovimientosPorOrigen(string $origen, int $origenId): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('SELECT * FROM ctacte_proveedor_movimientos WHERE origen = :o AND origen_id = :oid ORDER BY id ASC');
        $st->execute([':o' => $origen, ':oid' => $origenId]);
        $movs = $st->fetchAll();

        if (!$movs) return;

        $pdo->beginTransaction();
        try {
            $del = $pdo->prepare('DELETE FROM ctacte_proveedor_movimientos WHERE origen = :o AND origen_id = :oid');
            $del->execute([':o' => $origen, ':oid' => $origenId]);

            $grupos = [];
            foreach ($movs as $m) {
                $pid = (int)($m['proveedor_id'] ?? 0);
                $pn = (string)($m['proveedor_nombre'] ?? '');
                $key = $pid > 0 ? 'id:' . $pid : 'nm:' . $pn;
                $grupos[$key] = [$pid, $pn];
            }
            foreach ($grupos as [$pid, $pn]) {
                $this->recalcularSaldos($pid > 0 ? $pid : null, $pn);
            }

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function recalcularSaldos(?int $proveedorId = null, string $proveedorNombre = ''): void
    {
        $params = [];
        $where = '';
        if ($proveedorId !== null) {
            $where = ' WHERE proveedor_id = :pid';
            $params[':pid'] = $proveedorId;
        } elseif ($proveedorNombre !== '') {
            $where = ' WHERE proveedor_nombre = :pn AND proveedor_id IS NULL';
            $params[':pn'] = $proveedorNombre;
        } else {
            return;
        }

        $movs = Db::pdo()->prepare('
            SELECT id, tipo, monto_cents FROM ctacte_proveedor_movimientos' . $where . ' ORDER BY id ASC
        ');
        $movs->execute($params);
        $saldo = 0;
        $upd = Db::pdo()->prepare('UPDATE ctacte_proveedor_movimientos SET saldo_after_cents = :s WHERE id = :id');
        foreach ($movs as $m) {
            if ($m['tipo'] === 'debito') {
                $saldo += (int)$m['monto_cents'];
            } else {
                $saldo -= (int)$m['monto_cents'];
            }
            $upd->execute([':s' => $saldo, ':id' => $m['id']]);
        }
    }

    /** Corrige movimientos previos de notas de crédito cargadas como débito. */
    public function normalizarNotasCreditoCompra(): int
    {
        $st = Db::pdo()->query("
            SELECT m.id, m.proveedor_id, m.proveedor_nombre, fc.tipo, fc.imp_total
            FROM ctacte_proveedor_movimientos m
            INNER JOIN factura_compra fc ON fc.id = m.origen_id
            WHERE m.origen = 'compra' AND m.tipo = 'debito' AND COALESCE(fc.estado, '') <> 'anulada'
        ");
        $movimientos = [];
        foreach ($st->fetchAll() ?: [] as $row) {
            if (CompraRepo::esNotaCredito((string)($row['tipo'] ?? ''))) $movimientos[] = $row;
        }
        if (!$movimientos) return 0;

        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $update = $pdo->prepare("UPDATE ctacte_proveedor_movimientos SET tipo='credito', monto_cents=:m WHERE id=:id LIMIT 1");
            $providers = [];
            foreach ($movimientos as $row) {
                $update->execute([
                    ':m' => (int)round(abs((float)($row['imp_total'] ?? 0)) * 100),
                    ':id' => (int)$row['id'],
                ]);
                $providerId = (int)($row['proveedor_id'] ?? 0);
                $providerName = (string)($row['proveedor_nombre'] ?? '');
                $key = $providerId > 0 ? 'id:' . $providerId : 'name:' . $providerName;
                $providers[$key] = [$providerId, $providerName];
            }
            foreach ($providers as [$providerId, $providerName]) {
                $this->recalcularSaldos($providerId > 0 ? $providerId : null, $providerName);
            }
            $pdo->commit();
            return count($movimientos);
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Backfill: genera débitos/créditos en ctacte de los comprobantes de compra,
     * en orden de fecha, y recalcula saldos. Idempotente.
     * @return array{insertadas:int, omitidas:int}
     */
    public function sincronizarCompras(int $adminId): array
    {
        $pdo = Db::pdo();
        $rows = $pdo->query("
            SELECT fc.id, fc.idprovee, fc.razon_proveedor, fc.cuit_proveedor, fc.tipo,
                   fc.punto_venta, fc.numero_desde, fc.imp_total
            FROM factura_compra fc
            WHERE COALESCE(fc.estado, '') <> 'anulada'
            ORDER BY fc.fecha ASC, fc.id ASC
        ")->fetchAll();

        $insertadas = 0;
        $omitidas = 0;
        foreach ($rows as $r) {
            $compraId = (int)$r['id'];
            $impTotal = (float)($r['imp_total'] ?? 0);
            $notaCredito = CompraRepo::esNotaCredito((string)($r['tipo'] ?? ''));
            if (abs($impTotal) <= 0) {
                $omitidas++;
                continue;
            }

            $idprovee = (int)($r['idprovee'] ?? 0) ?: null;
            $razon = trim((string)($r['razon_proveedor'] ?? ''));
            if ($razon === '') {
                $razon = $this->razonProveedor($pdo, $idprovee, (string)($r['cuit_proveedor'] ?? ''));
            }
            if ($razon === '') {
                $razon = 'Proveedor';
            }

            $this->anularMovimientosPorOrigen('compra', $compraId);
            $this->agregarMovimiento(
                $notaCredito ? 'credito' : 'debito',
                'compra',
                $compraId,
                $idprovee,
                $razon,
                (int)round(abs($impTotal) * 100),
                ($notaCredito ? 'Nota de crédito ' : 'Compra ') . (string)$r['tipo'] . ' ' . (string)$r['punto_venta'] . '-' . (string)$r['numero_desde'] . ' — ' . $razon,
                $adminId
            );
            $insertadas++;
        }

        return ['insertadas' => $insertadas, 'omitidas' => $omitidas];
    }

    /**
     * Backfill idempotente: asigna el remanente sin asignar de cada OP a las
     * facturas impagas del proveedor (FIFO por fecha). Cubre las OP creadas
     * antes de existir la tabla orden_pago_compras. Devuelve filas creadas.
     */
    public function sincronizarAsignaciones(?int $proveedorId = null): int
    {
        $pdo = Db::pdo();

        if ($proveedorId !== null) {
            $pids = [$proveedorId];
        } else {
            $st = $pdo->query("
                SELECT o.proveedor_id
                FROM ordenes_pago o
                LEFT JOIN (
                    SELECT opc.orden_pago_id, SUM(opc.monto_cents) AS asignado
                    FROM orden_pago_compras opc
                    INNER JOIN ordenes_pago op ON op.id=opc.orden_pago_id AND op.estado='pagada'
                    INNER JOIN factura_compra fc ON fc.id = opc.factura_compra_id AND COALESCE(fc.estado, '') <> 'anulada'
                    WHERE UPPER(fc.tipo) NOT LIKE '%NOTA%CR%DITO%' AND UPPER(fc.tipo) NOT LIKE 'NC%'
                    GROUP BY opc.orden_pago_id
                ) x ON x.orden_pago_id = o.id
                WHERE o.proveedor_id IS NOT NULL AND o.estado = 'pagada'
                  AND (o.monto_cents - COALESCE(x.asignado, 0)) > 0
                GROUP BY o.proveedor_id
            ");
            $pids = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN) ?: []);
        }

        $creadas = 0;
        $ins = $pdo->prepare('
            INSERT INTO orden_pago_compras (orden_pago_id, factura_compra_id, monto_cents, created_at)
            VALUES (:op, :compra, :monto, NOW())
        ');
        foreach ($pids as $pid) {
            if ($pid <= 0) {
                continue;
            }
            $ops = $pdo->prepare("SELECT id, monto_cents FROM ordenes_pago WHERE proveedor_id = :p AND estado='pagada' ORDER BY id ASC");
            $ops->execute([':p' => $pid]);
            $ops = $ops->fetchAll();
            if (!$ops) {
                continue;
            }
            $facts = $pdo->prepare("SELECT id, imp_total FROM factura_compra WHERE idprovee = :p AND COALESCE(estado, '') <> 'anulada' AND UPPER(tipo) NOT LIKE '%NOTA%CR%DITO%' AND UPPER(tipo) NOT LIKE 'NC%' ORDER BY fecha ASC, id ASC");
            $facts->execute([':p' => $pid]);
            $facts = $facts->fetchAll();
            if (!$facts) {
                continue;
            }

            $opIds = array_column($ops, 'id');
            $in = implode(',', array_map('intval', $opIds));
            $asigOp = [];
            foreach ($pdo->query("SELECT opc.orden_pago_id, COALESCE(SUM(opc.monto_cents), 0) AS t FROM orden_pago_compras opc INNER JOIN ordenes_pago op ON op.id=opc.orden_pago_id AND op.estado='pagada' INNER JOIN factura_compra fc ON fc.id=opc.factura_compra_id AND COALESCE(fc.estado, '') <> 'anulada' WHERE opc.orden_pago_id IN ($in) AND UPPER(fc.tipo) NOT LIKE '%NOTA%CR%DITO%' AND UPPER(fc.tipo) NOT LIKE 'NC%' GROUP BY opc.orden_pago_id")->fetchAll() as $r) {
                $asigOp[(int)$r['orden_pago_id']] = (int)$r['t'];
            }
            $factIds = array_column($facts, 'id');
            $inF = implode(',', array_map('intval', $factIds));
            $asigFact = [];
            foreach ($pdo->query("SELECT opc.factura_compra_id, COALESCE(SUM(opc.monto_cents), 0) AS t FROM orden_pago_compras opc INNER JOIN ordenes_pago op ON op.id=opc.orden_pago_id AND op.estado='pagada' WHERE opc.factura_compra_id IN ($inF) GROUP BY opc.factura_compra_id")->fetchAll() as $r) {
                $asigFact[(int)$r['factura_compra_id']] = (int)$r['t'];
            }

            $pdo->beginTransaction();
            try {
                foreach ($ops as $op) {
                    $opId = (int)$op['id'];
                    $restante = (int)$op['monto_cents'] - ($asigOp[$opId] ?? 0);
                    if ($restante <= 0) {
                        continue;
                    }
                    foreach ($facts as $f) {
                        if ($restante <= 0) {
                            break;
                        }
                        $fid = (int)$f['id'];
                        $pend = (int)round(((float)($f['imp_total'] ?? 0)) * 100) - ($asigFact[$fid] ?? 0);
                        $a = min($pend, $restante);
                        if ($a > 0) {
                            $ins->execute([':op' => $opId, ':compra' => $fid, ':monto' => $a]);
                            $asigFact[$fid] = ($asigFact[$fid] ?? 0) + $a;
                            $restante -= $a;
                            $creadas++;
                        }
                    }
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                throw $e;
            }
        }

        return $creadas;
    }

    /**
     * Comprobantes de compra de un proveedor con cronograma y estado según
     * las asignaciones reales de las órdenes de pago.
     * @return array<int, array{id:int, tipo:string, punto_venta:string, numero_desde:string, fecha:?string, imp_total:float, cronograma:array, estado:string, pendiente:float, vencido:float}>
     */
    public function comprobantesConPlazo(?int $proveedorId, string $cuit = ''): array
    {
        $pdo = Db::pdo();
        $params = [];
        $where = '';
        if ($proveedorId !== null) {
            $where = " WHERE fc.idprovee = :pid AND COALESCE(fc.estado, '') <> 'anulada'";
            $params[':pid'] = $proveedorId;
        } elseif ($cuit !== '') {
            $where = " WHERE fc.cuit_proveedor = :c AND COALESCE(fc.estado, '') <> 'anulada'";
            $params[':c'] = $cuit;
        } else {
            return [];
        }

        $rows = $pdo->prepare('
            SELECT fc.id, fc.tipo, fc.punto_venta, fc.numero_desde, fc.fecha,
                   fc.imp_total, fc.plazo_cuotas, fc.plazo_dias
            FROM factura_compra fc' . $where . '
            ORDER BY fc.fecha ASC, fc.id ASC
        ');
        $rows->execute($params);
        $comprobantes = $rows->fetchAll();

        $opRepo = new OrdenPagoRepo();
        $out = [];
        foreach ($comprobantes as $c) {
            $esCredito = CompraRepo::esNotaCredito((string)($c['tipo'] ?? ''));
            $total = abs((float)($c['imp_total'] ?? 0));
            $asignadoCents = $esCredito ? 0 : $opRepo->totalAsignadoCompra((int)$c['id']);
            $asignado = $asignadoCents / 100;
            if ($esCredito) {
                $estado = 'Crédito pendiente';
                $pendiente = $total;
            } else {
                $pendiente = round($total - $asignado, 2);
                if ($pendiente <= 0) {
                    $estado = 'Pagada';
                    $pendiente = 0;
                } elseif ($asignado > 0) {
                    $estado = 'Parcial';
                } else {
                    $estado = 'Pendiente';
                }
            }
            $cronograma = $esCredito ? [] : Plazo::cronograma($c);
            $ultima = end($cronograma);
            $vencidoCents = $esCredito ? 0 : $this->vencidoPorCronograma($cronograma, min((int)round($total * 100), max(0, $asignadoCents)), date('Y-m-d'));
            $out[] = [
                'id' => (int)$c['id'],
                'tipo' => (string)($c['tipo'] ?? ''),
                'punto_venta' => (string)($c['punto_venta'] ?? ''),
                'numero_desde' => (string)($c['numero_desde'] ?? ''),
                'fecha' => $c['fecha'],
                'imp_total' => $total,
                'cronograma' => $cronograma,
                'vencimiento' => ($ultima['fecha'] ?? null) ?: null,
                'estado' => $estado,
                'pendiente' => $pendiente,
                'es_credito' => $esCredito,
                'vencido_cents' => $vencidoCents,
                'vencido' => $vencidoCents / 100,
            ];
        }
        if ($proveedorId !== null) {
            $adjustments = $pdo->prepare("SELECT id, monto_cents, concepto, created_at, tipo
                FROM ctacte_proveedor_movimientos
                WHERE proveedor_id=:p AND tipo IN ('debito', 'credito') AND origen='ajuste'
                ORDER BY created_at ASC, id ASC");
            $adjustments->execute([':p' => $proveedorId]);
            foreach ($adjustments->fetchAll() ?: [] as $adjustment) {
                $movementId = (int)$adjustment['id'];
                $totalCents = max(0, (int)$adjustment['monto_cents']);
                $isCredit = (string)$adjustment['tipo'] === 'credito';
                $assignedCents = $isCredit ? 0 : min($totalCents, $opRepo->totalAsignadoAjuste($movementId));
                $pendingCents = $isCredit ? $totalCents : max(0, $totalCents - $assignedCents);
                $state = $isCredit ? 'Crédito pendiente' : ($pendingCents <= 0 ? 'Pagada' : ($assignedCents > 0 ? 'Parcial' : 'Pendiente'));
                $out[] = [
                    'id' => $movementId,
                    'tipo' => $isCredit ? 'Ajuste crédito' : 'Ajuste débito',
                    'punto_venta' => '',
                    'numero_desde' => (string)$movementId,
                    'fecha' => $adjustment['created_at'],
                    'imp_total' => $totalCents / 100,
                    'cronograma' => [],
                    'vencimiento' => null,
                    'estado' => $state,
                    'pendiente' => $pendingCents / 100,
                    'es_credito' => $isCredit,
                    'es_ajuste' => true,
                    'concepto' => (string)($adjustment['concepto'] ?? ''),
                    'vencido_cents' => 0,
                    'vencido' => 0,
                ];
            }
        }
        usort($out, static fn(array $a, array $b): int => strcmp((string)($a['fecha'] ?? ''), (string)($b['fecha'] ?? '')));
        return $out;
    }

    private function razonProveedor(\PDO $pdo, ?int $idprovee, string $cuit): string
    {
        if ($idprovee !== null && $idprovee > 0) {
            $st = $pdo->prepare('SELECT razon FROM proveedo WHERE idprovee = :i LIMIT 1');
            $st->execute([':i' => $idprovee]);
            $r = trim((string)($st->fetchColumn() ?: ''));
            if ($r !== '') {
                return $r;
            }
        }
        $cuit = (string)preg_replace('/\D/', '', $cuit);
        if ($cuit !== '' && $cuit !== '0') {
            $st = $pdo->prepare('SELECT razon FROM proveedo WHERE cuit = :c LIMIT 1');
            $st->execute([':c' => $cuit]);
            $r = trim((string)($st->fetchColumn() ?: ''));
            if ($r !== '') {
                return $r;
            }
        }
        return '';
    }
}
