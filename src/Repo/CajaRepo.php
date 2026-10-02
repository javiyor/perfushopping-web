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

    public function cerrar(int $id, int $montoCierreCents, int $cerradaPor, int $montoRetiradoCents = 0, int $proximaAperturaCents = 0): void
    {
        $this->ensureCajaColumnas();
        $retSql = '';
        $params = [':mon' => $montoCierreCents, ':cp' => $cerradaPor, ':i' => $id];
        if ($this->aperturasTieneMontoRetirado()) {
            $retSql = ', monto_retirado_cents = :ret';
            $params[':ret'] = $montoRetiradoCents;
        }
        try {
            $st = Db::pdo()->prepare("
                UPDATE caja_aperturas SET estado = 'cerrada', monto_cierre_cents = :mon{$retSql}, monto_proxima_apertura_cents = :prox, cerrada_por = :cp, updated_at = NOW()
                WHERE id = :i LIMIT 1
            ");
            $params[':prox'] = $proximaAperturaCents;
            $st->execute($params);
        } catch (\Throwable $e) {
            unset($params[':prox']);
            $st = Db::pdo()->prepare("
                UPDATE caja_aperturas SET estado = 'cerrada', monto_cierre_cents = :mon{$retSql}, cerrada_por = :cp, updated_at = NOW()
                WHERE id = :i LIMIT 1
            ");
            $st->execute($params);
        }
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

    private function facturasTieneEntrega(): bool
    {
        static $has = null;
        if ($has !== null) return $has;
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
            $fields = array_column($cols, 'Field');
            // El filtro usa ambas columnas; si falta alguna, no se aplica.
            $has = in_array('entrega_tipo', $fields, true) && in_array('envio_estado', $fields, true);
        } catch (\Throwable $e) { $has = false; }
        return $has;
    }

    private function efectivoNoCajaWhere(): string
    {
        if (!$this->facturasTieneEntrega()) return '';
        // efectivo contra entrega pendiente NO cuenta en caja
        return " AND NOT (f.entrega_tipo='envio' AND f.envio_estado IN ('pendiente','en_transito') AND fp.forma_pago='efectivo') ";
    }

    /**
     * Tipo de comportamiento de fp.forma_pago (fp = alias de factura_pagos).
     * Usa la tabla formas_pago y cae a los códigos históricos si no existe.
     */
    private function formaPagoTipoSql(string $fpAlias = 'fp'): string
    {
        \Perfushopping\Web\Repo\FormaPagoRepo::ensureTable();
        // COLLATE explícito: formas_pago puede estar en uca1400 y factura_pagos en unicode_ci.
        return "(SELECT fpm.tipo FROM formas_pago fpm WHERE fpm.codigo = {$fpAlias}.forma_pago COLLATE utf8mb4_unicode_ci LIMIT 1)";
    }

    private function formaPagoTipoLegacySql(string $fpAlias = 'fp'): string
    {
        return "CASE {$fpAlias}.forma_pago
            WHEN 'efectivo' THEN 'efectivo'
            WHEN 'transferencia' THEN 'banco' WHEN 'mercadopago' THEN 'banco'
            WHEN 'debito' THEN 'banco' WHEN 'credito' THEN 'banco'
            WHEN 'tarjeta' THEN 'tarjeta' WHEN 'tarjeta_credito' THEN 'tarjeta'
            WHEN 'tarjeta_debito' THEN 'tarjeta' WHEN 'tarjetas' THEN 'tarjeta'
            WHEN 'cheque' THEN 'cheque' WHEN 'cuenta_corriente' THEN 'ctacte'
            ELSE 'otro' END";
    }

    private static ?bool $formasPagoTableExists = null;

    private function tieneTablaFormasPago(): bool
    {
        if (self::$formasPagoTableExists !== null) {
            return self::$formasPagoTableExists;
        }
        self::$formasPagoTableExists = false;
        try {
            \Perfushopping\Web\Repo\FormaPagoRepo::ensureTable();
            Db::pdo()->query('SELECT 1 FROM formas_pago LIMIT 1');
            self::$formasPagoTableExists = true;
        } catch (\Throwable $e) {
            error_log('CajaRepo::tieneTablaFormasPago: ' . $e->getMessage());
        }
        return self::$formasPagoTableExists;
    }

    private function formaPagoTipoExpr(string $fpAlias = 'fp'): string
    {
        if ($this->tieneTablaFormasPago()) {
            return 'COALESCE(' . $this->formaPagoTipoSql($fpAlias) . ', ' . $this->formaPagoTipoLegacySql($fpAlias) . ')';
        }
        return $this->formaPagoTipoLegacySql($fpAlias);
    }

    public function totalVentasEfectivo(string $fecha, int $puntoVenta): int
    {
        try {
            $extra = $this->efectivoNoCajaWhere();
            $tipo = $this->formaPagoTipoExpr('fp');
            $st = Db::pdo()->prepare("
                SELECT COALESCE(SUM(fp.monto_cents), 0)
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                WHERE f.estado = 'emitida'
                  AND f.fecha = :fec
                  AND f.punto_venta = :pv
                  AND {$tipo} = 'efectivo'
                  {$extra}
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            error_log('CajaRepo::totalVentasEfectivo error: '.$e->getMessage());
            return 0;
        }
    }

    public function totalVentasTransferencia(string $fecha, int $puntoVenta): int
    {
        try {
            $tipo = $this->formaPagoTipoExpr('fp');
            $st = Db::pdo()->prepare("
                SELECT COALESCE(SUM(fp.monto_cents), 0)
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                WHERE f.estado = 'emitida'
                  AND f.fecha = :fec
                  AND f.punto_venta = :pv
                  AND {$tipo} = 'banco'
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            error_log('CajaRepo::totalVentasTransferencia error: '.$e->getMessage());
            return 0;
        }
    }

    public function totalRecibos(string $fecha, int $puntoVenta): int
    {
        $pvWhere = '';
        $params = [':fec' => $fecha];
        if ($this->recibosTienePuntoVenta()) {
            $pvWhere = ' AND r.punto_venta = :pv';
            $params[':pv'] = $puntoVenta;
        }
        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(r.monto_cents), 0)
            FROM recibos r
            WHERE r.estado = 'emitido'
              AND r.fecha = :fec
              $pvWhere
        ");
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /** Pagos de facturas emitidas del día/punto de venta, con forma de pago (para el turno). */
    public function ventasDetalle(string $fecha, int $puntoVenta): array
    {
        try {
            $extra = $this->efectivoNoCajaWhere();
            $st = Db::pdo()->prepare("
                SELECT fp.id, f.codigo, f.cliente_nombre, f.created_at,
                       fp.forma_pago, fp.monto_cents
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                WHERE f.estado = 'emitida'
                  AND f.fecha = :fec
                  AND f.punto_venta = :pv
                  {$extra}
                ORDER BY f.created_at ASC, f.id ASC, fp.id ASC
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CajaRepo::ventasDetalle error: '.$e->getMessage());
            return [];
        }
    }

    /** Recibos emitidos del día/punto de venta (para el turno). */
    public function recibosDetalle(string $fecha, int $puntoVenta): array
    {
        try {
            $pvWhere = '';
            $params = [':fec' => $fecha];
            if ($this->recibosTienePuntoVenta()) {
                $pvWhere = ' AND r.punto_venta = :pv';
                $params[':pv'] = $puntoVenta;
            }
            $st = Db::pdo()->prepare("
                SELECT r.id, r.codigo, r.cliente_nombre, r.created_at,
                       r.forma_pago, r.monto_cents
                FROM recibos r
                WHERE r.estado = 'emitido'
                  AND r.fecha = :fec
                  $pvWhere
                ORDER BY r.created_at ASC, r.id ASC
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CajaRepo::recibosDetalle error: '.$e->getMessage());
            return [];
        }
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

    private static ?array $aperturasColumns = null;

    private function aperturasTieneMontoRetirado(): bool
    {
        if (self::$aperturasColumns === null) {
            try {
                $st = Db::pdo()->query('SHOW COLUMNS FROM caja_aperturas');
                self::$aperturasColumns = array_column($st->fetchAll(), 'Field');
            } catch (\Throwable $e) {
                self::$aperturasColumns = [];
            }
        }
        return in_array('monto_retirado_cents', self::$aperturasColumns, true);
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

    // ── Caja general ──

    public function ventasPorPuntoVenta(string $fecha): array
    {
        try {
            $extraEfectivo = $this->facturasTieneEntrega() ? " AND NOT (f.entrega_tipo='envio' AND f.envio_estado IN ('pendiente','en_transito') AND fp.forma_pago='efectivo')" : "";
            $tipo = $this->formaPagoTipoExpr('fp');
            // For total_efectivo we exclude pendiente efectivo envios; others count normally
            $st = Db::pdo()->prepare("
                SELECT f.punto_venta, COALESCE(s.nomsuc, CONCAT('Punto ', f.punto_venta)) AS sucursal_nombre,
                       COALESCE(SUM(CASE WHEN {$tipo} = 'efectivo' {$extraEfectivo} THEN fp.monto_cents ELSE 0 END), 0) AS total_efectivo,
                       COALESCE(SUM(CASE WHEN {$tipo} = 'banco' THEN fp.monto_cents ELSE 0 END), 0) AS total_transferencia,
                       COALESCE(SUM(CASE WHEN {$tipo} = 'efectivo' {$extraEfectivo} THEN fp.monto_cents WHEN {$tipo} = 'banco' THEN fp.monto_cents ELSE 0 END), 0) AS total
                FROM facturas f
                INNER JOIN factura_pagos fp ON fp.factura_id = f.id
                LEFT JOIN sucursales s ON s.id = f.punto_venta
                WHERE f.estado = 'emitida' AND f.fecha = :fec
                GROUP BY f.punto_venta
            ");
            $st->execute([':fec' => $fecha]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CajaRepo::ventasPorPuntoVenta error: ' . $e->getMessage());
            return [];
        }
    }

    public function agregarMovimientoGeneral(string $tipo, ?string $origen, ?int $origenId, string $concepto, int $montoCents, int $createdBy): int
    {
        try {
            $st = Db::pdo()->prepare('
                INSERT INTO caja_general_movimientos (tipo, origen, origen_id, concepto, monto_cents, created_by, created_at)
                VALUES (:tip, :ori, :oid, :con, :mon, :cb, NOW())
            ');
            $st->execute([
                ':tip' => $tipo,
                ':ori' => $origen,
                ':oid' => $origenId,
                ':con' => $concepto,
                ':mon' => $montoCents,
                ':cb' => $createdBy,
            ]);
            return (int)Db::pdo()->lastInsertId();
        } catch (\Throwable $e) {
            error_log('CajaRepo::agregarMovimientoGeneral error: ' . $e->getMessage());
            return 0;
        }
    }

    public function movimientosGenerales(?string $tipo = null, ?string $desde = null, ?string $hasta = null, string $q = ''): array
    {
        try {
            $sql = '
                SELECT cg.*, a.nombre AS created_by_nombre, c.nombre AS controlado_por_nombre
                FROM caja_general_movimientos cg
                LEFT JOIN admin_users a ON a.id = cg.created_by
                LEFT JOIN admin_users c ON c.id = cg.controlado_por
                WHERE 1=1
            ';
            $params = [];
            if ($tipo !== null && $tipo !== '') {
                $sql .= ' AND cg.tipo = :tip';
                $params[':tip'] = $tipo;
            }
            if ($desde !== null && $desde !== '') {
                $sql .= ' AND DATE(cg.created_at) >= :desde';
                $params[':desde'] = $desde;
            }
            if ($hasta !== null && $hasta !== '') {
                $sql .= ' AND DATE(cg.created_at) <= :hasta';
                $params[':hasta'] = $hasta;
            }
            $q = trim($q);
            if ($q !== '') {
                $sql .= ' AND (cg.concepto LIKE :q1 OR cg.origen LIKE :q2)';
                $params[':q1'] = '%' . $q . '%';
                $params[':q2'] = '%' . $q . '%';
            }
            $sql .= ' ORDER BY cg.created_at DESC LIMIT 200';

            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CajaRepo::movimientosGenerales error: ' . $e->getMessage());
            return [];
        }
    }

    public function totalMovimientosGenerales(?string $desde = null, ?string $hasta = null): array
    {
        try {
            $sql = "
                SELECT
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto_cents ELSE 0 END), 0) AS total_ingresos,
                    COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto_cents ELSE 0 END), 0) AS total_egresos
                FROM caja_general_movimientos
                WHERE 1=1
            ";
            $params = [];
            if ($desde !== null && $desde !== '') {
                $sql .= ' AND DATE(created_at) >= :desde';
                $params[':desde'] = $desde;
            }
            if ($hasta !== null && $hasta !== '') {
                $sql .= ' AND DATE(created_at) <= :hasta';
                $params[':hasta'] = $hasta;
            }
            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
            return $st->fetch() ?: ['total_ingresos' => 0, 'total_egresos' => 0];
        } catch (\Throwable $e) {
            error_log('CajaRepo::totalMovimientosGenerales error: ' . $e->getMessage());
            return ['total_ingresos' => 0, 'total_egresos' => 0];
        }
    }

    public function controlarMovimientoGeneral(int $id, int $adminUserId): void
    {
        try {
            $st = Db::pdo()->prepare('
                UPDATE caja_general_movimientos
                SET controlado = 1, controlado_por = :cp, controlado_at = NOW()
                WHERE id = :i LIMIT 1
            ');
            $st->execute([':cp' => $adminUserId, ':i' => $id]);
        } catch (\Throwable $e) {
            error_log('CajaRepo::controlarMovimientoGeneral error: ' . $e->getMessage());
        }
    }

    public function descontrolarMovimientoGeneral(int $id): void
    {
        try {
            $st = Db::pdo()->prepare('
                UPDATE caja_general_movimientos
                SET controlado = 0, controlado_por = NULL, controlado_at = NULL
                WHERE id = :i LIMIT 1
            ');
            $st->execute([':i' => $id]);
        } catch (\Throwable $e) {
            error_log('CajaRepo::descontrolarMovimientoGeneral error: ' . $e->getMessage());
        }
    }

    private static ?bool $cierreCtrlReady = null;

    private function ensureCierreControles(): void
    {
        if (self::$cierreCtrlReady !== null) {
            return;
        }
        self::$cierreCtrlReady = true;
        try {
            Db::pdo()->exec('
                CREATE TABLE IF NOT EXISTS caja_cierre_controles (
                    caja_id INT UNSIGNED NOT NULL,
                    controlado_por INT UNSIGNED DEFAULT NULL,
                    controlado_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (caja_id)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ');
        } catch (\Throwable $e) {
            error_log('CajaRepo::ensureCierreControles error: ' . $e->getMessage());
        }
    }

    /** Aperturas/cierres con sucursal, punto de venta y estado de control. */
    public function cierresEfectivo(?string $desde = null, ?string $hasta = null, int $limit = 50): array
    {
        $this->ensureCierreControles();
        $limit = max(1, min(200, $limit));
        $params = [];
        $where = '1=1';
        if ($desde !== null && $desde !== '') {
            $where .= ' AND ca.fecha >= :desde';
            $params[':desde'] = $desde;
        }
        if ($hasta !== null && $hasta !== '') {
            $where .= ' AND ca.fecha <= :hasta';
            $params[':hasta'] = $hasta;
        }
        try {
            $st = Db::pdo()->prepare("
                SELECT ca.*, s.nomsuc AS sucursal_nombre, s.punto_venta AS pto_vta,
                       cc.controlado_por, cc.controlado_at, a.nombre AS controlado_por_nombre
                FROM caja_aperturas ca
                LEFT JOIN admin_sucursales s ON s.id = ca.sucursal_id
                LEFT JOIN caja_cierre_controles cc ON cc.caja_id = ca.id
                LEFT JOIN admin_users a ON a.id = cc.controlado_por
                WHERE {$where}
                ORDER BY ca.fecha DESC, ca.id DESC
                LIMIT {$limit}
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CajaRepo::cierresEfectivo error: ' . $e->getMessage());
            return [];
        }
    }

    /** Desglose de efectivo de una apertura/cierre. */
    public function efectivoCierre(array $ap, int $puntoVenta): array
    {
        $apId = (int)($ap['id'] ?? 0);
        $fecha = (string)($ap['fecha'] ?? date('Y-m-d'));
        $apCreada = (string)($ap['created_at'] ?? $fecha . ' 00:00:00');
        $inicial = (int)($ap['monto_inicial_cents'] ?? 0);
        $ventas = $apId > 0 ? $this->totalVentasEfectivoTurno($apId, $fecha, $puntoVenta, $apCreada) : 0;
        $mov = $apId > 0 ? $this->totalMovimientos($apId) : ['total_ingresos' => 0, 'total_egresos' => 0];
        $ingresos = (int)($mov['total_ingresos'] ?? 0);
        $egresos = (int)($mov['total_egresos'] ?? 0);
        return [
            'inicial' => $inicial,
            'ventas_efectivo' => $ventas,
            'ingresos' => $ingresos,
            'egresos' => $egresos,
            'saldo' => $inicial + $ventas + $ingresos - $egresos,
        ];
    }

    public function controlarCierre(int $id, int $adminUserId): void
    {
        $this->ensureCierreControles();
        try {
            Db::pdo()->prepare('
                INSERT INTO caja_cierre_controles (caja_id, controlado_por, controlado_at)
                VALUES (:id, :cp, NOW())
                ON DUPLICATE KEY UPDATE controlado_por = :cp, controlado_at = NOW()
            ')->execute([':id' => $id, ':cp' => $adminUserId]);
        } catch (\Throwable $e) {
            error_log('CajaRepo::controlarCierre error: ' . $e->getMessage());
        }
    }

    public function descontrolarCierre(int $id): void
    {
        $this->ensureCierreControles();
        try {
            Db::pdo()->prepare('DELETE FROM caja_cierre_controles WHERE caja_id = :id LIMIT 1')->execute([':id' => $id]);
        } catch (\Throwable $e) {
            error_log('CajaRepo::descontrolarCierre error: ' . $e->getMessage());
        }
    }

    /** Ingresos/egresos de caja general discriminados por controlado. */
    public function totalesGeneralesControl(?string $desde = null, ?string $hasta = null): array
    {
        $out = [
            'ing_controlado' => 0, 'ing_no_controlado' => 0,
            'egr_controlado' => 0, 'egr_no_controlado' => 0,
        ];
        try {
            $sql = "
                SELECT
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' AND controlado = 1 THEN monto_cents ELSE 0 END), 0) AS ing_controlado,
                    COALESCE(SUM(CASE WHEN tipo = 'ingreso' AND (controlado = 0 OR controlado IS NULL) THEN monto_cents ELSE 0 END), 0) AS ing_no_controlado,
                    COALESCE(SUM(CASE WHEN tipo = 'egreso' AND controlado = 1 THEN monto_cents ELSE 0 END), 0) AS egr_controlado,
                    COALESCE(SUM(CASE WHEN tipo = 'egreso' AND (controlado = 0 OR controlado IS NULL) THEN monto_cents ELSE 0 END), 0) AS egr_no_controlado
                FROM caja_general_movimientos
                WHERE 1=1
            ";
            $params = [];
            if ($desde !== null && $desde !== '') {
                $sql .= ' AND DATE(created_at) >= :desde';
                $params[':desde'] = $desde;
            }
            if ($hasta !== null && $hasta !== '') {
                $sql .= ' AND DATE(created_at) <= :hasta';
                $params[':hasta'] = $hasta;
            }
            $st = Db::pdo()->prepare($sql);
            $st->execute($params);
            $row = $st->fetch() ?: [];
            foreach ($out as $k => $v) {
                $out[$k] = (int)($row[$k] ?? 0);
            }
        } catch (\Throwable $e) {
            error_log('CajaRepo::totalesGeneralesControl error: ' . $e->getMessage());
        }
        return $out;
    }

    public function saldoGeneral(): int
    {
        try {
            $st = Db::pdo()->query("
                SELECT COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto_cents ELSE 0 END), 0)
                     - COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto_cents ELSE 0 END), 0)
                FROM caja_general_movimientos
            ");
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            error_log('CajaRepo::saldoGeneral error: ' . $e->getMessage());
            return 0;
        }
    }

    // ── Ajustes de apertura con aprobación ──

    // ── Cierre por turno: imputación de documentos ──

    private static ?bool $cajaColsReady = null;

    private function ensureCajaColumnas(): void
    {
        if (self::$cajaColsReady !== null) {
            return;
        }
        self::$cajaColsReady = true;
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
            $fields = array_column($cols, 'Field');
            if (!in_array('caja_apertura_id', $fields, true)) {
                Db::pdo()->exec('ALTER TABLE facturas ADD COLUMN caja_apertura_id INT UNSIGNED DEFAULT NULL');
                Db::pdo()->exec('ALTER TABLE facturas ADD KEY idx_caja_apertura (caja_apertura_id)');
            }
        } catch (\Throwable $e) {
            error_log('CajaRepo::ensureCajaColumnas facturas error: ' . $e->getMessage());
        }
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM recibos')->fetchAll();
            $fields = array_column($cols, 'Field');
            if (!in_array('caja_apertura_id', $fields, true)) {
                Db::pdo()->exec('ALTER TABLE recibos ADD COLUMN caja_apertura_id INT UNSIGNED DEFAULT NULL');
                Db::pdo()->exec('ALTER TABLE recibos ADD KEY idx_caja_apertura (caja_apertura_id)');
            }
        } catch (\Throwable $e) {
            error_log('CajaRepo::ensureCajaColumnas recibos error: ' . $e->getMessage());
        }
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM caja_aperturas')->fetchAll();
            $fields = array_column($cols, 'Field');
            if (!in_array('monto_proxima_apertura_cents', $fields, true)) {
                Db::pdo()->exec('ALTER TABLE caja_aperturas ADD COLUMN monto_proxima_apertura_cents INT NOT NULL DEFAULT 0');
            }
        } catch (\Throwable $e) {
            error_log('CajaRepo::ensureCajaColumnas aperturas error: ' . $e->getMessage());
        }
    }

    /** Documentos del turno: imputados a esta apertura o creados después de abrirla. */
    private function turnoWhere(string $alias): string
    {
        return "({$alias}.caja_apertura_id = :caja OR ({$alias}.caja_apertura_id IS NULL AND {$alias}.created_at >= :apCreada))";
    }

    public function totalVentasEfectivoTurno(int $cajaId, string $fecha, int $puntoVenta, string $aperturaCreada): int
    {
        $this->ensureCajaColumnas();
        try {
            $extra = $this->efectivoNoCajaWhere();
            $tipo = $this->formaPagoTipoExpr('fp');
            $st = Db::pdo()->prepare("
                SELECT COALESCE(SUM(fp.monto_cents), 0)
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                WHERE f.estado = 'emitida'
                  AND f.fecha = :fec
                  AND f.punto_venta = :pv
                  AND " . $this->turnoWhere('f') . "
                  AND {$tipo} = 'efectivo'
                  {$extra}
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta, ':caja' => $cajaId, ':apCreada' => $aperturaCreada]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            error_log('CajaRepo::totalVentasEfectivoTurno error: ' . $e->getMessage());
            return 0;
        }
    }

    public function totalVentasTransferenciaTurno(int $cajaId, string $fecha, int $puntoVenta, string $aperturaCreada): int
    {
        $this->ensureCajaColumnas();
        try {
            $tipo = $this->formaPagoTipoExpr('fp');
            $st = Db::pdo()->prepare("
                SELECT COALESCE(SUM(fp.monto_cents), 0)
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                WHERE f.estado = 'emitida'
                  AND f.fecha = :fec
                  AND f.punto_venta = :pv
                  AND " . $this->turnoWhere('f') . "
                  AND {$tipo} = 'banco'
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta, ':caja' => $cajaId, ':apCreada' => $aperturaCreada]);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            error_log('CajaRepo::totalVentasTransferenciaTurno error: ' . $e->getMessage());
            return 0;
        }
    }

    public function totalRecibosTurno(int $cajaId, string $fecha, int $puntoVenta, string $aperturaCreada): int
    {
        $this->ensureCajaColumnas();
        try {
            $pvWhere = '';
            $params = [':fec' => $fecha, ':caja' => $cajaId, ':apCreada' => $aperturaCreada];
            if ($this->recibosTienePuntoVenta()) {
                $pvWhere = ' AND r.punto_venta = :pv';
                $params[':pv'] = $puntoVenta;
            }
            $st = Db::pdo()->prepare("
                SELECT COALESCE(SUM(r.monto_cents), 0)
                FROM recibos r
                WHERE r.estado = 'emitido'
                  AND r.fecha = :fec
                  AND " . $this->turnoWhere('r') . "
                  $pvWhere
            ");
            $st->execute($params);
            return (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            error_log('CajaRepo::totalRecibosTurno error: ' . $e->getMessage());
            return 0;
        }
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

    public function ventasDetalleTurno(int $cajaId, string $fecha, int $puntoVenta, string $aperturaCreada): array
    {
        $this->ensureCajaColumnas();
        try {
            $extra = $this->efectivoNoCajaWhere();
            $tipoSel = $this->formaPagoTipoExpr('fp') . ' AS forma_tipo';
            $equipoSel = 'NULL AS equipo_id, NULL AS equipo_nombre';
            $equipoJoin = '';
            if ($this->pagosTieneEquipo()) {
                $equipoSel = "fp.equipo_id, COALESCE(NULLIF(TRIM(e.empresa), ''), CONCAT('Equipo ', fp.equipo_id)) AS equipo_nombre";
                $equipoJoin = 'LEFT JOIN equipotar e ON e.idequipo = fp.equipo_id';
            }
            $st = Db::pdo()->prepare("
                SELECT fp.id, f.codigo, f.cliente_nombre, f.created_at,
                       fp.forma_pago, fp.monto_cents, {$tipoSel}, {$equipoSel}
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                {$equipoJoin}
                WHERE f.estado = 'emitida'
                  AND f.fecha = :fec
                  AND f.punto_venta = :pv
                  AND " . $this->turnoWhere('f') . "
                  {$extra}
                ORDER BY f.created_at ASC, f.id ASC, fp.id ASC
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta, ':caja' => $cajaId, ':apCreada' => $aperturaCreada]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CajaRepo::ventasDetalleTurno error: ' . $e->getMessage());
            return [];
        }
    }

    public function recibosDetalleTurno(int $cajaId, string $fecha, int $puntoVenta, string $aperturaCreada): array
    {
        $this->ensureCajaColumnas();
        try {
            $pvWhere = '';
            $params = [':fec' => $fecha, ':caja' => $cajaId, ':apCreada' => $aperturaCreada];
            if ($this->recibosTienePuntoVenta()) {
                $pvWhere = ' AND r.punto_venta = :pv';
                $params[':pv'] = $puntoVenta;
            }
            $st = Db::pdo()->prepare("
                SELECT r.id, r.codigo, r.cliente_nombre, r.created_at,
                       r.forma_pago, r.monto_cents
                FROM recibos r
                WHERE r.estado = 'emitido'
                  AND r.fecha = :fec
                  AND " . $this->turnoWhere('r') . "
                  $pvWhere
                ORDER BY r.created_at ASC, r.id ASC
            ");
            $st->execute($params);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            error_log('CajaRepo::recibosDetalleTurno error: ' . $e->getMessage());
            return [];
        }
    }

    /** Marca facturas y recibos del turno como imputados a este cierre. */
    public function marcarDocumentosCierre(int $cajaId, string $fecha, int $puntoVenta, string $aperturaCreada): void
    {
        $this->ensureCajaColumnas();
        try {
            $st = Db::pdo()->prepare("
                UPDATE facturas
                SET caja_apertura_id = :caja
                WHERE caja_apertura_id IS NULL
                  AND estado = 'emitida'
                  AND fecha = :fec
                  AND punto_venta = :pv
                  AND created_at >= :apCreada
            ");
            $st->execute([':caja' => $cajaId, ':fec' => $fecha, ':pv' => $puntoVenta, ':apCreada' => $aperturaCreada]);
        } catch (\Throwable $e) {
            error_log('CajaRepo::marcarDocumentosCierre facturas error: ' . $e->getMessage());
        }
        try {
            $pvWhere = $this->recibosTienePuntoVenta() ? ' AND punto_venta = :pv' : '';
            $params = [':caja' => $cajaId, ':fec' => $fecha, ':apCreada' => $aperturaCreada];
            if ($pvWhere !== '') {
                $params[':pv'] = $puntoVenta;
            }
            $st = Db::pdo()->prepare("
                UPDATE recibos
                SET caja_apertura_id = :caja
                WHERE caja_apertura_id IS NULL
                  AND estado = 'emitido'
                  AND fecha = :fec
                  AND created_at >= :apCreada
                  $pvWhere
            ");
            $st->execute($params);
        } catch (\Throwable $e) {
            error_log('CajaRepo::marcarDocumentosCierre recibos error: ' . $e->getMessage());
        }
    }

    /** Último cierre con saldo dejado para la próxima apertura. */
    public function ultimoCierreConSaldo(int $sucursalId): ?array
    {
        $this->ensureCajaColumnas();
        try {
            $st = Db::pdo()->prepare("
                SELECT id, fecha, turno, monto_proxima_apertura_cents
                FROM caja_aperturas
                WHERE sucursal_id = :suc
                  AND estado = 'cerrada'
                  AND monto_proxima_apertura_cents > 0
                ORDER BY id DESC LIMIT 1
            ");
            $st->execute([':suc' => $sucursalId]);
            return $st->fetch() ?: null;
        } catch (\Throwable $e) {
            error_log('CajaRepo::ultimoCierreConSaldo error: ' . $e->getMessage());
            return null;
        }
    }

    private static ?bool $ajustesTableReady = null;

    private function ensureAjustesTable(): void
    {
        if (self::$ajustesTableReady !== null) {
            return;
        }
        self::$ajustesTableReady = true;
        try {
            Db::pdo()->exec("
                CREATE TABLE IF NOT EXISTS caja_apertura_ajustes (
                    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
                    caja_id INT UNSIGNED NOT NULL,
                    campo VARCHAR(50) NOT NULL DEFAULT 'monto_inicial_cents',
                    valor_anterior_cents INT NOT NULL DEFAULT 0,
                    valor_nuevo_cents INT NOT NULL DEFAULT 0,
                    motivo TEXT NOT NULL,
                    estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
                    solicitado_por INT UNSIGNED DEFAULT NULL,
                    resuelto_por INT UNSIGNED DEFAULT NULL,
                    nota_resolucion VARCHAR(255) DEFAULT NULL,
                    created_at DATETIME DEFAULT NULL,
                    resolved_at DATETIME DEFAULT NULL,
                    PRIMARY KEY (id),
                    KEY idx_caja (caja_id),
                    KEY idx_estado (estado)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
            ");
        } catch (\Throwable $e) {
            error_log('CajaRepo::ensureAjustesTable error: ' . $e->getMessage());
        }
    }

    public function solicitarAjusteApertura(int $cajaId, int $nuevoCents, string $motivo, int $solicitadoPor): int
    {
        $this->ensureAjustesTable();
        $apertura = $this->findById($cajaId);
        if (!$apertura) {
            throw new \RuntimeException('Apertura no encontrada.');
        }
        $st = Db::pdo()->prepare('
            INSERT INTO caja_apertura_ajustes (caja_id, campo, valor_anterior_cents, valor_nuevo_cents, motivo, estado, solicitado_por, created_at)
            VALUES (:caja, \'monto_inicial_cents\', :ant, :nuevo, :motivo, \'pendiente\', :sol, NOW())
        ');
        $st->execute([
            ':caja' => $cajaId,
            ':ant' => (int)($apertura['monto_inicial_cents'] ?? 0),
            ':nuevo' => $nuevoCents,
            ':motivo' => $motivo,
            ':sol' => $solicitadoPor ?: null,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function ajustePendienteDeCaja(int $cajaId): ?array
    {
        $this->ensureAjustesTable();
        $st = Db::pdo()->prepare("
            SELECT aj.*, s.nombre AS solicitado_por_nombre
            FROM caja_apertura_ajustes aj
            LEFT JOIN admin_users s ON s.id = aj.solicitado_por
            WHERE aj.caja_id = :caja AND aj.estado = 'pendiente'
            ORDER BY aj.id DESC LIMIT 1
        ");
        $st->execute([':caja' => $cajaId]);
        return $st->fetch() ?: null;
    }

    public const CAMPOS_AJUSTE_CIERRE = [
        'monto_cierre_cents' => 'Monto de cierre',
        'monto_retirado_cents' => 'Pasaje a Caja General',
        'monto_proxima_apertura_cents' => 'Saldo próxima apertura',
    ];

    public function solicitarAjusteCierre(int $cajaId, string $campo, int $nuevoCents, string $motivo, int $solicitadoPor): int
    {
        $this->ensureAjustesTable();
        if (!isset(self::CAMPOS_AJUSTE_CIERRE[$campo])) {
            throw new \RuntimeException('Campo inválido.');
        }
        $apertura = $this->findById($cajaId);
        if (!$apertura) {
            throw new \RuntimeException('Cierre no encontrado.');
        }
        if (($apertura['estado'] ?? '') !== 'cerrada') {
            throw new \RuntimeException('Solo se puede corregir un cierre ya realizado.');
        }
        $st = Db::pdo()->prepare('
            INSERT INTO caja_apertura_ajustes (caja_id, campo, valor_anterior_cents, valor_nuevo_cents, motivo, estado, solicitado_por, created_at)
            VALUES (:caja, :campo, :ant, :nuevo, :motivo, \'pendiente\', :sol, NOW())
        ');
        $st->execute([
            ':caja' => $cajaId,
            ':campo' => $campo,
            ':ant' => (int)($apertura[$campo] ?? 0),
            ':nuevo' => $nuevoCents,
            ':motivo' => $motivo,
            ':sol' => $solicitadoPor ?: null,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function ajustePendienteDeCajaPorCampo(int $cajaId, string $campo): ?array
    {
        $this->ensureAjustesTable();
        $st = Db::pdo()->prepare("
            SELECT aj.*, s.nombre AS solicitado_por_nombre
            FROM caja_apertura_ajustes aj
            LEFT JOIN admin_users s ON s.id = aj.solicitado_por
            WHERE aj.caja_id = :caja AND aj.campo = :campo AND aj.estado = 'pendiente'
            ORDER BY aj.id DESC LIMIT 1
        ");
        $st->execute([':caja' => $cajaId, ':campo' => $campo]);
        return $st->fetch() ?: null;
    }

    public function movimientoGeneralPorOrigen(string $origen, int $origenId): ?array
    {
        try {
            $st = Db::pdo()->prepare('
                SELECT * FROM caja_general_movimientos
                WHERE origen = :o AND origen_id = :oid LIMIT 1
            ');
            $st->execute([':o' => $origen, ':oid' => $origenId]);
            return $st->fetch() ?: null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function actualizarMontoGeneral(int $id, int $montoCents): void
    {
        Db::pdo()->prepare('UPDATE caja_general_movimientos SET monto_cents = :m WHERE id = :i LIMIT 1')
            ->execute([':m' => $montoCents, ':i' => $id]);
    }

    public function eliminarMovimientoGeneral(int $id): void
    {
        Db::pdo()->prepare('DELETE FROM caja_general_movimientos WHERE id = :i LIMIT 1')
            ->execute([':i' => $id]);
    }

    public function ajustesPendientes(): array
    {
        $this->ensureAjustesTable();
        $st = Db::pdo()->query("
            SELECT aj.*, s.nombre AS solicitado_por_nombre,
                   ca.fecha, ca.turno, ca.monto_inicial_cents, su.nomsuc AS sucursal_nombre
            FROM caja_apertura_ajustes aj
            INNER JOIN caja_aperturas ca ON ca.id = aj.caja_id
            LEFT JOIN admin_users s ON s.id = aj.solicitado_por
            LEFT JOIN admin_sucursales su ON su.id = ca.sucursal_id
            WHERE aj.estado = 'pendiente'
            ORDER BY aj.created_at ASC
        ");
        return $st->fetchAll();
    }

    public function ajustesHistorial(int $limit = 20): array
    {
        $this->ensureAjustesTable();
        $limit = max(1, min(100, $limit));
        $st = Db::pdo()->query("
            SELECT aj.*, s.nombre AS solicitado_por_nombre, r.nombre AS resuelto_por_nombre,
                   ca.fecha, ca.turno, su.nomsuc AS sucursal_nombre
            FROM caja_apertura_ajustes aj
            INNER JOIN caja_aperturas ca ON ca.id = aj.caja_id
            LEFT JOIN admin_users s ON s.id = aj.solicitado_por
            LEFT JOIN admin_users r ON r.id = aj.resuelto_por
            LEFT JOIN admin_sucursales su ON su.id = ca.sucursal_id
            ORDER BY aj.id DESC LIMIT {$limit}
        ");
        return $st->fetchAll();
    }

    public function resolverAjuste(int $id, string $estado, int $resueltoPor, ?string $nota): void
    {
        $this->ensureAjustesTable();
        $this->ensureCajaColumnas();
        if (!in_array($estado, ['aprobado', 'rechazado'], true)) {
            throw new \RuntimeException('Estado inválido.');
        }
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $st = $pdo->prepare("SELECT * FROM caja_apertura_ajustes WHERE id = :i LIMIT 1 FOR UPDATE");
            $st->execute([':i' => $id]);
            $aj = $st->fetch() ?: null;
            if (!$aj) {
                throw new \RuntimeException('Solicitud no encontrada.');
            }
            if (($aj['estado'] ?? '') !== 'pendiente') {
                throw new \RuntimeException('La solicitud ya fue resuelta.');
            }
            $campo = (string)($aj['campo'] ?? 'monto_inicial_cents');
            $cajaId = (int)$aj['caja_id'];
            $nuevo = (int)$aj['valor_nuevo_cents'];
            if ($estado === 'aprobado') {
                $stCaja = $pdo->prepare('SELECT * FROM caja_aperturas WHERE id = :caja LIMIT 1 FOR UPDATE');
                $stCaja->execute([':caja' => $cajaId]);
                $caja = $stCaja->fetch() ?: null;
                if (!$caja) {
                    throw new \RuntimeException('Caja no encontrada.');
                }
                if ($campo === 'monto_inicial_cents' && ($caja['estado'] ?? '') !== 'abierta') {
                    throw new \RuntimeException('La caja ya se cerró; la corrección de apertura ya no aplica.');
                }
                if (!in_array($campo, ['monto_inicial_cents', 'monto_cierre_cents', 'monto_retirado_cents', 'monto_proxima_apertura_cents'], true)) {
                    throw new \RuntimeException('Campo inválido.');
                }
                $pdo->prepare("UPDATE caja_aperturas SET {$campo} = :mon, updated_at = NOW() WHERE id = :caja LIMIT 1")
                    ->execute([':mon' => $nuevo, ':caja' => $cajaId]);

                // Sincronizar el pasaje a Caja General si se corrigió el retiro.
                if ($campo === 'monto_retirado_cents') {
                    $mov = $this->movimientoGeneralPorOrigen('cierre_caja', $cajaId);
                    if ($mov && $nuevo > 0) {
                        $this->actualizarMontoGeneral((int)$mov['id'], $nuevo);
                    } elseif ($mov && $nuevo <= 0) {
                        $this->eliminarMovimientoGeneral((int)$mov['id']);
                    } elseif (!$mov && $nuevo > 0) {
                        $this->agregarMovimientoGeneral('ingreso', 'cierre_caja', $cajaId,
                            'Retiro cierre caja (corrección)', $nuevo, $resueltoPor);
                    }
                }
            }
            $pdo->prepare("
                UPDATE caja_apertura_ajustes
                SET estado = :est, resuelto_por = :res, nota_resolucion = :nota, resolved_at = NOW()
                WHERE id = :i LIMIT 1
            ")->execute([':est' => $estado, ':res' => $resueltoPor, ':nota' => $nota, ':i' => $id]);
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
