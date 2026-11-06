<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class EmpleadoRepo
{
    private static bool $asistenciaTableReady = false;

    private function ensureAsistenciaTable(): void
    {
        if (self::$asistenciaTableReady) return;
        Db::pdo()->exec("CREATE TABLE IF NOT EXISTS empleado_asistencia_turnos (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            admin_user_id INT UNSIGNED NOT NULL,
            sucursal_id INT UNSIGNED NOT NULL,
            turno VARCHAR(10) NOT NULL,
            fecha DATE NOT NULL,
            horas DECIMAL(5,2) NOT NULL DEFAULT 4.00,
            created_by INT UNSIGNED DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_empleado_asistencia_turno (admin_user_id, fecha, turno),
            KEY idx_empleado_asistencia_fecha (fecha),
            KEY idx_empleado_asistencia_user (admin_user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$asistenciaTableReady = true;
    }

    /** Persiste como 4 horas cada turno en el que el vendedor fue marcado presente. */
    public function registrarAsistenciaTurno(array $adminUserIds, int $sucursalId, string $turno, string $fecha, int $createdBy): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $adminUserIds), static fn(int $id): bool => $id > 0)));
        if (!$ids || $sucursalId <= 0 || !in_array($turno, ['manana', 'tarde'], true) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            return 0;
        }
        $this->ensureAsistenciaTable();
        $st = Db::pdo()->prepare('INSERT IGNORE INTO empleado_asistencia_turnos
            (admin_user_id, sucursal_id, turno, fecha, horas, created_by, created_at)
            VALUES (:uid, :sucursal, :turno, :fecha, 4.00, :created_by, NOW())');
        $registradas = 0;
        foreach ($ids as $id) {
            $st->execute([
                ':uid' => $id,
                ':sucursal' => $sucursalId,
                ':turno' => $turno,
                ':fecha' => $fecha,
                ':created_by' => $createdBy > 0 ? $createdBy : null,
            ]);
            $registradas += $st->rowCount();
        }
        return $registradas;
    }

    public function turnosPresentesMes(int $adminUserId, string $periodo): array
    {
        $this->ensureAsistenciaTable();
        $st = Db::pdo()->prepare("SELECT et.*, s.nomsuc AS sucursal_nombre
            FROM empleado_asistencia_turnos et
            LEFT JOIN admin_sucursales s ON s.id = et.sucursal_id
            WHERE et.admin_user_id = :uid AND DATE_FORMAT(et.fecha, '%Y-%m') = :periodo
            ORDER BY et.fecha ASC, et.turno ASC");
        $st->execute([':uid' => $adminUserId, ':periodo' => $periodo]);
        return $st->fetchAll();
    }

    public function horasTurnosMes(int $adminUserId, string $periodo): float
    {
        return (float)$this->resumenHorasMes($adminUserId, $periodo)['horas_turnos'];
    }

    /** Horas manuales por fecha prevalecen sobre el cómputo automático de turnos de ese día. */
    public function resumenHorasMes(int $adminUserId, string $periodo): array
    {
        $horasManualesPorFecha = [];
        foreach ($this->horasDelMes($adminUserId, $periodo) as $registro) {
            $fecha = (string)($registro['fecha'] ?? '');
            if ($fecha !== '') {
                $horasManualesPorFecha[$fecha] = ($horasManualesPorFecha[$fecha] ?? 0.0) + (float)($registro['horas'] ?? 0);
            }
        }

        $horasTurnos = 0.0;
        $turnosIncluidos = 0;
        foreach ($this->turnosPresentesMes($adminUserId, $periodo) as $turno) {
            $fecha = (string)($turno['fecha'] ?? '');
            if ($fecha === '' || isset($horasManualesPorFecha[$fecha])) continue;
            $horasTurnos += (float)($turno['horas'] ?? 4);
            $turnosIncluidos++;
        }

        $manuales = array_sum($horasManualesPorFecha);
        return [
            'horas_manuales' => (float)$manuales,
            'horas_turnos' => $horasTurnos,
            'total_horas' => (float)($manuales + $horasTurnos),
            'turnos_incluidos' => $turnosIncluidos,
        ];
    }

    public function listConfig(): array
    {
        return Db::pdo()->query('
            SELECT ec.*, a.nombre, a.username, a.rol, a.email, a.activo AS user_activo
            FROM empleado_config ec
            INNER JOIN admin_users a ON a.id = ec.admin_user_id
            ORDER BY a.nombre ASC
        ')->fetchAll();
    }

    public function listVendedoresDisponibles(): array
    {
        return Db::pdo()->query("
            SELECT a.id, a.nombre, a.username, a.rol
            FROM admin_users a
            WHERE a.activo = 1 AND a.rol IN ('ventas','superadmin')
            ORDER BY a.nombre ASC
        ")->fetchAll();
    }

    public function findConfig(int $adminUserId): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT ec.*, a.nombre, a.username, a.rol, a.email, a.activo AS user_activo
            FROM empleado_config ec
            INNER JOIN admin_users a ON a.id = ec.admin_user_id
            WHERE ec.admin_user_id = :id
            LIMIT 1
        ');
        $st->execute([':id' => $adminUserId]);
        return $st->fetch() ?: null;
    }

    public function findVendedor(int $adminUserId): ?array
    {
        $st = Db::pdo()->prepare("
            SELECT id, nombre, username, rol
            FROM admin_users
            WHERE id = :id AND activo = 1 AND rol IN ('ventas','superadmin')
            LIMIT 1
        ");
        $st->execute([':id' => $adminUserId]);
        return $st->fetch() ?: null;
    }

    public function saveConfig(int $adminUserId, array $data): void
    {
        $pdo = Db::pdo();
        $st = $pdo->prepare('
            INSERT INTO empleado_config (admin_user_id, tipo, sueldo_base_cents, valor_hora_cents, cuil, banco, cbu, activo, created_at, updated_at)
            VALUES (:uid, :tipo, :sueldo, :hora, :cuil, :banco, :cbu, :activo, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                tipo = VALUES(tipo),
                sueldo_base_cents = VALUES(sueldo_base_cents),
                valor_hora_cents = VALUES(valor_hora_cents),
                cuil = VALUES(cuil),
                banco = VALUES(banco),
                cbu = VALUES(cbu),
                activo = VALUES(activo),
                updated_at = NOW()
        ');
        $st->execute([
            ':uid' => $adminUserId,
            ':tipo' => $data['tipo'],
            ':sueldo' => (int)($data['sueldo_base_cents'] ?? 0),
            ':hora' => (int)($data['valor_hora_cents'] ?? 0),
            ':cuil' => $data['cuil'] ?? null,
            ':banco' => $data['banco'] ?? null,
            ':cbu' => $data['cbu'] ?? null,
            ':activo' => (int)($data['activo'] ?? 1),
        ]);
    }

    public function getComisiones(int $adminUserId): array
    {
        $st = Db::pdo()->prepare('
            SELECT ec.*, s.nomsub
            FROM empleado_comisiones ec
            LEFT JOIN subrubro s ON s.codsub = ec.codsub
            WHERE ec.admin_user_id = :uid
            ORDER BY s.nomsub ASC
        ');
        $st->execute([':uid' => $adminUserId]);
        return $st->fetchAll();
    }

    public function saveComision(int $adminUserId, int $codsub, float $porcentaje): void
    {
        $st = Db::pdo()->prepare('
            INSERT INTO empleado_comisiones (admin_user_id, codsub, porcentaje, created_at)
            VALUES (:uid, :codsub, :pct, NOW())
            ON DUPLICATE KEY UPDATE porcentaje = VALUES(porcentaje)
        ');
        $st->execute([':uid' => $adminUserId, ':codsub' => $codsub, ':pct' => $porcentaje]);
    }

    public function deleteComision(int $adminUserId, int $codsub): void
    {
        $st = Db::pdo()->prepare('DELETE FROM empleado_comisiones WHERE admin_user_id = :uid AND codsub = :codsub LIMIT 1');
        $st->execute([':uid' => $adminUserId, ':codsub' => $codsub]);
    }

    /** Copia las comisiones del empleado a todos los demás empleados activos (ventas/superadmin). Devuelve a cuántos. */
    public function compartirComisiones(int $sourceUserId): int
    {
        $pdo = Db::pdo();
        try {
            $pdo->beginTransaction();

            $st = $pdo->prepare("
                DELETE ec FROM empleado_comisiones ec
                INNER JOIN admin_users a ON a.id = ec.admin_user_id
                WHERE a.activo = 1 AND a.rol IN ('ventas','superadmin') AND ec.admin_user_id <> :src
            ");
            $st->execute([':src' => $sourceUserId]);

            $st = $pdo->prepare("
                INSERT INTO empleado_comisiones (admin_user_id, codsub, porcentaje, created_at)
                SELECT a.id, src.codsub, src.porcentaje, NOW()
                FROM empleado_comisiones src
                CROSS JOIN admin_users a
                WHERE src.admin_user_id = :src
                  AND a.activo = 1 AND a.rol IN ('ventas','superadmin') AND a.id <> :src
                ON DUPLICATE KEY UPDATE porcentaje = VALUES(porcentaje)
            ");
            $st->execute([':src' => $sourceUserId]);

            $st = $pdo->prepare("
                SELECT COUNT(DISTINCT ec.admin_user_id)
                FROM empleado_comisiones ec
                INNER JOIN admin_users a ON a.id = ec.admin_user_id
                WHERE a.activo = 1 AND a.rol IN ('ventas','superadmin') AND ec.admin_user_id <> :src
            ");
            $st->execute([':src' => $sourceUserId]);
            $empleados = (int)$st->fetchColumn();

            $pdo->commit();
            return $empleados;
        } catch (\Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Comisiones ganadas por marca y total por empleado en un periodo (YYYY-MM).
     * Considera facturas emitidas aunque todavía no tengan CAE.
     * Si $soloUid viene informado, devuelve solo ese empleado.
     */
    public function comisionesGanadas(string $periodo, ?int $soloUid = null): array
    {
        $pdo = Db::pdo();

        $sql = "
            SELECT ec.admin_user_id, a.nombre, ec.tipo
            FROM empleado_config ec
            INNER JOIN admin_users a ON a.id = ec.admin_user_id
            WHERE a.activo = 1 AND a.rol IN ('ventas','superadmin')
        ";
        $params = [];
        if ($soloUid !== null && $soloUid > 0) {
            $sql .= ' AND ec.admin_user_id = :uidEmp';
            $params[':uidEmp'] = $soloUid;
        }
        $sql .= ' ORDER BY a.nombre ASC';
        $st = $pdo->prepare($sql);
        $st->execute($params);

        $empleados = [];
        foreach ($st->fetchAll() as $r) {
            $uid = (int)$r['admin_user_id'];
            $empleados[$uid] = [
                'admin_user_id' => $uid,
                'nombre' => (string)$r['nombre'],
                'tipo' => (string)$r['tipo'],
                'marcas' => [],
                'total_cents' => 0,
            ];
        }

        $sql = "
            SELECT ec.admin_user_id, ec.codsub, ec.porcentaje, s.nomsub
            FROM empleado_comisiones ec
            INNER JOIN empleado_config cfg ON cfg.admin_user_id = ec.admin_user_id
            LEFT JOIN subrubro s ON s.codsub = ec.codsub
        ";
        $params = [];
        if ($soloUid !== null && $soloUid > 0) {
            $sql .= ' WHERE ec.admin_user_id = :uidCom';
            $params[':uidCom'] = $soloUid;
        }
        $sql .= ' ORDER BY ec.admin_user_id, s.nomsub ASC';
        $st = $pdo->prepare($sql);
        $st->execute($params);

        $seed = [];
        foreach ($st->fetchAll() as $r) {
            $uid = (int)$r['admin_user_id'];
            if (!isset($empleados[$uid])) {
                continue;
            }
            $cod = (int)$r['codsub'];
            $pct = (float)$r['porcentaje'];
            $seed[$uid][$cod] = count($empleados[$uid]['marcas']);
            $empleados[$uid]['marcas'][] = [
                'codsub' => $cod,
                'marca' => $r['nomsub'] !== null && $r['nomsub'] !== '' ? (string)$r['nomsub'] : ('Marca #' . $cod),
                'porcentaje' => $pct,
                'ventas_cents' => 0,
                'comision_cents' => 0,
            ];
        }

        $sql = "
            SELECT f.vendedor_id, COALESCE(p.codsub, 0) AS codsub,
                   CASE WHEN f.tipo_comprobante = 'NC' THEN -fi.total_cents ELSE fi.total_cents END AS total_cents
            FROM factura_items fi
            INNER JOIN facturas f ON f.id = fi.factura_id
            LEFT JOIN producto p ON p.idprodu = fi.idprodu
            WHERE f.estado = 'emitida'
              AND f.vendedor_id IS NOT NULL
              AND DATE_FORMAT(f.fecha, '%Y-%m') = :periodo
        ";
        $params = [':periodo' => $periodo];
        if ($soloUid !== null && $soloUid > 0) {
            $sql .= ' AND f.vendedor_id = :uidVent';
            $params[':uidVent'] = $soloUid;
        }
        $st = $pdo->prepare($sql);
        $st->execute($params);

        foreach ($st->fetchAll() as $r) {
            $uid = (int)$r['vendedor_id'];
            $cod = (int)$r['codsub'];
            if (!isset($seed[$uid][$cod])) {
                continue;
            }
            $total = (int)$r['total_cents'];
            $row = &$empleados[$uid]['marcas'][$seed[$uid][$cod]];
            $row['ventas_cents'] += $total;
            $row['comision_cents'] += (int)round($total * $row['porcentaje'] / 100);
            unset($row);
        }

        foreach ($empleados as $uid => $emp) {
            $totalEmp = 0;
            foreach ($emp['marcas'] as $m) {
                $totalEmp += (int)$m['comision_cents'];
            }
            $empleados[$uid]['total_cents'] = $totalEmp;
        }

        return array_values($empleados);
    }

    public function listMarcas(): array
    {
        return Db::pdo()->query('SELECT codsub, nomsub FROM subrubro ORDER BY nomsub ASC')->fetchAll();
    }

    public function horasDelMes(int $adminUserId, string $periodo): array
    {
        $st = Db::pdo()->prepare("
            SELECT * FROM empleado_horas
            WHERE admin_user_id = :uid AND DATE_FORMAT(fecha, '%Y-%m') = :periodo
            ORDER BY fecha ASC
        ");
        $st->execute([':uid' => $adminUserId, ':periodo' => $periodo]);
        return $st->fetchAll();
    }

    public function horasManualesMes(int $adminUserId, string $periodo): float
    {
        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(horas), 0) FROM empleado_horas
            WHERE admin_user_id = :uid AND DATE_FORMAT(fecha, '%Y-%m') = :periodo
        ");
        $st->execute([':uid' => $adminUserId, ':periodo' => $periodo]);
        return (float)$st->fetchColumn();
    }

    public function totalHorasMes(int $adminUserId, string $periodo): float
    {
        return (float)$this->resumenHorasMes($adminUserId, $periodo)['total_horas'];
    }

    public function guardarHoras(int $adminUserId, string $fecha, float $horas, string $concepto, int $createdBy): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO empleado_horas (admin_user_id, fecha, horas, concepto, created_by, created_at)
            VALUES (:uid, :fecha, :horas, :concepto, :cb, NOW())
        ');
        $st->execute([
            ':uid' => $adminUserId,
            ':fecha' => $fecha,
            ':horas' => $horas,
            ':concepto' => $concepto ?: null,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function ventasDelMes(int $vendedorId, string $periodo): array
    {
        $st = Db::pdo()->prepare("
            SELECT fi.*, p.codsub, f.tipo_comprobante
            FROM factura_items fi
            INNER JOIN facturas f ON f.id = fi.factura_id
            LEFT JOIN producto p ON p.idprodu = fi.idprodu
            WHERE f.vendedor_id = :vid
              AND f.estado = 'emitida'
              AND DATE_FORMAT(f.fecha, '%Y-%m') = :periodo
            ORDER BY f.fecha ASC
        ");
        $st->execute([':vid' => $vendedorId, ':periodo' => $periodo]);
        return $st->fetchAll();
    }

    public function comisionesDelVendedor(int $vendedorId): array
    {
        $st = Db::pdo()->prepare('
            SELECT ec.codsub, ec.porcentaje
            FROM empleado_comisiones ec
            WHERE ec.admin_user_id = :uid
        ');
        $st->execute([':uid' => $vendedorId]);
        $result = [];
        foreach ($st->fetchAll() as $row) {
            $result[(int)$row['codsub']] = (float)$row['porcentaje'];
        }
        return $result;
    }

    public function calcularLiquidacion(int $vendedorId, string $periodo): array
    {
        $config = $this->findConfig($vendedorId);
        if (!$config) {
            throw new \RuntimeException('El empleado no tiene configuración salarial.');
        }

        $sueldoBase = (int)$config['sueldo_base_cents'];
        $valorHora = (int)$config['valor_hora_cents'];
        $resumenHoras = $this->resumenHorasMes($vendedorId, $periodo);
        $horasManuales = $resumenHoras['horas_manuales'];
        $horasTurnos = $resumenHoras['horas_turnos'];
        $totalHoras = $resumenHoras['total_horas'];
        $tipo = (string)$config['tipo'];
        $sueldoBaseAplicado = in_array($tipo, ['fijo', 'mixto'], true) ? $sueldoBase : 0;
        $horasCents = in_array($tipo, ['horas', 'mixto'], true) ? (int)round($totalHoras * $valorHora) : 0;

        $comisionCents = 0;
        $comisiones = $this->comisionesDelVendedor($vendedorId);
        if ($comisiones) {
            $items = $this->ventasDelMes($vendedorId, $periodo);
            foreach ($items as $it) {
                $codsub = (int)($it['codsub'] ?? 0);
                $pct = $comisiones[$codsub] ?? 0;
                if ($pct > 0) {
                    $importeVenta = (int)$it['total_cents'];
                    if ((string)($it['tipo_comprobante'] ?? '') === 'NC') $importeVenta = -$importeVenta;
                    $comisionCents += (int)round($importeVenta * $pct / 100);
                }
            }
        }

        $total = $sueldoBaseAplicado + $horasCents + $comisionCents;

        return [
            'admin_user_id' => $vendedorId,
            'periodo' => $periodo,
            'tipo' => $tipo,
            'sueldo_base_cents' => $sueldoBaseAplicado,
            'horas_cents' => $horasCents,
            'comision_cents' => $comisionCents,
            'total_cents' => $total,
            'horas_manuales' => $horasManuales,
            'horas_turnos' => $horasTurnos,
            'total_horas' => $totalHoras,
            'turnos_presentes' => $resumenHoras['turnos_incluidos'],
            'valor_hora_cents' => $valorHora,
            'detalle' => json_encode([
                'total_horas' => $totalHoras,
                'horas_manuales' => $horasManuales,
                'horas_turnos' => $horasTurnos,
                'turnos_incluidos' => $resumenHoras['turnos_incluidos'],
                'comisiones_aplicadas' => $comisiones,
            ]),
        ];
    }

    public function guardarLiquidacion(array $liq, int $createdBy): int
    {
        $st = Db::pdo()->prepare('
            INSERT INTO empleado_liquidacion (admin_user_id, periodo, sueldo_base_cents, horas_cents, comision_cents, total_cents, estado, detalle, created_by, created_at)
            VALUES (:uid, :periodo, :sueldo, :horas, :comision, :total, :estado, :detalle, :cb, NOW())
        ');
        $st->execute([
            ':uid' => $liq['admin_user_id'],
            ':periodo' => $liq['periodo'],
            ':sueldo' => $liq['sueldo_base_cents'],
            ':horas' => $liq['horas_cents'],
            ':comision' => $liq['comision_cents'],
            ':total' => $liq['total_cents'],
            ':estado' => 'calculada',
            ':detalle' => $liq['detalle'] ?? null,
            ':cb' => $createdBy,
        ]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function listLiquidaciones(string $periodo = '', int $adminUserId = 0): array
    {
        $sql = '
            SELECT el.*, a.nombre, a.username
            FROM empleado_liquidacion el
            INNER JOIN admin_users a ON a.id = el.admin_user_id
        ';
        $params = [];
        $wheres = [];

        if ($periodo !== '') {
            $wheres[] = 'el.periodo = :periodo';
            $params[':periodo'] = $periodo;
        }
        if ($adminUserId > 0) {
            $wheres[] = 'el.admin_user_id = :uid';
            $params[':uid'] = $adminUserId;
        }

        if ($wheres) {
            $sql .= ' WHERE ' . implode(' AND ', $wheres);
        }
        $sql .= ' ORDER BY el.created_at DESC';

        $st = Db::pdo()->prepare($sql);
        $st->execute($params);
        return $st->fetchAll();
    }

    public function findLiquidacion(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT el.*, a.nombre, a.username
            FROM empleado_liquidacion el
            INNER JOIN admin_users a ON a.id = el.admin_user_id
            WHERE el.id = :id LIMIT 1
        ');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    public function marcarPagada(int $id, int $userId): void
    {
        $st = Db::pdo()->prepare("UPDATE empleado_liquidacion SET estado = 'pagada', pagada_at = NOW() WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
    }

    public function anularLiquidacion(int $id): void
    {
        $st = Db::pdo()->prepare("UPDATE empleado_liquidacion SET estado = 'anulada' WHERE id = :id LIMIT 1");
        $st->execute([':id' => $id]);
    }

    public function facturasVendedorMes(int $vendedorId, string $periodo): array
    {
        $st = Db::pdo()->prepare("
            SELECT f.id, f.codigo, f.tipo_comprobante, f.fecha, f.total_cents, f.cliente_nombre, f.cae
            FROM facturas f
            WHERE f.vendedor_id = :vid
              AND f.estado = 'emitida'
              AND DATE_FORMAT(f.fecha, '%Y-%m') = :periodo
            ORDER BY f.fecha DESC
        ");
        $st->execute([':vid' => $vendedorId, ':periodo' => $periodo]);
        return $st->fetchAll();
    }
}
