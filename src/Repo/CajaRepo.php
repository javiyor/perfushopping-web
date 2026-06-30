<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class CajaRepo
{
    public function aperturaActiva(int $sucursalId, string $turno, string $fecha): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT ca.*, a.nombre AS created_by_nombre
            FROM caja_aperturas ca
            LEFT JOIN admin_users a ON a.id = ca.created_by
            WHERE ca.sucursal_id = :suc
              AND ca.turno = :tur
              AND ca.fecha = :fec
              AND ca.estado = \'abierta\'
            ORDER BY ca.id DESC LIMIT 1
        ');
        $st->execute([':suc' => $sucursalId, ':tur' => $turno, ':fec' => $fecha]);
        return $st->fetch() ?: null;
    }

    public function abrir(int $sucursalId, string $turno, string $fecha, int $montoInicialCents, ?string $obs, int $createdBy): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO caja_aperturas (sucursal_id, turno, fecha, monto_inicial_cents, estado, observaciones, created_by, created_at, updated_at)
            VALUES (:suc, :tur, :fec, :mon, \'abierta\', :obs, :cb, NOW(), NOW())
        ');
        $st->execute([
            ':suc' => $sucursalId,
            ':tur' => $turno,
            ':fec' => $fecha,
            ':mon' => $montoInicialCents,
            ':obs' => $obs,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function cerrar(int $id, int $montoCierreCents, int $cerradaPor): void
    {
        $st = Db::pdo()->prepare('
            UPDATE caja_aperturas SET estado = \'cerrada\', monto_cierre_cents = :mon, cerrada_por = :cp, updated_at = NOW()
            WHERE id = :i LIMIT 1
        ');
        $st->execute([':mon' => $montoCierreCents, ':cp' => $cerradaPor, ':i' => $id]);
    }

    public function agregarMovimiento(int $cajaId, string $tipo, string $concepto, int $montoCents, int $createdBy): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO caja_movimientos (caja_id, tipo, concepto, monto_cents, created_by, created_at)
            VALUES (:ci, :tip, :con, :mon, :cb, NOW())
        ');
        $st->execute([
            ':ci' => $cajaId,
            ':tip' => $tipo,
            ':con' => $concepto,
            ':mon' => $montoCents,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function movimientos(int $cajaId): array
    {
        $st = Db::pdo()->prepare('
            SELECT cm.*, a.nombre AS created_by_nombre
            FROM caja_movimientos cm
            LEFT JOIN admin_users a ON a.id = cm.created_by
            WHERE cm.caja_id = :ci
            ORDER BY cm.created_at ASC
        ');
        $st->execute([':ci' => $cajaId]);
        return $st->fetchAll();
    }

    public function totalMovimientos(int $cajaId): array
    {
        $st = Db::pdo()->prepare("
            SELECT
                COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto_cents ELSE 0 END), 0) AS total_ingresos,
                COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto_cents ELSE 0 END), 0) AS total_egresos
            FROM caja_movimientos
            WHERE caja_id = :ci
        ");
        $st->execute([':ci' => $cajaId]);
        return $st->fetch() ?: ['total_ingresos' => 0, 'total_egresos' => 0];
    }

    public function totalVentasEfectivo(string $fecha, int $puntoVenta): int
    {
        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(fp.monto_cents), 0)
            FROM factura_pagos fp
            INNER JOIN facturas f ON f.id = fp.factura_id
            WHERE f.estado = 'emitida'
              AND f.fecha = :fec
              AND f.punto_venta = :pv
              AND fp.forma_pago = 'efectivo'
        ");
        $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
        return (int)$st->fetchColumn();
    }

    public function totalVentasTransferencia(string $fecha, int $puntoVenta): int
    {
        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(fp.monto_cents), 0)
            FROM factura_pagos fp
            INNER JOIN facturas f ON f.id = fp.factura_id
            WHERE f.estado = 'emitida'
              AND f.fecha = :fec
              AND f.punto_venta = :pv
              AND fp.forma_pago IN ('transferencia', 'mercadopago', 'debito', 'credito')
        ");
        $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
        return (int)$st->fetchColumn();
    }

    public function totalRecibos(string $fecha, int $puntoVenta): int
    {
        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(r.total_cents), 0)
            FROM recibos r
            WHERE r.estado = 'emitido'
              AND r.fecha = :fec
              AND r.punto_venta = :pv
        ");
        $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
        return (int)$st->fetchColumn();
    }

    public function arqueos(int $cajaId): array
    {
        $st = Db::pdo()->prepare('
            SELECT ca.*, a.nombre AS created_by_nombre
            FROM caja_arqueos ca
            LEFT JOIN admin_users a ON a.id = ca.created_by
            WHERE ca.caja_id = :ci
            ORDER BY ca.created_at DESC
        ');
        $st->execute([':ci' => $cajaId]);
        return $st->fetchAll();
    }

    public function registrarArqueo(int $cajaId, int $totalCents, string $obs, int $createdBy): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO caja_arqueos (caja_id, total_cents, observaciones, created_by, created_at)
            VALUES (:ci, :mon, :obs, :cb, NOW())
        ');
        $st->execute([
            ':ci' => $cajaId,
            ':mon' => $totalCents,
            ':obs' => $obs,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT ca.*, a.nombre AS created_by_nombre, c.nombre AS cerrada_por_nombre
            FROM caja_aperturas ca
            LEFT JOIN admin_users a ON a.id = ca.created_by
            LEFT JOIN admin_users c ON c.id = ca.cerrada_por
            WHERE ca.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function historial(int $sucursalId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        $st = Db::pdo()->prepare('
            SELECT ca.*, a.nombre AS created_by_nombre
            FROM caja_aperturas ca
            LEFT JOIN admin_users a ON a.id = ca.created_by
            WHERE ca.sucursal_id = :suc
            ORDER BY ca.created_at DESC
            LIMIT ' . $limit
        );
        $st->execute([':suc' => $sucursalId]);
        return $st->fetchAll();
    }
}
