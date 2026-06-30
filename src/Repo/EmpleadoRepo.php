<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class EmpleadoRepo
{
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

    public function totalHorasMes(int $adminUserId, string $periodo): float
    {
        $st = Db::pdo()->prepare("
            SELECT COALESCE(SUM(horas), 0) FROM empleado_horas
            WHERE admin_user_id = :uid AND DATE_FORMAT(fecha, '%Y-%m') = :periodo
        ");
        $st->execute([':uid' => $adminUserId, ':periodo' => $periodo]);
        return (float)$st->fetchColumn();
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
            SELECT fi.*, p.codsub
            FROM factura_items fi
            INNER JOIN facturas f ON f.id = fi.factura_id
            LEFT JOIN producto p ON p.idprodu = fi.idprodu
            WHERE f.vendedor_id = :vid
              AND f.estado = 'emitida'
              AND DATE_FORMAT(f.fecha, '%Y-%m') = :periodo
              AND f.cae IS NOT NULL
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
        $totalHoras = $this->totalHorasMes($vendedorId, $periodo);
        $horasCents = (int)round($totalHoras * $valorHora);

        $comisionCents = 0;
        $comisiones = $this->comisionesDelVendedor($vendedorId);
        if ($comisiones) {
            $items = $this->ventasDelMes($vendedorId, $periodo);
            foreach ($items as $it) {
                $codsub = (int)($it['codsub'] ?? 0);
                $pct = $comisiones[$codsub] ?? 0;
                if ($pct > 0) {
                    $comisionCents += (int)round((int)$it['total_cents'] * $pct / 100);
                }
            }
        }

        $tipo = $config['tipo'];
        if ($tipo === 'fijo') {
            $total = $sueldoBase;
        } elseif ($tipo === 'horas') {
            $total = $horasCents;
        } elseif ($tipo === 'comision') {
            $total = $comisionCents;
        } else {
            $total = $sueldoBase + $horasCents + $comisionCents;
        }

        return [
            'admin_user_id' => $vendedorId,
            'periodo' => $periodo,
            'tipo' => $tipo,
            'sueldo_base_cents' => $sueldoBase,
            'horas_cents' => $horasCents,
            'comision_cents' => $comisionCents,
            'total_cents' => $total,
            'detalle' => json_encode([
                'total_horas' => $totalHoras,
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
            SELECT f.id, f.codigo, f.fecha, f.total_cents, f.cliente_nombre, f.cae
            FROM facturas f
            WHERE f.vendedor_id = :vid
              AND f.estado = 'emitida'
              AND DATE_FORMAT(f.fecha, '%Y-%m') = :periodo
              AND f.cae IS NOT NULL
            ORDER BY f.fecha DESC
        ");
        $st->execute([':vid' => $vendedorId, ':periodo' => $periodo]);
        return $st->fetchAll();
    }
}
