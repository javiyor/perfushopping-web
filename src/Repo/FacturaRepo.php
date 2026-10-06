<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class FacturaRepo
{
    public static function normalizeCondIva(?string $value): string
    {
        $lower = strtolower(trim((string)$value));
        $collapsed = preg_replace('/\s+/', ' ', $lower);
        $normalized = trim(is_string($collapsed) ? $collapsed : $lower);
        $map = [
            'responsable inscripto' => 'responsable_inscripto',
            'iva responsable inscripto' => 'responsable_inscripto',
            'resp inscripto' => 'responsable_inscripto',
            'resp. inscripto' => 'responsable_inscripto',
            'responsable_inscript' => 'responsable_inscripto',
            'ri' => 'responsable_inscripto',
            'consumidor final' => 'consumidor_final',
            'cf' => 'consumidor_final',
            'monotributista' => 'monotributista',
            'monotributo' => 'monotributista',
            'mono' => 'monotributista',
            'exento' => 'exento',
            'ex' => 'exento',
        ];
        return $map[$normalized] ?? $normalized ?: 'consumidor_final';
    }

    private function searchWhere(string $q, string $estado, string $desde, string $hasta, array &$params): array
    {
        $where = [];
        if ($q !== '') {
            $where[] = '(f.codigo LIKE :like OR f.cliente_nombre LIKE :like OR f.cliente_cuit LIKE :like)';
            $params[':like'] = '%' . $q . '%';
        }
        if ($estado !== '') {
            $where[] = 'f.estado = :estado';
            $params[':estado'] = $estado;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde)) {
            $where[] = 'f.fecha >= :desde';
            $params[':desde'] = $desde;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
            $where[] = 'f.fecha <= :hasta';
            $params[':hasta'] = $hasta;
        }
        return $where;
    }

    public function search(string $q = '', string $estado = '', int $limit = 60, int $offset = 0, string $desde = '', string $hasta = ''): array
    {
        $this->ensureEntregaColumns();
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);
        $q = trim($q);
        $estado = trim($estado);
        $params = [];
        $where = $this->searchWhere($q, $estado, $desde, $hasta, $params);

        $sql = '
            SELECT f.*, a.nombre AS created_by_nombre, v.nombre AS vendedor_nombre, COUNT(fi.id) AS items_count,
                   ac.cae, ac.cae_vto, ac.resultado AS arca_resultado, ac.observaciones AS arca_observaciones,
                   COALESCE(s.punto_venta_arca, f.punto_venta) AS pv_arca_num
            FROM facturas f
            LEFT JOIN admin_users a ON a.id = f.created_by
            LEFT JOIN admin_users v ON v.id = f.vendedor_id
            LEFT JOIN admin_sucursales s ON s.id = f.sucursal_id
            LEFT JOIN factura_items fi ON fi.factura_id = f.id
            LEFT JOIN (
                SELECT ac1.*
                FROM arca_comprobantes ac1
                INNER JOIN (
                    SELECT factura_id, MAX(id) AS max_id
                    FROM arca_comprobantes
                    GROUP BY factura_id
                ) ac2 ON ac2.factura_id = ac1.factura_id AND ac2.max_id = ac1.id
            ) ac ON ac.factura_id = f.id
        ';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' GROUP BY f.id ORDER BY f.created_at DESC, f.id DESC LIMIT ' . $limit . ' OFFSET ' . $offset;

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function countSearch(string $q = '', string $estado = '', string $desde = '', string $hasta = ''): int
    {
        $q = trim($q);
        $estado = trim($estado);
        $params = [];
        $where = $this->searchWhere($q, $estado, $desde, $hasta, $params);

        $sql = 'SELECT COUNT(*) FROM facturas f';
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    private static ?bool $facturasEntregaChecked = null;
    private static bool $facturasEntregaHasCols = false;
    private static ?bool $facturasOrderChecked = null;
    private static bool $facturasOrderHasCol = false;

    /** Crea la columna facturas.order_id si falta. Devuelve si quedó disponible. */
    public function ensureOrderColumn(): bool
    {
        if (self::$facturasOrderChecked !== null) {
            return self::$facturasOrderHasCol;
        }
        self::$facturasOrderChecked = true;
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
            $fields = array_column($cols, 'Field');
            if (!in_array('order_id', $fields, true)) {
                Db::pdo()->exec('ALTER TABLE facturas ADD COLUMN order_id INT UNSIGNED DEFAULT NULL AFTER presupuesto_id');
                Db::pdo()->exec('ALTER TABLE facturas ADD KEY idx_order (order_id)');
            }
            self::$facturasOrderHasCol = true;
        } catch (\Throwable $e) {
            try {
                $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
                self::$facturasOrderHasCol = in_array('order_id', array_column($cols, 'Field'), true);
            } catch (\Throwable $e2) {
                self::$facturasOrderHasCol = false;
            }
        }
        return self::$facturasOrderHasCol;
    }

    private function ensureComprobanteAsociadoColumn(): bool
    {
        if (self::$facturasEntregaChecked !== null) {
            return self::$facturasEntregaHasCols || in_array('comprobante_asociado_id', array_column(Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll(), 'Field'), true);
        }
        self::$facturasEntregaChecked = true;
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
            $fields = array_column($cols, 'Field');
            if (!in_array('comprobante_asociado_id', $fields, true)) {
                Db::pdo()->exec('ALTER TABLE facturas ADD COLUMN comprobante_asociado_id INT UNSIGNED DEFAULT NULL');
            }
            self::$facturasEntregaHasCols = in_array('comprobante_asociado_id', array_column($cols, 'Field'), true) || self::$facturasEntregaHasCols;
        } catch (\Throwable $e) {
            try {
                $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
                self::$facturasEntregaHasCols = in_array('comprobante_asociado_id', array_column($cols, 'Field'), true) || self::$facturasEntregaHasCols;
            } catch (\Throwable $e2) {
                self::$facturasEntregaHasCols = false;
            }
        }
        return self::$facturasEntregaHasCols;
    }

    public function facturaIdByOrder(int $orderId): ?int
    {
        if (!$this->ensureOrderColumn()) {
            return null;
        }
        $st = Db::pdo()->prepare('SELECT id FROM facturas WHERE order_id = :o LIMIT 1');
        $st->execute([':o' => $orderId]);
        $v = $st->fetchColumn();
        return $v !== false && $v !== null ? (int)$v : null;
    }

    private function ensureEntregaColumns(): void
    {
        if (self::$facturasEntregaChecked !== null) {
            return;
        }
        self::$facturasEntregaChecked = true;
        // Columna por columna: si una falla (ej. ya existe), igual se crean las demás.
        $ddls = [
            'entrega_tipo' => "ADD COLUMN entrega_tipo ENUM('local','envio') NOT NULL DEFAULT 'local'",
'transporte' => "ADD COLUMN transporte ENUM('propio','delivery','correo_argentino') DEFAULT NULL",
            'envio_estado' => "ADD COLUMN envio_estado ENUM('pendiente','en_transito','entregado','cancelado') DEFAULT NULL",
            'envio_direccion' => 'ADD COLUMN envio_direccion VARCHAR(255) DEFAULT NULL',
            'envio_observacion' => 'ADD COLUMN envio_observacion TEXT DEFAULT NULL',
            'comprobante_asociado_id' => 'ADD COLUMN comprobante_asociado_id INT UNSIGNED DEFAULT NULL'
        ];
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
            $fields = array_column($cols, 'Field');
            foreach ($ddls as $col => $ddl) {
                if (!in_array($col, $fields, true)) {
                    try {
                        Db::pdo()->exec("ALTER TABLE facturas {$ddl}");
                    } catch (\Throwable $e) {
                        error_log('FacturaRepo::ensureEntregaColumns ' . $col . ': ' . $e->getMessage());
                    }
                }
            }
            $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
            self::$facturasEntregaHasCols = in_array('entrega_tipo', array_column($cols, 'Field'), true);
        } catch (\Throwable $e) {
            self::$facturasEntregaHasCols = false;
        }
    }

    private static ?bool $sucursalColumnChecked = null;
    private static bool $sucursalColumnExists = false;

    private function ensureSucursalColumn(): bool
    {
        if (self::$sucursalColumnChecked !== null) {
            return self::$sucursalColumnExists;
        }
        self::$sucursalColumnChecked = true;
        try {
            $st = Db::pdo()->query("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'facturas' AND COLUMN_NAME = 'sucursal_id'");
            $exists = (int)$st->fetchColumn() > 0;
            if (!$exists) {
                Db::pdo()->exec('ALTER TABLE facturas ADD COLUMN sucursal_id INT UNSIGNED DEFAULT NULL AFTER punto_venta');
            }
            self::$sucursalColumnExists = true;
        } catch (\Throwable $e) {
            try {
                $cols = Db::pdo()->query('SHOW COLUMNS FROM facturas')->fetchAll();
                self::$sucursalColumnExists = in_array('sucursal_id', array_column($cols, 'Field'), true);
            } catch (\Throwable $e2) {
                self::$sucursalColumnExists = false;
            }
        }
        return self::$sucursalColumnExists;
    }

    public function findById(int $id): ?array
    {
        $this->ensureEntregaColumns();
        $st = Db::pdo()->prepare('
            SELECT f.*, a.nombre AS created_by_nombre, v.nombre AS vendedor_nombre
            FROM facturas f
            LEFT JOIN admin_users a ON a.id = f.created_by
            LEFT JOIN admin_users v ON v.id = f.vendedor_id
            WHERE f.id = :i LIMIT 1
        ');
        $st->execute([':i' => $id]);
        return $st->fetch() ?: null;
    }

    public function items(int $facturaId): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM factura_items WHERE factura_id = :f ORDER BY id ASC');
        $st->execute([':f' => $facturaId]);
        return $st->fetchAll();
    }

    public function pagos(int $facturaId): array
    {
        // Intentar con nuevas columnas, fallback si no existen
        try {
            $st = Db::pdo()->prepare('
                SELECT fp.*, c.banco_emisor AS cheque_banco, c.numero_cheque, c.titular AS cheque_titular, c.fecha_vencimiento AS cheque_vto, c.estado AS cheque_estado, c.cuit_titular AS cheque_cuit,
                       b.nombanc AS banco_nombre, p.descripcion AS plazo_descripcion, p.cuotas AS plazo_cuotas, p.dias AS plazo_dias, p.pricuo AS plazo_pricuo,
                       t.nomtar AS tarjeta_nombre, e.empresa AS equipo_empresa, e.idequipo AS equipo_idequipo,
                       bc.banco AS banco_cuenta_nombre
                FROM factura_pagos fp
                LEFT JOIN cheques c ON c.id = fp.cheque_id
                LEFT JOIN bancos b ON b.idban = fp.banco_id
                LEFT JOIN plazopago p ON p.idplazo = fp.idplazo
                LEFT JOIN tarjeta t ON t.idtarje = fp.tarjeta_id
                LEFT JOIN equipotar e ON e.idequipo = fp.equipo_id
                LEFT JOIN banco_cuentas bc ON bc.id = fp.banco_cuenta_id
                WHERE fp.factura_id = :f ORDER BY fp.id ASC
            ');
            $st->execute([':f' => $facturaId]);
            return $st->fetchAll();
        } catch (\Throwable $e) {
            $st = Db::pdo()->prepare('
                SELECT fp.*, c.banco_emisor AS cheque_banco, c.numero_cheque, c.titular AS cheque_titular, c.fecha_vencimiento AS cheque_vto, c.estado AS cheque_estado, c.cuit_titular AS cheque_cuit,
                       b.nombanc AS banco_nombre, p.descripcion AS plazo_descripcion, p.cuotas AS plazo_cuotas, p.dias AS plazo_dias, p.pricuo AS plazo_pricuo
                FROM factura_pagos fp
                LEFT JOIN cheques c ON c.id = fp.cheque_id
                LEFT JOIN bancos b ON b.idban = fp.banco_id
                LEFT JOIN plazopago p ON p.idplazo = fp.idplazo
                WHERE fp.factura_id = :f ORDER BY fp.id ASC
            ');
            $st->execute([':f' => $facturaId]);
            return $st->fetchAll();
        }
    }

    public function countActivas(): int
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM facturas WHERE estado <> 'anulada'");
        return (int)$st->fetchColumn();
    }

    public function nextCodigo(string $tipo = 'FACT-B'): string
    {
        $st = Db::pdo()->query("SELECT COUNT(*) FROM facturas WHERE YEAR(created_at) = YEAR(CURDATE())");
        $count = (int)$st->fetchColumn();
        return 'F-' . date('Ymd') . '-' . str_pad((string)($count + 1), 4, '0', STR_PAD_LEFT);
    }

    public function create(array $data, array $items, array $pagos): int
    {
        $this->ensureEntregaColumns();
        $hasOrder = $this->ensureOrderColumn();
        $hasSucursal = $this->ensureSucursalColumn();
        $hasComprobanteAsociado = $this->ensureComprobanteAsociadoColumn();
        $orderCol = $hasOrder ? ', order_id' : '';
        $orderVal = $hasOrder ? ', :order_id' : '';
        $sucursalCol = $hasSucursal ? ', sucursal_id' : '';
        $sucursalVal = $hasSucursal ? ', :sucursal_id' : '';
        $comprobanteAsociadoCol = $hasComprobanteAsociado ? ', comprobante_asociado_id' : '';
        $comprobanteAsociadoVal = $hasComprobanteAsociado ? ', :comprobante_asociado_id' : '';
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $fparams = [
                ':codigo' => $data['codigo'],
                ':tipo' => $data['tipo_comprobante'],
                ':punto_venta' => $data['punto_venta'] ?? 1,
                ':sucursal_id' => $data['sucursal_id'] ?? null,
                ':remito_id' => $data['remito_id'],
                ':presupuesto_id' => $data['presupuesto_id'],
                ':cliente_id' => $data['cliente_id'],
                ':idclien' => $data['idclien'],
                ':cliente_nombre' => $data['cliente_nombre'],
                ':cliente_cuit' => $data['cliente_cuit'],
                ':cliente_direc' => $data['cliente_direc'],
                ':cliente_tele' => $data['cliente_tele'],
                ':cliente_mail' => $data['cliente_mail'],
                ':cliente_condicion_iva' => $data['cliente_condicion_iva'],
                ':fecha' => $data['fecha'],
                ':subtotal' => $data['subtotal_cents'],
                ':iva' => $data['iva_cents'],
                ':descuento' => $data['descuento_cents'] ?? 0,
                ':puntos' => $data['puntos_cents'] ?? 0,
                ':total' => $data['total_cents'],
                ':estado' => $data['estado'] ?? 'emitida',
                ':forma_pago' => $data['forma_pago'],
                ':notas' => $data['notas'],
                ':created_by' => $data['created_by'],
                ':vendedor_id' => $data['vendedor_id'] ?? null,
                ':comprobante_asociado_id' => $data['comprobante_asociado_id'] ?? null,
            ];
            $params[':comprobante_asociado_id'] = $data['comprobante_asociado_id'] ?? null;
            if ($hasOrder) {
                $fparams[':order_id'] = $data['order_id'] ?? null;
            }
            if (!$hasSucursal) {
                unset($fparams[':sucursal_id']);
            }
            if (self::$facturasEntregaHasCols) {
$st = $pdo->prepare('
                    INSERT INTO facturas (codigo, tipo_comprobante, punto_venta' . $sucursalCol . $comprobanteAsociadoCol . ', remito_id, presupuesto_id' . $orderCol . ', cliente_id, idclien, cliente_nombre, cliente_cuit, cliente_direc, cliente_tele, cliente_mail, cliente_condicion_iva, fecha, subtotal_cents, iva_cents, descuento_cents, puntos_cents, total_cents, estado, forma_pago, entrega_tipo, transporte, envio_estado, envio_direccion, envio_obs, notas, created_by, vendedor_id, created_at, updated_at)
                    VALUES (:codigo, :tipo, :punto_venta' . $sucursalVal . $comprobanteAsociadoVal . ', :remito_id, :presupuesto_id' . $orderVal . ', :cliente_id, :idclien, :cliente_nombre, :cliente_cuit, :cliente_direc, :cliente_tele, :cliente_mail, :cliente_condicion_iva, :fecha, :subtotal, :iva, :descuento, :puntos, :total, :estado, :forma_pago, :entrega_tipo, :transporte, :envio_estado, :envio_direccion, :envio_obs, :notas, :created_by, :vendedor_id, NOW(), NOW())
                ');
                $fparams[':entrega_tipo'] = $data['entrega_tipo'] ?? 'local';
                $fparams[':transporte'] = $data['transporte'] ?? null;
                $fparams[':envio_estado'] = $data['envio_estado'] ?? null;
                $fparams[':envio_direccion'] = $data['envio_direccion'] ?? null;
                $fparams[':envio_obs'] = $data['envio_observacion'] ?? null;
                $st->execute($fparams);
            } else {
                $st = $pdo->prepare('
                    INSERT INTO facturas (codigo, tipo_comprobante, punto_venta' . $sucursalCol . $comprobanteAsociadoCol . ', remito_id, presupuesto_id, cliente_id, idclien, cliente_nombre, cliente_cuit, cliente_direc, cliente_tele, cliente_mail, cliente_condicion_iva, fecha, subtotal_cents, iva_cents, descuento_cents, puntos_cents, total_cents, estado, forma_pago, notas, created_by, vendedor_id, created_at, updated_at)
                    VALUES (:codigo, :tipo, :punto_venta' . $sucursalVal . $comprobanteAsociadoVal . ', :remito_id, :presupuesto_id, :cliente_id, :idclien, :cliente_nombre, :cliente_cuit, :cliente_direc, :cliente_tele, :cliente_mail, :cliente_condicion_iva, :fecha, :subtotal, :iva, :descuento, :puntos, :total, :estado, :forma_pago, :notas, :created_by, :vendedor_id, NOW(), NOW())
                ');
                unset($fparams[':order_id']);
                $st->execute($fparams);
            }
            $id = (int)$pdo->lastInsertId();

            $this->insertarItems($id, $items);
            $this->insertarPagos($id, $pagos);

            $pdo->commit();
            return $id;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    private function insertarItems(int $id, array $items): void
    {
        $pdo = Db::pdo();
        $hasDtoCol = false;
        $hasCostoCol = false;
        try {
            $itemCols = $pdo->query('SHOW COLUMNS FROM factura_items')->fetchAll();
            $itemFields = array_column($itemCols, 'Field');
            $hasDtoCol = in_array('descuento_pct', $itemFields, true);
            if (!in_array('costo_cents', $itemFields, true)) {
                $pdo->exec('ALTER TABLE factura_items ADD COLUMN costo_cents INT DEFAULT NULL');
            }
            $hasCostoCol = true;
        } catch (\Throwable $e) {}
        // Snapshot del costo vigente (precomp neto en pesos -> centavos por unidad)
        $costos = [];
        try {
            $ids = [];
            foreach ($items as $it) {
                $pid = (int)($it['idprodu'] ?? 0);
                if ($pid > 0) {
                    $ids[$pid] = true;
                }
            }
            if ($ids) {
                $in = implode(',', array_keys($ids));
                foreach ($pdo->query("SELECT idprodu, precomp FROM producto WHERE idprodu IN ({$in})")->fetchAll() as $pr) {
                    $costos[(int)$pr['idprodu']] = (int)round((float)($pr['precomp'] ?? 0) * 100);
                }
            }
        } catch (\Throwable $e) {}
        $sti = $pdo->prepare('
            INSERT INTO factura_items (factura_id, idprodu, idcodgusto, producto, variedad, qty, unit_price_cents, iva_rate, iva_cents, total_cents' . ($hasDtoCol ? ', descuento_pct' : '') . ($hasCostoCol ? ', costo_cents' : '') . ')
            VALUES (:fid, :idprodu, :idcodgusto, :producto, :variedad, :qty, :unit_price, :iva_rate, :iva_cents, :total' . ($hasDtoCol ? ', :dto' : '') . ($hasCostoCol ? ', :costo' : '') . ')
        ');
        foreach ($items as $it) {
            $itemParams = [
                ':fid' => $id,
                ':idprodu' => $it['idprodu'],
                ':idcodgusto' => $it['idcodgusto'],
                ':producto' => $it['producto'],
                ':variedad' => $it['variedad'],
                ':qty' => $it['qty'],
                ':unit_price' => $it['unit_price_cents'],
                ':iva_rate' => $it['iva_rate'],
                ':iva_cents' => $it['iva_cents'],
                ':total' => $it['total_cents'],
            ];
            if ($hasDtoCol) {
                $itemParams[':dto'] = $it['descuento_pct'] ?? 0;
            }
            if ($hasCostoCol) {
                $pid = (int)($it['idprodu'] ?? 0);
                $itemParams[':costo'] = $costos[$pid] ?? null;
            }
            $sti->execute($itemParams);
        }
    }

    private function insertarPagos(int $id, array $pagos): void
    {
        $pdo = Db::pdo();
        $hasTarjeta = false; $hasEquipo = false; $hasBancoCuenta = false; $hasMoneda = false;
        try {
            $cols = $pdo->query('SHOW COLUMNS FROM factura_pagos')->fetchAll();
            $fields = array_column($cols, 'Field');
            $hasTarjeta = in_array('tarjeta_id', $fields, true);
            $hasEquipo = in_array('equipo_id', $fields, true);
            $hasBancoCuenta = in_array('banco_cuenta_id', $fields, true);
            $needMoneda = ['moneda' => 'ADD COLUMN moneda CHAR(3) DEFAULT NULL',
                'monto_moneda_cents' => 'ADD COLUMN monto_moneda_cents INT DEFAULT NULL',
                'cotizacion' => 'ADD COLUMN cotizacion DECIMAL(18,6) DEFAULT NULL'];
            foreach ($needMoneda as $col => $ddl) {
                if (!in_array($col, $fields, true)) {
                    $pdo->exec("ALTER TABLE factura_pagos {$ddl}");
                }
            }
            $hasMoneda = true;
        } catch (\Throwable $e) {}
        if ($hasTarjeta && $hasEquipo && $hasBancoCuenta) {
            $monCols = $hasMoneda ? ', moneda, monto_moneda_cents, cotizacion' : '';
            $monVals = $hasMoneda ? ', :moneda, :montoMoneda, :cotiz' : '';
            $stp = $pdo->prepare('INSERT INTO factura_pagos (factura_id, forma_pago, cheque_id, monto_cents, cupon_numero, cupon_monto_cents, idplazo, banco_id, tarjeta_id, equipo_id, banco_cuenta_id' . $monCols . ') VALUES (:fid, :forma, :chq, :monto, :cupon, :cuponm, :plazo, :banco, :tarjeta, :equipo, :bancoCuenta' . $monVals . ')');
            foreach ($pagos as $pg) {
                // Compatibilidad: el código viejo 'tarjetas' equivale a 'tarjeta'
                $forma = $pg['forma_pago'] === 'tarjetas' ? 'tarjeta' : $pg['forma_pago'];
                $params = [
                    ':fid' => $id,
                    ':forma' => $forma,
                    ':chq' => $pg['cheque_id'] ?? null,
                    ':monto' => $pg['monto_cents'],
                    ':cupon' => $pg['cupon_numero'] ?? null,
                    ':cuponm' => $pg['cupon_monto_cents'] ?? null,
                    ':plazo' => $pg['idplazo'] ?? null,
                    ':banco' => $pg['banco_id'] ?? null,
                    ':tarjeta' => $pg['tarjeta_id'] ?? null,
                    ':equipo' => $pg['equipo_id'] ?? null,
                    ':bancoCuenta' => $pg['banco_cuenta_id'] ?? null,
                ];
                if ($hasMoneda) {
                    $params[':moneda'] = $pg['moneda'] ?? null;
                    $params[':montoMoneda'] = $pg['monto_moneda_cents'] ?? null;
                    $params[':cotiz'] = $pg['cotizacion'] ?? null;
                }
                $stp->execute($params);
            }
        } else {
            $stp = $pdo->prepare('INSERT INTO factura_pagos (factura_id, forma_pago, cheque_id, monto_cents, cupon_numero, cupon_monto_cents, idplazo, banco_id) VALUES (:fid, :forma, :chq, :monto, :cupon, :cuponm, :plazo, :banco)');
            foreach ($pagos as $pg) {
                $forma = $pg['forma_pago'] === 'tarjetas' ? 'tarjeta' : $pg['forma_pago'];
                $stp->execute([
                    ':fid' => $id,
                    ':forma' => $forma,
                    ':chq' => $pg['cheque_id'] ?? null,
                    ':monto' => $pg['monto_cents'],
                    ':cupon' => $pg['cupon_numero'] ?? null,
                    ':cuponm' => $pg['cupon_monto_cents'] ?? null,
                    ':plazo' => $pg['idplazo'] ?? null,
                    ':banco' => $pg['banco_id'] ?? null,
                ]);
            }
        }
    }

    /** Reemplaza cabecera, items y pagos de una factura existente (sin tocar código ni estado). */
    public function actualizar(int $id, array $data, array $items, array $pagos): void
    {
        $this->ensureEntregaColumns();
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $cols = 'tipo_comprobante = :tipo, cliente_id = :cliente_id, idclien = :idclien, cliente_nombre = :cliente_nombre, '
                . 'cliente_cuit = :cliente_cuit, cliente_direc = :cliente_direc, cliente_tele = :cliente_tele, '
                . 'cliente_mail = :cliente_mail, cliente_condicion_iva = :cond, fecha = :fecha, '
                . 'subtotal_cents = :subtotal, iva_cents = :iva, descuento_cents = :descuento, '
                . 'puntos_cents = :puntos, total_cents = :total, forma_pago = :forma_pago, '
                . 'notas = :notas, vendedor_id = :vendedor_id, updated_at = NOW()';
            $params = [
                ':tipo' => $data['tipo_comprobante'],
                ':cliente_id' => $data['cliente_id'],
                ':idclien' => $data['idclien'],
                ':cliente_nombre' => $data['cliente_nombre'],
                ':cliente_cuit' => $data['cliente_cuit'],
                ':cliente_direc' => $data['cliente_direc'],
                ':cliente_tele' => $data['cliente_tele'],
                ':cliente_mail' => $data['cliente_mail'],
                ':cond' => $data['cliente_condicion_iva'],
                ':fecha' => $data['fecha'],
                ':subtotal' => $data['subtotal_cents'],
                ':iva' => $data['iva_cents'],
                ':descuento' => $data['descuento_cents'] ?? 0,
                ':puntos' => $data['puntos_cents'] ?? 0,
                ':total' => $data['total_cents'],
                ':forma_pago' => $data['forma_pago'],
                ':notas' => $data['notas'],
                ':vendedor_id' => $data['vendedor_id'] ?? null,
            ];
            if (self::$facturasEntregaHasCols) {
                $cols .= ', entrega_tipo = :entrega_tipo, transporte = :transporte, envio_estado = :envio_estado, '
                    . 'envio_direccion = :envio_direccion, envio_observacion = :envio_obs';
                $params[':entrega_tipo'] = $data['entrega_tipo'] ?? 'local';
                $params[':transporte'] = $data['transporte'] ?? null;
                $params[':envio_estado'] = $data['envio_estado'] ?? null;
                $params[':envio_direccion'] = $data['envio_direccion'] ?? null;
                $params[':envio_obs'] = $data['envio_observacion'] ?? null;
            }
            $st = $pdo->prepare("UPDATE facturas SET {$cols} WHERE id = :i LIMIT 1");
            $params[':i'] = $id;
            $st->execute($params);

            $pdo->prepare('DELETE FROM factura_items WHERE factura_id = :f')->execute([':f' => $id]);
            $pdo->prepare('DELETE FROM factura_pagos WHERE factura_id = :f')->execute([':f' => $id]);
            $this->insertarItems($id, $items);
            $this->insertarPagos($id, $pagos);

            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function listarEnvios(?string $estado = null, int $limit = 100): array
    {
        $this->ensureEntregaColumns();
        if (!self::$facturasEntregaHasCols) {
            return [];
        }
        $where = "f.entrega_tipo = 'envio'";
        $params = [];
        if ($estado) {
            $where .= " AND f.envio_estado = :e";
            $params[':e'] = $estado;
        }
        $sql = "SELECT f.*, a.nombre AS created_by_nombre FROM facturas f LEFT JOIN admin_users a ON a.id=f.created_by WHERE {$where} ORDER BY f.fecha DESC, f.id DESC LIMIT " . max(1, min(200, $limit));
        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function marcarEnvioEntregado(int $id, int $adminId): void
    {
        $this->ensureEntregaColumns();
        Db::pdo()->prepare("UPDATE facturas SET envio_estado='entregado', updated_at=NOW() WHERE id=:i AND entrega_tipo='envio' LIMIT 1")->execute([':i' => $id]);
    }

    public function marcarEnvioEstado(int $id, string $estado): void
    {
        $this->ensureEntregaColumns();
        Db::pdo()->prepare("UPDATE facturas SET envio_estado=:e, updated_at=NOW() WHERE id=:i LIMIT 1")->execute([':e' => $estado, ':i' => $id]);
    }

    public function updateEnvioNumero(int $id, string $numero): void
    {
        $this->ensureEntregaColumns();
        $st = Db::pdo()->prepare("UPDATE facturas SET envio_numero = :n, updated_at = NOW() WHERE id = :i AND entrega_tipo = 'envio'");
        $st->execute([':n' => $numero, ':i' => $id]);
    }

    public function countEnviosPendientes(): int
    {
        $this->ensureEntregaColumns();
        if (!self::$facturasEntregaHasCols) return 0;
        $st = Db::pdo()->query("SELECT COUNT(*) FROM facturas WHERE entrega_tipo='envio' AND envio_estado IN ('pendiente','en_transito')");
        return (int)$st->fetchColumn();
    }

    public function updateEstado(int $id, string $estado): void
    {
        $st = Db::pdo()->prepare('UPDATE facturas SET estado = :e, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':e' => $estado, ':i' => $id]);
    }

    public function actualizarCodigo(int $id, string $codigo): void
    {
        $st = Db::pdo()->prepare('UPDATE facturas SET codigo = :c, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':c' => $codigo, ':i' => $id]);
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM facturas WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
    }

    private static ?array $productoColumns = null;

    private static function productoColumns(): array
    {
        if (self::$productoColumns !== null) {
            return self::$productoColumns;
        }
        try {
            $rows = Db::pdo()->query('SHOW COLUMNS FROM producto')->fetchAll();
            self::$productoColumns = array_column($rows, 'Field');
        } catch (\Throwable $e) {
            self::$productoColumns = [];
        }
        return self::$productoColumns;
    }

    public function searchProducts(string $q, int $limit = 20, ?int $iddepo = null): array
    {
        $limit = max(1, min(50, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $pdo = Db::pdo();
        $like = '%' . $q . '%';
        $params = [':like' => $like, ':likeBarcode' => $like, ':exact' => $q];

        // Mismos criterios que la búsqueda de compras: nombre, códigos,
        // código de barras del producto y código de variante (parcial).
        // Si es numérico también busca por idprodu exacto.
        $where = ['p.produ LIKE :like', 'p.codprodu LIKE :like', 'p.codprodup LIKE :like'];
        $exactRank = ['p.codprodu = :exact', 'p.codprodup = :exact'];
        if (ctype_digit($q)) {
            $params[':exactId'] = (int)$q;
            array_unshift($where, 'p.idprodu = :exactId');
            array_unshift($exactRank, 'p.idprodu = :exactId');
        }
        if (in_array('codbarra', self::productoColumns(), true)) {
            $where[] = 'p.codbarra LIKE :like';
            $exactRank[] = 'p.codbarra = :exact';
        }

        $sql = '
            SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precio1, p.precomp, p.codprodup, p.enweb, p.stocact,
                   i.codivaprodu, i.tiva
            FROM producto p
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE ' . implode(' OR ', $where) . '
               OR EXISTS (SELECT 1 FROM gustos g WHERE g.idprodu = p.idprodu AND g.codscan LIKE :likeBarcode)
            GROUP BY p.idprodu
            ORDER BY CASE WHEN ' . implode(' OR ', $exactRank) . ' THEN 0 ELSE 1 END, p.produ ASC
            LIMIT ' . $limit;
        $st = $pdo->prepare($sql);
        $st->execute($params);
        $products = $st->fetchAll();

        $matchedVariant = null;
        if (ctype_digit($q) || preg_match('/^\d{8,13}$/', $q)) {
            $st2 = $pdo->prepare('
                SELECT p.idprodu, p.codprodu, p.produ, p.precio, p.precio1, p.precomp, p.codprodup, p.enweb, p.stocact,
                       i.codivaprodu, i.tiva,
                       g.idcodgusto, g.nomgusto AS matched_nomgusto
                FROM gustos g
                INNER JOIN producto p ON p.idprodu = g.idprodu
                LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
                WHERE g.codscan = :c
                GROUP BY p.idprodu
                LIMIT 1
            ');
            $st2->execute([':c' => $q]);
            $byCode = $st2->fetch();
            if ($byCode) {
                $matchedVariant = [
                    'idcodgusto' => (int)$byCode['idcodgusto'],
                    'nomgusto' => $byCode['matched_nomgusto'],
                    'codscan' => $q,
                ];
                unset($byCode['idcodgusto'], $byCode['matched_nomgusto']);
                $exists = false;
                foreach ($products as $pr) {
                    if ((int)$pr['idprodu'] === (int)$byCode['idprodu']) { $exists = true; break;
                    }
                }
                if (!$exists) array_unshift($products, $byCode);
            }
        }

        $matchedV = $matchedVariant;
        foreach ($products as $idx => $pr) {
            $idprodu = (int)$pr['idprodu'];

            $st3 = $pdo->prepare('
                SELECT idcodgusto, nomgusto, codscan, stockact
                FROM gustos
                WHERE idprodu = :id AND discont = 0
                GROUP BY idcodgusto, nomgusto, codscan, stockact
                ORDER BY nomgusto ASC
            ');
            $st3->execute([':id' => $idprodu]);
            $products[$idx]['variants'] = $st3->fetchAll();

            $products[$idx]['stock_total'] = (int)($pr['stocact'] ?? 0);

            $products[$idx]['stock_deposito'] = 0;
            if ($iddepo) {
                $st4 = $pdo->prepare('
                    SELECT COALESCE(SUM(stock), 0)
                    FROM stock
                    WHERE idprodu = :p AND iddepo = :d
                ');
                $st4->execute([':p' => $idprodu, ':d' => $iddepo]);
                $products[$idx]['stock_deposito'] = (int)$st4->fetchColumn();
            }

            foreach (($products[$idx]['variants'] ?? []) as $vi => $v) {
                $idg = (int)$v['idcodgusto'];
                $products[$idx]['variants'][$vi]['stock_total'] = (int)($v['stockact'] ?? 0);
                $products[$idx]['variants'][$vi]['stock_deposito'] = 0;
                if ($iddepo) {
                    $st5 = $pdo->prepare('
                        SELECT COALESCE(SUM(stock), 0)
                        FROM stock
                        WHERE idcodgusto = :g AND iddepo = :d
                    ');
                    $st5->execute([':g' => $idg, ':d' => $iddepo]);
                    $products[$idx]['variants'][$vi]['stock_deposito'] = (int)$st5->fetchColumn();
                }
            }
        }

        if ($matchedV) {
            foreach ($products as $idx => $pr) {
                if ((int)$pr['idprodu'] === 0) continue;
                foreach (($pr['variants'] ?? []) as $v) {
                    if ((int)$v['idcodgusto'] === $matchedV['idcodgusto']) {
                        $products[$idx]['matched_variant_id'] = $matchedV['idcodgusto'];
                        $products[$idx]['matched_variant'] = $matchedV;
                        break;
                    }
                }
            }
        }

        return $products;
    }

    public function findClienteWeb(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        if ($q === '') return [];

        $condIvaExpr = $this->clientesTieneCondicionIva()
            ? 'COALESCE(c.condicion_iva, \'consumidor_final\')'
            : '\'consumidor_final\'';
        $catExpr = $this->clientesTieneCategoria()
            ? 'COALESCE(c.categoria, \'minorista\') AS categoria, COALESCE(c.precio_mayorista, 0) AS precio_mayorista, c.especialidad'
            : '\'minorista\' AS categoria, 0 AS precio_mayorista, NULL AS especialidad';

        $st = Db::pdo()->prepare('
            SELECT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   ' . $condIvaExpr . ' AS condicion_iva,
                   ' . $catExpr . '
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE c.razon LIKE :like OR c.cuit LIKE :like2
            ORDER BY c.razon ASC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%', ':like2' => '%' . $q . '%']);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            $r['condicion_iva'] = self::normalizeCondIva($r['condicion_iva'] ?? null);
            $r['categoria'] = self::normalizeCategoria($r['categoria'] ?? null);
            $r['precio_mayorista'] = (int)($r['precio_mayorista'] ?? 0);
        }
        return $rows;
    }

    public static function normalizeCategoria(?string $value): string
    {
        $normalized = strtolower(trim((string)$value));
        $map = [
            'minorista' => 'minorista',
            'minor' => 'minorista',
            'consumidor' => 'minorista',
            'mayorista' => 'mayorista',
            'mayor' => 'mayorista',
            'profesional' => 'profesional',
            'prof' => 'profesional',
        ];
        return $map[$normalized] ?? 'minorista';
    }

    public static function categoriaLabels(): array
    {
        return ['minorista' => 'Minorista', 'mayorista' => 'Mayorista', 'profesional' => 'Profesional'];
    }

    private static ?bool $clientesCategoriaChecked = null;
    private static bool $clientesTieneCategoria = false;

    private function clientesTieneCategoria(): bool
    {
        if (self::$clientesCategoriaChecked !== null) {
            return self::$clientesTieneCategoria;
        }
        self::$clientesCategoriaChecked = true;
        try {
            $cols = Db::pdo()->query('SHOW COLUMNS FROM clientes')->fetchAll();
            $fields = array_column($cols, 'Field');
            $missing = [];
            if (!in_array('categoria', $fields, true)) {
                $missing[] = "ADD COLUMN categoria VARCHAR(20) NOT NULL DEFAULT 'minorista'";
            }
            if (!in_array('precio_mayorista', $fields, true)) {
                $missing[] = 'ADD COLUMN precio_mayorista TINYINT(1) NOT NULL DEFAULT 0';
            }
            if (!in_array('especialidad', $fields, true)) {
                $missing[] = 'ADD COLUMN especialidad VARCHAR(60) DEFAULT NULL';
            }
            foreach ($missing as $ddl) {
                Db::pdo()->exec("ALTER TABLE clientes {$ddl}");
            }
            self::$clientesTieneCategoria = true;
        } catch (\Throwable $e) {
            try {
                $cols = Db::pdo()->query('SHOW COLUMNS FROM clientes')->fetchAll();
                self::$clientesTieneCategoria = in_array('categoria', array_column($cols, 'Field'), true);
            } catch (\Throwable $e2) {
                self::$clientesTieneCategoria = false;
            }
        }
        return self::$clientesTieneCategoria;
    }

    private function clientesTieneCondicionIva(): bool
    {
        return !empty(CustomerRepo::clientesColumnas()['condicion_iva']);
    }

    public function findRemitosDisponibles(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        $st = Db::pdo()->prepare('
            SELECT r.id, r.codigo, r.cliente_nombre, r.total_cents, r.fecha
            FROM remitos r
            WHERE r.estado = \'completado\'
              AND r.tipo = \'salida\'
              AND (r.codigo LIKE :like OR r.cliente_nombre LIKE :like)
            ORDER BY r.created_at DESC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function findFacturasDisponibles(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        $st = Db::pdo()->prepare('
            SELECT f.id, f.codigo, f.cliente_nombre, f.total_cents, f.fecha, f.tipo_comprobante
            FROM facturas f
            WHERE f.estado = \'emitida\'
              AND f.tipo_comprobante IN (\'FACT-A\', \'FACT-B\', \'FACT-C\')
              AND (f.codigo LIKE :like OR f.cliente_nombre LIKE :like)
            ORDER BY f.created_at DESC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function itemsByRemito(int $remitoId): array
    {
        $st = Db::pdo()->prepare('
            SELECT ri.*, p.precio, i.tiva
            FROM remito_items ri
            LEFT JOIN producto p ON p.idprodu = ri.idprodu
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE ri.remito_id = :r
            ORDER BY ri.id ASC
        ');
        $st->execute([':r' => $remitoId]);
        return $st->fetchAll();
    }

    public function findPresupuestosDisponibles(string $q, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $q = trim($q);
        $st = Db::pdo()->prepare('
            SELECT p.id, p.codigo, p.cliente_nombre, p.total_cents, p.fecha, p.cliente_id, p.idclien, p.cliente_cuit, p.cliente_direc, p.cliente_tele, p.cliente_mail
            FROM presupuestos p
            WHERE p.estado = \'aprobado\'
              AND (p.codigo LIKE :like OR p.cliente_nombre LIKE :like)
            ORDER BY p.created_at DESC
            LIMIT ' . $limit
        );
        $st->execute([':like' => '%' . $q . '%']);
        return $st->fetchAll();
    }

    public function itemsByPresupuesto(int $presupuestoId): array
    {
        $st = Db::pdo()->prepare('
            SELECT pi.*, p.precio, i.tiva
            FROM presupuesto_items pi
            LEFT JOIN producto p ON p.idprodu = pi.idprodu
            LEFT JOIN ivaprodu i ON i.codivaprodu = p.iva
            WHERE pi.presupuesto_id = :p
            ORDER BY pi.id ASC
        ');
        $st->execute([':p' => $presupuestoId]);
        return $st->fetchAll();
    }

    public function findClienteErpByWebId(int $webUserId): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT c.*
            FROM clientes c
            INNER JOIN web_users w ON w.cliente_id = c.idclien
            WHERE w.id = :id
            LIMIT 1
        ');
        $st->execute([':id' => $webUserId]);
        return $st->fetch() ?: null;
    }

    public function findClienteByIdclien(int $idclien): ?array
    {        $st = Db::pdo()->prepare('
            SELECT c.*
            FROM clientes c
            WHERE c.idclien = :id
            LIMIT 1
        ');
        $st->execute([':id' => $idclien]);
        return $st->fetch() ?: null;
    }

    public function upsertClienteArca(array $data): ?array
    {
        $cuit = trim($data['cuit'] ?? '');
        if ($cuit === '') return null;

        $razon = trim($data['razon'] ?? $data['razonSocial'] ?? '');
        $direc = trim($data['direc'] ?? '');
        $localidad = trim($data['localidad'] ?? '');
        $condIva = self::normalizeCondIva((string)($data['condicion_iva'] ?? ''));
        CustomerRepo::clientesColumnas(); // asegura localidad/condicion_iva antes de escribir
        $hasCond = self::clientesTieneCondicionIva();
        // Check if exists by CUIT
        $st = Db::pdo()->prepare('SELECT * FROM clientes WHERE cuit = :c LIMIT 1');
        $st->execute([':c' => $cuit]);
        $existing = $st->fetch();

        if ($existing) {
            $sets = ['razon = :r'];
            $params = [':r' => $razon, ':id' => $existing['idclien']];
            if ($direc !== '') {
                $sets[] = 'direc = :d';
                $params[':d'] = $direc;
            }
            if ($localidad !== '') {
                $sets[] = 'Localidad = :l';
                $params[':l'] = $localidad;
            }
            if ($hasCond) {
                $sets[] = "condicion_iva = COALESCE(NULLIF(TRIM(condicion_iva), ''), :ci)";
                $params[':ci'] = $condIva;
            }
            $st = Db::pdo()->prepare('UPDATE clientes SET ' . implode(', ', $sets) . ' WHERE idclien = :id LIMIT 1');
            $st->execute($params);
            $idclien = (int)$existing['idclien'];
        } else {
            $condCols = $hasCond ? ', condicion_iva' : '';
            $condVals = $hasCond ? ', :ci' : '';
            $st = Db::pdo()->prepare("
                INSERT INTO clientes (razon, cuit, direc, Localidad{$condCols}, activo, fealta)
                VALUES (:r, :c, :d, :l{$condVals}, 1, NOW())
            ");
            $params = [
                ':r' => $razon,
                ':c' => $cuit,
                ':d' => $direc,
                ':l' => $localidad,
            ];
            if ($hasCond) {
                $params[':ci'] = $condIva;
            }
            $st->execute($params);
            $idclien = (int)Db::pdo()->lastInsertId();
        }

        // Return in same format as findClienteWeb
        $condSelect = $hasCond ? "COALESCE(c.condicion_iva, 'consumidor_final')" : "'consumidor_final'";
        $st = Db::pdo()->prepare("
            SELECT 0 AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   {$condSelect} AS condicion_iva
            FROM clientes c
            WHERE c.idclien = :id LIMIT 1
        ");
        $st->execute([':id' => $idclien]);
        $r = $st->fetch();
        if ($r) {
            $r['condicion_iva'] = self::normalizeCondIva($r['condicion_iva'] ?? null);
        }
        return $r ?: null;
    }

    /** @return array<int, array<string,mixed>> */
    public function findDuplicadosCliente(string $razon, string $cuit): array
    {
        $razon = trim($razon);
        $digits = preg_replace('/\D/', '', $cuit) ?? '';
        $conds = [];
        $params = [];
        if ($digits !== '') {
            if (strlen($digits) <= 8) {
                $conds[] = 'c.cuit = :d1';
                $params[':d1'] = $digits;
                $conds[] = '(LENGTH(c.cuit) = 11 AND SUBSTRING(c.cuit, 3, 8) = :d2)';
                $params[':d2'] = str_pad($digits, 8, '0', STR_PAD_LEFT);
            } elseif (strlen($digits) === 11) {
                $conds[] = 'c.cuit = :d3';
                $params[':d3'] = $digits;
                $conds[] = 'c.cuit = :d4';
                $params[':d4'] = substr($digits, 2, 8);
            }
        }
        if ($razon !== '') {
            $conds[] = 'c.razon = :razon';
            $params[':razon'] = $razon;
        }
        if (!$conds) {
            return [];
        }

        $condIvaExpr = $this->clientesTieneCondicionIva()
            ? 'COALESCE(c.condicion_iva, \'consumidor_final\')'
            : '\'consumidor_final\'';
        $catExpr = $this->clientesTieneCategoria()
            ? 'COALESCE(c.categoria, \'minorista\') AS categoria, COALESCE(c.precio_mayorista, 0) AS precio_mayorista, c.especialidad'
            : '\'minorista\' AS categoria, 0 AS precio_mayorista, NULL AS especialidad';

        $st = Db::pdo()->prepare('
            SELECT DISTINCT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   ' . $condIvaExpr . ' AS condicion_iva,
                   ' . $catExpr . '
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE (' . implode(' OR ', $conds) . ')
            ORDER BY c.razon ASC
            LIMIT 6
        ');
        $st->execute($params);
        $rows = $st->fetchAll();
        $razonN = self::normNombre($razon);
        foreach ($rows as &$r) {
            $r['condicion_iva'] = self::normalizeCondIva($r['condicion_iva'] ?? null);
            $r['categoria'] = self::normalizeCategoria($r['categoria'] ?? null);
            $r['precio_mayorista'] = (int)($r['precio_mayorista'] ?? 0);

            $rc = preg_replace('/\D/', '', (string)($r['cuit'] ?? '')) ?? '';
            $motivos = [];
            if ($digits !== '' && $rc !== '') {
                $len = strlen($digits);
                $match = false;
                if ($len <= 8) {
                    $match = $rc === $digits
                        || (strlen($rc) === 11 && substr($rc, 2, 8) === str_pad($digits, 8, '0', STR_PAD_LEFT));
                } elseif ($len === 11) {
                    $match = $rc === $digits || $rc === substr($digits, 2, 8);
                }
                if ($match) {
                    $motivos[] = 'mismo DNI/CUIT';
                }
            }
            if ($razonN !== '' && self::normNombre((string)($r['name'] ?? '')) === $razonN) {
                $motivos[] = 'mismo nombre';
            }
            $r['motivo'] = $motivos ? implode(' y ', $motivos) : 'posible duplicado';
        }
        return $rows;
    }

    private static function normNombre(string $s): string
    {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = strtr($s, ['á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n']);
        return (string)preg_replace('/\s+/', ' ', $s);
    }

    public function crearClientePos(array $data): ?array
    {
        $cuit = trim($data['cuit'] ?? '');
        $razon = trim($data['razon'] ?? '');
        if ($razon === '') return null;

        $direc = trim($data['direc'] ?? '');
        $tele = trim($data['tele'] ?? '');
        $mail = trim($data['mail'] ?? '');
        $condIva = self::normalizeCondIva($data['condicion_iva'] ?? 'consumidor_final');
        $tieneCondIva = $this->clientesTieneCondicionIva();
        $tieneCat = $this->clientesTieneCategoria();
        $categoria = self::normalizeCategoria($data['categoria'] ?? 'minorista');
        $precioMayorista = !empty($data['precio_mayorista']) ? 1 : 0;
        $especialidad = mb_substr(trim((string)($data['especialidad'] ?? '')), 0, 60) ?: null;

        $existing = null;
        if ($cuit !== '') {
            $st = Db::pdo()->prepare('SELECT * FROM clientes WHERE cuit = :c LIMIT 1');
            $st->execute([':c' => $cuit]);
            $existing = $st->fetch();
        }

        if ($existing) {
            $setCond = $tieneCondIva ? ', condicion_iva = :ci' : '';
            $setCat = $tieneCat ? ', categoria = :cat, precio_mayorista = :pm, especialidad = :esp' : '';
            $st = Db::pdo()->prepare('
                UPDATE clientes SET razon = :r, direc = :d, tele = :t, mail = :m' . $setCond . $setCat . '
                WHERE idclien = :id LIMIT 1
            ');
            $params = [':r' => $razon, ':d' => $direc, ':t' => $tele, ':m' => $mail, ':id' => $existing['idclien']];
            if ($tieneCondIva) $params[':ci'] = $condIva;
            if ($tieneCat) {
                $params[':cat'] = $categoria;
                $params[':pm'] = $precioMayorista;
                $params[':esp'] = $especialidad;
            }
            $st->execute($params);
            $idclien = (int)$existing['idclien'];
        } else {
            $condCol = $tieneCondIva ? ', condicion_iva' : '';
            $condVal = $tieneCondIva ? ', :ci' : '';
            $catCol = $tieneCat ? ', categoria, precio_mayorista, especialidad' : '';
            $catVal = $tieneCat ? ', :cat, :pm, :esp' : '';
            $st = Db::pdo()->prepare('
                INSERT INTO clientes (razon, cuit, direc, tele, mail, activo, fealta' . $condCol . $catCol . ')
                VALUES (:r, :c, :d, :t, :m, 1, NOW()' . $condVal . $catVal . ')
            ');
            $params = [':r' => $razon, ':c' => $cuit, ':d' => $direc, ':t' => $tele, ':m' => $mail];
            if ($tieneCondIva) $params[':ci'] = $condIva;
            if ($tieneCat) {
                $params[':cat'] = $categoria;
                $params[':pm'] = $precioMayorista;
                $params[':esp'] = $especialidad;
            }
            $st->execute($params);
            $idclien = (int)Db::pdo()->lastInsertId();
        }

        // Return in same shape as findClienteWeb
        $condIvaExpr = $tieneCondIva
            ? 'COALESCE(c.condicion_iva, \'consumidor_final\')'
            : '\'consumidor_final\'';
        $catExpr = $tieneCat
            ? 'COALESCE(c.categoria, \'minorista\') AS categoria, COALESCE(c.precio_mayorista, 0) AS precio_mayorista, c.especialidad'
            : '\'minorista\' AS categoria, 0 AS precio_mayorista, NULL AS especialidad';
        $st = Db::pdo()->prepare('
            SELECT COALESCE(w.id, 0) AS id, c.idclien,
                   c.razon AS name, c.cuit, c.direc, c.tele AS phone, c.mail AS email,
                   c.Localidad AS city,
                   ' . $condIvaExpr . ' AS condicion_iva,
                   ' . $catExpr . '
            FROM clientes c
            LEFT JOIN web_users w ON w.cliente_id = c.idclien
            WHERE c.idclien = :id LIMIT 1
        ');
        $st->execute([':id' => $idclien]);
        $r = $st->fetch();
        if ($r) {
            $r['condicion_iva'] = self::normalizeCondIva($r['condicion_iva'] ?? null);
            $r['categoria'] = self::normalizeCategoria($r['categoria'] ?? null);
            $r['precio_mayorista'] = (int)($r['precio_mayorista'] ?? 0);
        }
        return $r ?: null;
    }
}
