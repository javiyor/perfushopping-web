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
        if ($puntoVenta > 0 && $this->recibosTienePuntoVenta()) {
            $pvWhere = ' AND r.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM(r.monto_cents), 0) AS total_cents
            FROM recibos r
            WHERE r.estado = 'emitido'
              AND r.fecha BETWEEN :desde AND :hasta
              $pvWhere
        ");
        $st->execute($params);
        return $st->fetch() ?: ['cantidad' => 0, 'total_cents' => 0];
    }

    private static ?array $recibosColumns = null;

    private function recibosTienePuntoVenta(): bool
    {
        if (self::$recibosColumns === null) {
            try {
                $st = Db::pdo()->query('SHOW COLUMNS FROM recibos');
                self::$recibosColumns = array_column($st->fetchAll(), 'Field');
            } catch (\Throwable $e) {
                self::$recibosColumns = [];
            }
        }
        return in_array('punto_venta', self::$recibosColumns, true);
    }

    /** Ventas agrupadas por sucursal (todas, sin filtro de sesión). */
    public function ventasPorSucursal(string $desde, string $hasta): array
    {
        try {
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(s.id, CONCAT('pv-', f.punto_venta)) AS sucursal_key,
                    COALESCE(s.nomsuc, spv_suc.nomsuc, CONCAT('PV ', f.punto_venta)) AS sucursal,
                    COUNT(*) AS cantidad,
                    COALESCE(SUM(f.total_cents), 0) AS total_cents
                FROM facturas f
                LEFT JOIN admin_sucursales s ON s.id = f.sucursal_id
                LEFT JOIN admin_sucursal_puntos_venta spv ON spv.punto_venta = f.punto_venta
                LEFT JOIN admin_sucursales spv_suc ON spv_suc.id = spv.sucursal_id
                WHERE f.estado = 'emitida'
                  AND f.fecha BETWEEN :desde AND :hasta
                GROUP BY sucursal_key
                ORDER BY total_cents DESC
            ");
            $st->execute([':desde' => $desde, ':hasta' => $hasta]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ventasPorSucursal error: ' . $e->getMessage());
            return [];
        }
    }

    /** Totales por mes calendario (mes = 'YYYY-MM'). */
    public function ventasMensuales(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        try {
            $params = [':desde' => $desde, ':hasta' => $hasta];
            $pvWhere = '';
            if ($puntoVenta > 0) {
                $pvWhere = ' AND f.punto_venta = :pv';
                $params[':pv'] = $puntoVenta;
            }
            $st = Db::pdo()->prepare("
                SELECT DATE_FORMAT(f.fecha, '%Y-%m') AS mes,
                       COUNT(*) AS cantidad,
                       COALESCE(SUM(f.total_cents), 0) AS total_cents
                FROM facturas f
                WHERE f.estado = 'emitida'
                  AND f.fecha BETWEEN :desde AND :hasta
                  $pvWhere
                GROUP BY mes
                ORDER BY mes ASC
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ventasMensuales error: ' . $e->getMessage());
            return [];
        }
    }

    private static ?array $facturasCols = null;
    private static ?array $facturaItemsCols = null;

    private function facturasTieneColumna(string $col): bool
    {
        if (self::$facturasCols === null) {
            try {
                $rows = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
                self::$facturasCols = array_column($rows, 'Field');
            } catch (\Throwable $e) {
                self::$facturasCols = [];
            }
        }
        return in_array($col, self::$facturasCols, true);
    }

    private function facturaItemsTieneColumna(string $col): bool
    {
        if (self::$facturaItemsCols === null) {
            try {
                $rows = Db::pdo()->query('SHOW COLUMNS FROM factura_items')->fetchAll();
                self::$facturaItemsCols = array_column($rows, 'Field');
            } catch (\Throwable $e) {
                self::$facturaItemsCols = [];
            }
        }
        return in_array($col, self::$facturaItemsCols, true);
    }

    /** Expresión del costo unitario: snapshot al facturar o precomp actual. */
    private function costoUnitExpr(): string
    {
        if ($this->facturaItemsTieneColumna('costo_cents')) {
            return 'COALESCE(fi.costo_cents, ROUND(p.precomp * 100))';
        }
        return 'ROUND(p.precomp * 100)';
    }

    /**
     * Ganancia neta: neto vendido sin IVA menos descuentos y costo.
     * Costo = snapshot costo_cents al facturar, o precomp actual si no hay.
     */
    public function ganancia(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $row = ['neto_cents' => 0, 'costo_cents' => 0];
        try {
            $costo = $this->costoUnitExpr();
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(SUM(fi.total_cents - fi.iva_cents), 0) AS neto_cents,
                    COALESCE(SUM({$costo} * fi.qty), 0) AS costo_cents
                FROM factura_items fi
                INNER JOIN facturas f ON f.id = fi.factura_id
                LEFT JOIN producto p ON p.idprodu = fi.idprodu
                WHERE f.estado = 'emitida'
                  AND f.fecha BETWEEN :desde AND :hasta
                  $pvWhere
            ");
            $st->execute($params);
            $row = $st->fetch() ?: $row;
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ganancia error: ' . $e->getMessage());
        }

        $descuento = 0;
        try {
            $descCols = [];
            if ($this->facturasTieneColumna('descuento_cents')) {
                $descCols[] = 'COALESCE(SUM(f.descuento_cents), 0)';
            }
            if ($this->facturasTieneColumna('puntos_cents')) {
                $descCols[] = 'COALESCE(SUM(f.puntos_cents), 0)';
            }
            if ($descCols) {
                $std = Db::pdo()->prepare('
                    SELECT ' . implode(' + ', $descCols) . ' AS descuento_cents
                    FROM facturas f
                    WHERE f.estado = \'emitida\'
                      AND f.fecha BETWEEN :desde AND :hasta
                      ' . ($puntoVenta > 0 ? ' AND f.punto_venta = :pv' : '') . '
                ');
                $std->execute($params);
                $descuento = (int)($std->fetchColumn() ?: 0);
            }
        } catch (\Throwable $e) {
            $descuento = 0;
        }

        $neto = (int)($row['neto_cents'] ?? 0) - $descuento;
        $costo = (int)($row['costo_cents'] ?? 0);
        return [
            'neto_cents' => $neto,
            'costo_cents' => $costo,
            'descuento_cents' => $descuento,
            'ganancia_cents' => $neto - $costo,
        ];
    }

    /** Top productos ordenados por ganancia bruta (sin prorratear descuentos de factura). */
    public function topGanancia(string $desde, string $hasta, int $limite = 15, int $puntoVenta = 0): array
    {
        $limite = max(1, min(50, $limite));
        $params = [':desde' => $desde, ':hasta' => $hasta, ':lim' => $limite];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        try {
            $costo = $this->costoUnitExpr();
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(NULLIF(fi.producto, ''), '(sin nombre)') AS producto,
                    fi.variedad,
                    SUM(fi.qty) AS qty_total,
                    SUM(fi.total_cents - fi.iva_cents) AS neto_cents,
                    SUM({$costo} * fi.qty) AS costo_cents,
                    SUM(fi.total_cents - fi.iva_cents) - SUM({$costo} * fi.qty) AS ganancia_cents
                FROM factura_items fi
                INNER JOIN facturas f ON f.id = fi.factura_id
                LEFT JOIN producto p ON p.idprodu = fi.idprodu
                WHERE f.estado = 'emitida'
                  AND f.fecha BETWEEN :desde AND :hasta
                  $pvWhere
                GROUP BY fi.producto, fi.variedad
                ORDER BY ganancia_cents DESC
                LIMIT :lim
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('ReporteRepo::topGanancia error: ' . $e->getMessage());
            return [];
        }
    }

    /** Margen por departamento (ganancia bruta, sin prorratear descuentos). */
    public function margenPorDepartamento(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        try {
            $costo = $this->costoUnitExpr();
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(NULLIF(d.nomdepar, ''), 'Sin dep.') AS departamento,
                    SUM(fi.qty) AS qty_total,
                    SUM(fi.total_cents - fi.iva_cents) AS neto_cents,
                    SUM({$costo} * fi.qty) AS costo_cents,
                    SUM(fi.total_cents - fi.iva_cents) - SUM({$costo} * fi.qty) AS ganancia_cents
                FROM factura_items fi
                INNER JOIN facturas f ON f.id = fi.factura_id
                LEFT JOIN producto p ON p.idprodu = fi.idprodu
                LEFT JOIN departa d ON d.codepar = p.codepar
                WHERE f.estado = 'emitida'
                  AND f.fecha BETWEEN :desde AND :hasta
                  $pvWhere
                GROUP BY d.codepar
                ORDER BY ganancia_cents DESC
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('ReporteRepo::margenPorDepartamento error: ' . $e->getMessage());
            return [];
        }
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
