<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ReporteRepo
{
    public function resumenVentas(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM(f.total_cents), 0) AS total_cents,
                COALESCE(SUM(f.iva_cents), 0) AS iva_cents,
                COALESCE(SUM(f.subtotal_cents), 0) AS subtotal_cents
            FROM facturas f
            WHERE f.estado = 'emitida'
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
        ");
        $st->execute($params);
        return $st->fetch() ?: ['cantidad' => 0, 'total_cents' => 0, 'iva_cents' => 0, 'subtotal_cents' => 0];
    }

    public function ventasDiarias(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT f.fecha, COUNT(*) AS cantidad, COALESCE(SUM(f.total_cents), 0) AS total_cents
            FROM facturas f
            WHERE f.estado = 'emitida'
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY f.fecha
            ORDER BY f.fecha ASC
        ");
        $st->execute($params);
        return $st->fetchAll();
    }

    public function topProductos(string $desde, string $hasta, int $limite = 10, int $puntoVenta = 0): array
    {
        $limite = max(1, min(50, $limite));
        $params = [':desde' => $desde, ':hasta' => $hasta, ':lim' => $limite];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT
                COALESCE(NULLIF(fi.producto, ''), '(sin nombre)') AS producto,
                fi.variedad,
                SUM(fi.qty) AS qty_total,
                SUM(fi.total_cents) AS total_cents,
                COUNT(DISTINCT f.id) AS facturas
            FROM factura_items fi
            INNER JOIN facturas f ON f.id = fi.factura_id
            WHERE f.estado = 'emitida'
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY fi.producto, fi.variedad
            ORDER BY qty_total DESC
            LIMIT :lim
        ");
        $st->execute($params);
        return $st->fetchAll();
    }

    public function ventasPorDepartamento(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT
                COALESCE(NULLIF(d.nomdepar, ''), 'Sin dep.') AS departamento,
                SUM(fi.qty) AS qty_total,
                SUM(fi.total_cents) AS total_cents
            FROM factura_items fi
            INNER JOIN facturas f ON f.id = fi.factura_id
            LEFT JOIN producto p ON p.idprodu = fi.idprodu
            LEFT JOIN departa d ON d.codepar = p.codepar
            WHERE f.estado = 'emitida'
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY d.codepar
            ORDER BY total_cents DESC
        ");
        $st->execute($params);
        return $st->fetchAll();
    }

    public function ventasPorFormaPago(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT fp.forma_pago, SUM(fp.monto_cents) AS total_cents, COUNT(DISTINCT fp.factura_id) AS cantidad
            FROM factura_pagos fp
            INNER JOIN facturas f ON f.id = fp.factura_id
            WHERE f.estado = 'emitida'
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY fp.forma_pago
            ORDER BY total_cents DESC
        ");
        $st->execute($params);
        return $st->fetchAll();
    }

    public function resumenRecibos(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND r.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM(r.total_cents), 0) AS total_cents
            FROM recibos r
            WHERE r.estado = 'emitido'
              AND r.fecha BETWEEN :desde AND :hasta
              $pvWhere
        ");
        $st->execute($params);
        return $st->fetch() ?: ['cantidad' => 0, 'total_cents' => 0];
    }

    public function facturasPorTipo(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT f.tipo_comprobante, COUNT(*) AS cantidad, COALESCE(SUM(f.total_cents), 0) AS total_cents
            FROM facturas f
            WHERE f.estado = 'emitida'
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY f.tipo_comprobante
            ORDER BY total_cents DESC
        ");
        $st->execute($params);
        return $st->fetchAll();
    }
}
