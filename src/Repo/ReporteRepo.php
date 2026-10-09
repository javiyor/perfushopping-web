<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class ReporteRepo
{
    /** Puntos de venta con facturación (0 = todos). Para el filtro de reportes. */
    public function puntosVentaDisponibles(): array
    {
        try {
            $aut = $this->soloAutorizadasWhere();
            $st = Db::pdo()->query("
                SELECT f.punto_venta,
                       COALESCE(s.nomsuc, spv_suc.nomsuc, CONCAT('PV ', f.punto_venta)) AS nombre,
                       COUNT(*) AS comprobantes
                FROM facturas f
                LEFT JOIN admin_sucursales s ON s.id = f.sucursal_id
                LEFT JOIN admin_sucursal_puntos_venta spv ON spv.punto_venta = f.punto_venta
                LEFT JOIN admin_sucursales spv_suc ON spv_suc.id = spv.sucursal_id
                WHERE f.estado = 'emitida'{$aut}
                GROUP BY f.punto_venta
                ORDER BY f.punto_venta ASC
            ");
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('ReporteRepo::puntosVentaDisponibles error: ' . $e->getMessage());
            return [];
        }
    }

    public function resumenVentas(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $aut = $this->soloAutorizadasWhere();
        $total = $this->montoSignado('f.total_cents');
        $iva = $this->montoSignado('f.iva_cents');
        $subtotal = $this->montoSignado('f.subtotal_cents');
        $st = Db::pdo()->prepare("
            SELECT
                COUNT(*) AS cantidad,
                COALESCE(SUM({$total}), 0) AS total_cents,
                COALESCE(SUM({$iva}), 0) AS iva_cents,
                COALESCE(SUM({$subtotal}), 0) AS subtotal_cents
            FROM facturas f
            WHERE f.estado = 'emitida'{$aut}
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
        $aut = $this->soloAutorizadasWhere();
        $total = $this->montoSignado('f.total_cents');
        $st = Db::pdo()->prepare("
            SELECT f.fecha, COUNT(*) AS cantidad, COALESCE(SUM({$total}), 0) AS total_cents
            FROM facturas f
            WHERE f.estado = 'emitida'{$aut}
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
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $aut = $this->soloAutorizadasWhere();
        $qty = $this->montoSignado('fi.qty');
        $total = $this->montoSignado('fi.total_cents');
        $st = Db::pdo()->prepare("
            SELECT
                COALESCE(NULLIF(fi.producto, ''), '(sin nombre)') AS producto,
                fi.variedad,
                SUM({$qty}) AS qty_total,
                SUM({$total}) AS total_cents,
                COUNT(DISTINCT f.id) AS facturas
            FROM factura_items fi
            INNER JOIN facturas f ON f.id = fi.factura_id
            WHERE f.estado = 'emitida'{$aut}
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY fi.producto, fi.variedad
            ORDER BY qty_total DESC
            LIMIT " . $limite . "
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
        $aut = $this->soloAutorizadasWhere();
        $qty = $this->montoSignado('fi.qty');
        $total = $this->montoSignado('fi.total_cents');
        $st = Db::pdo()->prepare("
            SELECT
                COALESCE(NULLIF(d.nomdepar, ''), 'Sin dep.') AS departamento,
                d.codactiv,
                SUM({$qty}) AS qty_total,
                SUM({$total}) AS total_cents
            FROM factura_items fi
            INNER JOIN facturas f ON f.id = fi.factura_id
            LEFT JOIN producto p ON p.idprodu = fi.idprodu
            LEFT JOIN departa d ON d.codepar = p.codepar
            WHERE f.estado = 'emitida'{$aut}
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY d.codepar, d.codactiv
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
        $aut = $this->soloAutorizadasWhere();
        $monto = $this->montoSignado('fp.monto_cents');
        $st = Db::pdo()->prepare("
            SELECT fp.forma_pago, SUM({$monto}) AS total_cents, COUNT(DISTINCT fp.factura_id) AS cantidad
            FROM factura_pagos fp
            INNER JOIN facturas f ON f.id = fp.factura_id
            WHERE f.estado = 'emitida'{$aut}
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY fp.forma_pago
            ORDER BY total_cents DESC
        ");
        $st->execute($params);
        return $st->fetchAll();
    }

    private static ?bool $pagosTieneEquipo = null;

    private function pagosTieneEquipo(): bool
    {
        if (self::$pagosTieneEquipo !== null) {
            return self::$pagosTieneEquipo;
        }
        try {
            $cols = array_column(Db::pdo()->query('SHOW COLUMNS FROM factura_pagos')->fetchAll(), 'Field');
            self::$pagosTieneEquipo = in_array('equipo_id', $cols, true);
        } catch (\Throwable $e) {
            self::$pagosTieneEquipo = false;
        }
        return self::$pagosTieneEquipo;
    }

    /** Pagos con tarjeta agrupados por equipo POS. */
    public function ventasTarjetasPorEquipo(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        if (!$this->pagosTieneEquipo()) {
            return [];
        }
        try {
            $params = [':desde' => $desde, ':hasta' => $hasta];
            $pvWhere = '';
            if ($puntoVenta > 0) {
                $pvWhere = ' AND f.punto_venta = :pv';
                $params[':pv'] = $puntoVenta;
            }
            $legacy = "CASE fp.forma_pago WHEN 'tarjeta' THEN 'tarjeta' WHEN 'tarjeta_credito' THEN 'tarjeta' WHEN 'tarjeta_debito' THEN 'tarjeta' WHEN 'tarjetas' THEN 'tarjeta' ELSE 'otro' END";
            try {
                \Perfushopping\Web\Repo\FormaPagoRepo::ensureTable();
                $tipoExpr = 'COALESCE((SELECT fpm.tipo FROM formas_pago fpm WHERE fpm.codigo = fp.forma_pago COLLATE utf8mb4_unicode_ci LIMIT 1), ' . $legacy . ')';
            } catch (\Throwable $e) {
                $tipoExpr = $legacy;
            }
            $st = Db::pdo()->prepare("
                SELECT COALESCE(NULLIF(TRIM(e.empresa), ''), CONCAT('Equipo ', fp.equipo_id), 'Sin equipo') AS equipo,
                       COUNT(*) AS pagos,
                       COALESCE(SUM({$this->montoSignado('fp.monto_cents')}), 0) AS total_cents
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                LEFT JOIN equipotar e ON e.idequipo = fp.equipo_id
                WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
                  AND f.fecha BETWEEN :desde AND :hasta
                  AND {$tipoExpr} = 'tarjeta'
                  {$pvWhere}
                GROUP BY equipo
                ORDER BY total_cents DESC
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ventasTarjetasPorEquipo error: ' . $e->getMessage());
            return [];
        }
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

    /** Ventas agrupadas por sucursal con métricas completas (comprobantes, neto, costo, ganancia, margen, ticket, gastos). */
    public function ventasPorSucursal(string $desde, string $hasta): array
    {
        $byKey = [];
        try {
            $descExpr = $this->facturasTieneColumna('descuento_cents') ? 'COALESCE(SUM(' . $this->montoSignado('f.descuento_cents') . '), 0)' : '0';
            $puntosExpr = $this->facturasTieneColumna('puntos_cents') ? 'COALESCE(SUM(' . $this->montoSignado('f.puntos_cents') . '), 0)' : '0';
            $total = $this->montoSignado('f.total_cents');
            $subtotal = $this->montoSignado('f.subtotal_cents');
            $iva = $this->montoSignado('f.iva_cents');
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(s.id, CONCAT('pv-', f.punto_venta)) AS sucursal_key,
                    COALESCE(s.nomsuc, spv_suc.nomsuc, CONCAT('PV ', f.punto_venta)) AS sucursal,
                    COUNT(*) AS cantidad,
                    COALESCE(SUM({$total}), 0) AS total_cents,
                    COALESCE(SUM({$subtotal}), 0) AS subtotal_cents,
                    COALESCE(SUM({$iva}), 0) AS iva_cents,
                    {$descExpr} AS descuento_cents,
                    {$puntosExpr} AS puntos_cents
                FROM facturas f
                LEFT JOIN admin_sucursales s ON s.id = f.sucursal_id
                LEFT JOIN admin_sucursal_puntos_venta spv ON spv.punto_venta = f.punto_venta
                LEFT JOIN admin_sucursales spv_suc ON spv_suc.id = spv.sucursal_id
                WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
                  AND f.fecha BETWEEN :desde AND :hasta
                GROUP BY sucursal_key
            ");
            $st->execute([':desde' => $desde, ':hasta' => $hasta]);
            foreach ($st->fetchAll() as $r) {
                $key = (string)$r['sucursal_key'];
                $byKey[$key] = [
                    'sucursal_key' => $r['sucursal_key'],
                    'sucursal' => (string)$r['sucursal'],
                    'cantidad' => (int)$r['cantidad'],
                    'total_cents' => (int)$r['total_cents'],
                    'subtotal_cents' => (int)$r['subtotal_cents'],
                    'iva_cents' => (int)$r['iva_cents'],
                    'descuento_cents' => (int)$r['descuento_cents'],
                    'puntos_cents' => (int)$r['puntos_cents'],
                    'costo_cents' => 0,
                    'gastos_cents' => 0,
                ];
            }
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ventasPorSucursal error: ' . $e->getMessage());
            return [];
        }

        try {
            $costo = $this->costoUnitExpr();
            $costoNc = "CASE WHEN f.tipo_comprobante = 'NC' THEN -({$costo} * fi.qty) ELSE ({$costo} * fi.qty) END";
            $st2 = Db::pdo()->prepare("
                SELECT
                    COALESCE(s.id, CONCAT('pv-', f.punto_venta)) AS sucursal_key,
                    COALESCE(SUM({$costoNc}), 0) AS costo_cents
                FROM factura_items fi
                INNER JOIN facturas f ON f.id = fi.factura_id
                LEFT JOIN producto p ON p.idprodu = fi.idprodu
                LEFT JOIN admin_sucursales s ON s.id = f.sucursal_id
                LEFT JOIN admin_sucursal_puntos_venta spv ON spv.punto_venta = f.punto_venta
                LEFT JOIN admin_sucursales spv_suc ON spv_suc.id = spv.sucursal_id
                WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
                  AND f.fecha BETWEEN :desde AND :hasta
                GROUP BY sucursal_key
            ");
            $st2->execute([':desde' => $desde, ':hasta' => $hasta]);
            foreach ($st2->fetchAll() as $r) {
                $key = (string)$r['sucursal_key'];
                if (isset($byKey[$key])) {
                    $byKey[$key]['costo_cents'] = (int)$r['costo_cents'];
                }
            }
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ventasPorSucursal costo error: ' . $e->getMessage());
        }

        try {
            $st3 = Db::pdo()->prepare("
                SELECT
                    COALESCE(s.id, CONCAT('pv-', g.punto_venta)) AS sucursal_key,
                    COALESCE(s.nomsuc, spv_suc.nomsuc, CONCAT('PV ', g.punto_venta)) AS sucursal,
                    COALESCE(SUM(g.importe_cents), 0) AS gastos_cents
                FROM gastos g
                LEFT JOIN admin_sucursales s ON s.id = g.sucursal_id
                LEFT JOIN admin_sucursal_puntos_venta spv ON spv.punto_venta = g.punto_venta
                LEFT JOIN admin_sucursales spv_suc ON spv_suc.id = spv.sucursal_id
                WHERE g.fecha BETWEEN :desde AND :hasta
                  AND (g.sucursal_id IS NOT NULL OR g.punto_venta IS NOT NULL)
                GROUP BY sucursal_key
            ");
            $st3->execute([':desde' => $desde, ':hasta' => $hasta]);
            foreach ($st3->fetchAll() as $r) {
                $key = (string)$r['sucursal_key'];
                if (!isset($byKey[$key])) {
                    $byKey[$key] = [
                        'sucursal_key' => $r['sucursal_key'],
                        'sucursal' => (string)$r['sucursal'],
                        'cantidad' => 0,
                        'total_cents' => 0,
                        'subtotal_cents' => 0,
                        'iva_cents' => 0,
                        'descuento_cents' => 0,
                        'puntos_cents' => 0,
                        'costo_cents' => 0,
                        'gastos_cents' => 0,
                    ];
                }
                $byKey[$key]['gastos_cents'] = (int)$r['gastos_cents'];
            }
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ventasPorSucursal gastos error: ' . $e->getMessage());
        }

        foreach ($byKey as &$row) {
            $neto = $row['subtotal_cents'] - $row['descuento_cents'] - $row['puntos_cents'];
            $row['neto_cents'] = $neto;
            $row['ganancia_cents'] = $neto - $row['costo_cents'];
            $row['ticket_promedio_cents'] = $row['cantidad'] > 0 ? (int)round($row['total_cents'] / $row['cantidad']) : 0;
            $row['margen_pct'] = $neto > 0 ? round(($row['ganancia_cents'] / $neto) * 100, 1) : null;
        }
        unset($row);

        $rows = array_values($byKey);
        usort($rows, static fn (array $a, array $b): int => $b['total_cents'] <=> $a['total_cents']);
        return $rows;
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
                       COALESCE(SUM({$this->montoSignado('f.total_cents')}), 0) AS total_cents
                FROM facturas f
                WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
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
     * Condición de comprobante autorizado por ARCA (con CAE). Si la columna
     * no existe, todo cuenta como autorizado (compatibilidad).
     */
    private function esAutorizadaExpr(string $fAlias = 'f'): string
    {
        if (!$this->facturasTieneColumna('cae')) {
            return '(1 = 1)';
        }
        return "({$fAlias}.cae IS NOT NULL AND {$fAlias}.cae <> '' AND {$fAlias}.cae <> 'NULL')";
    }

    /**
     * Filtro de comprobantes autorizados por ARCA (con CAE). Estricto: sin
     * CAE el comprobante no suma en ningún total del reporte.
     */
    private function soloAutorizadasWhere(string $fAlias = 'f'): string
    {
        if (!$this->facturasTieneColumna('cae')) {
            return '';
        }
        return ' AND ' . $this->esAutorizadaExpr($fAlias);
    }

    /**
     * Importe con signo para sumas del reporte: las Notas de Crédito
     * restan; facturas y Notas de Débito suman.
     */
    private function montoSignado(string $montoExpr, string $fAlias = 'f'): string
    {
        return "CASE WHEN {$fAlias}.tipo_comprobante = 'NC' THEN -({$montoExpr}) ELSE ({$montoExpr}) END";
    }

    /**
     * Ganancia neta: neto vendido sin IVA menos descuentos y costo.
     * Costo = snapshot costo_cents al facturar, o precomp actual si no hay.
     * Base = total vendido (autorizadas y no autorizadas), igual que el KPI total.
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
            $netoItem = $this->montoSignado('fi.total_cents - fi.iva_cents');
            $costoItem = "CASE WHEN f.tipo_comprobante = 'NC' THEN -({$costo} * fi.qty) ELSE ({$costo} * fi.qty) END";
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(SUM({$netoItem}), 0) AS neto_cents,
                    COALESCE(SUM({$costoItem}), 0) AS costo_cents
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
                $descCols[] = 'COALESCE(SUM(' . $this->montoSignado('f.descuento_cents') . '), 0)';
            }
            if ($this->facturasTieneColumna('puntos_cents')) {
                $descCols[] = 'COALESCE(SUM(' . $this->montoSignado('f.puntos_cents') . '), 0)';
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
        $params = [':desde' => $desde, ':hasta' => $hasta];
        $pvWhere = '';
        if ($puntoVenta > 0) {
            $pvWhere = ' AND f.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        try {
            $costo = $this->costoUnitExpr();
            $netoItem = $this->montoSignado('fi.total_cents - fi.iva_cents');
            $costoItem = "CASE WHEN f.tipo_comprobante = 'NC' THEN -({$costo} * fi.qty) ELSE ({$costo} * fi.qty) END";
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(NULLIF(fi.producto, ''), '(sin nombre)') AS producto,
                    fi.variedad,
                    SUM({$this->montoSignado('fi.qty')}) AS qty_total,
                    SUM({$netoItem}) AS neto_cents,
                    SUM({$costoItem}) AS costo_cents,
                    SUM({$netoItem}) - SUM({$costoItem}) AS ganancia_cents
                FROM factura_items fi
                INNER JOIN facturas f ON f.id = fi.factura_id
                LEFT JOIN producto p ON p.idprodu = fi.idprodu
                WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
                  AND f.fecha BETWEEN :desde AND :hasta
                  $pvWhere
                GROUP BY fi.producto, fi.variedad
                ORDER BY ganancia_cents DESC
                LIMIT " . $limite . "
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
            $netoItem = $this->montoSignado('fi.total_cents - fi.iva_cents');
            $costoItem = "CASE WHEN f.tipo_comprobante = 'NC' THEN -({$costo} * fi.qty) ELSE ({$costo} * fi.qty) END";
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(NULLIF(d.nomdepar, ''), 'Sin dep.') AS departamento,
                    SUM({$this->montoSignado('fi.qty')}) AS qty_total,
                    SUM({$netoItem}) AS neto_cents,
                    SUM({$costoItem}) AS costo_cents,
                    SUM({$netoItem}) - SUM({$costoItem}) AS ganancia_cents
                FROM factura_items fi
                INNER JOIN facturas f ON f.id = fi.factura_id
                LEFT JOIN producto p ON p.idprodu = fi.idprodu
                LEFT JOIN departa d ON d.codepar = p.codepar
                WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
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
            SELECT f.tipo_comprobante, COUNT(*) AS cantidad, COALESCE(SUM({$this->montoSignado('f.total_cents')}), 0) AS total_cents
            FROM facturas f
            WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
              AND f.fecha BETWEEN :desde AND :hasta
              $pvWhere
            GROUP BY f.tipo_comprobante
            ORDER BY total_cents DESC
        ");
        $st->execute($params);
        return $st->fetchAll();
    }

    /**
     * Ventas totales discriminadas por autorización ARCA: autorizadas (con
     * CAE) vs no autorizadas (emitidas sin CAE: pendientes o rechazadas).
     * Importes con signo (NC resta), igual que el resto del reporte.
     */
    public function resumenAutorizacion(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        $out = [
            'autorizadas' => ['cantidad' => 0, 'total_cents' => 0, 'iva_cents' => 0],
            'no_autorizadas' => ['cantidad' => 0, 'total_cents' => 0, 'iva_cents' => 0],
        ];
        try {
            $params = [':desde' => $desde, ':hasta' => $hasta];
            $pvWhere = '';
            if ($puntoVenta > 0) {
                $pvWhere = ' AND f.punto_venta = :pv';
                $params[':pv'] = $puntoVenta;
            }
            $esAut = $this->esAutorizadaExpr();
            $total = $this->montoSignado('f.total_cents');
            $iva = $this->montoSignado('f.iva_cents');
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(SUM(CASE WHEN {$esAut} THEN 1 ELSE 0 END), 0) AS aut_cantidad,
                    COALESCE(SUM(CASE WHEN {$esAut} THEN {$total} ELSE 0 END), 0) AS aut_total,
                    COALESCE(SUM(CASE WHEN {$esAut} THEN {$iva} ELSE 0 END), 0) AS aut_iva,
                    COALESCE(SUM(CASE WHEN NOT ({$esAut}) THEN 1 ELSE 0 END), 0) AS noaut_cantidad,
                    COALESCE(SUM(CASE WHEN NOT ({$esAut}) THEN {$total} ELSE 0 END), 0) AS noaut_total,
                    COALESCE(SUM(CASE WHEN NOT ({$esAut}) THEN {$iva} ELSE 0 END), 0) AS noaut_iva
                FROM facturas f
                WHERE f.estado = 'emitida'
                  AND f.fecha BETWEEN :desde AND :hasta
                  $pvWhere
            ");
            $st->execute($params);
            $r = $st->fetch() ?: [];
            $out['autorizadas'] = [
                'cantidad' => (int)($r['aut_cantidad'] ?? 0),
                'total_cents' => (int)($r['aut_total'] ?? 0),
                'iva_cents' => (int)($r['aut_iva'] ?? 0),
            ];
            $out['no_autorizadas'] = [
                'cantidad' => (int)($r['noaut_cantidad'] ?? 0),
                'total_cents' => (int)($r['noaut_total'] ?? 0),
                'iva_cents' => (int)($r['noaut_iva'] ?? 0),
            ];
        } catch (\Throwable $e) {
            error_log('ReporteRepo::resumenAutorizacion error: ' . $e->getMessage());
        }
        return $out;
    }

    /**
     * Ventas por vendedor (admin_users.nombre vía facturas.vendedor_id).
     * Importes con signo (NC resta), solo comprobantes autorizados.
     */
    public function ventasPorVendedor(string $desde, string $hasta, int $puntoVenta = 0): array
    {
        try {
            $params = [':desde' => $desde, ':hasta' => $hasta];
            $pvWhere = '';
            if ($puntoVenta > 0) {
                $pvWhere = ' AND f.punto_venta = :pv';
                $params[':pv'] = $puntoVenta;
            }
            $total = $this->montoSignado('f.total_cents');
            $st = Db::pdo()->prepare("
                SELECT
                    COALESCE(NULLIF(TRIM(v.nombre), ''), 'Sin vendedor') AS vendedor,
                    COUNT(*) AS cantidad,
                    COALESCE(SUM({$total}), 0) AS total_cents
                FROM facturas f
                LEFT JOIN admin_users v ON v.id = f.vendedor_id
                WHERE f.estado = 'emitida'{$this->soloAutorizadasWhere()}
                  AND f.fecha BETWEEN :desde AND :hasta
                  $pvWhere
                GROUP BY vendedor
                ORDER BY total_cents DESC
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('ReporteRepo::ventasPorVendedor error: ' . $e->getMessage());
            return [];
        }
    }
}
