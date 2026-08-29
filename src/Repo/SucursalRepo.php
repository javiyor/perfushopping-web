<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class SucursalRepo
{
    private static bool $puntosTableReady = false;

    private function ensurePuntosVentaTable(): void
    {
        if (self::$puntosTableReady) {
            return;
        }
        $pdo = Db::pdo();
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_sucursal_puntos_venta (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            sucursal_id INT UNSIGNED NOT NULL,
            punto_venta INT UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE KEY uq_admin_sucursal_pv (punto_venta),
            KEY idx_admin_sucursal_pv_sucursal (sucursal_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        self::$puntosTableReady = true;
    }

    private function normalizarPuntosVenta(array $puntos): array
    {
        $out = [];
        foreach ($puntos as $pv) {
            $n = (int)$pv;
            if ($n > 0) {
                $out[$n] = $n;
            }
        }
        ksort($out);
        return array_values($out);
    }

    private function syncPuntosVenta(int $sucursalId, array $puntos): void
    {
        $this->ensurePuntosVentaTable();
        $pdo = Db::pdo();
        $puntos = $this->normalizarPuntosVenta($puntos);
        if (!$puntos) {
            throw new \RuntimeException('La sucursal debe tener al menos un punto de venta.');
        }

        $placeholders = implode(',', array_fill(0, count($puntos), '?'));
        $params = $puntos;
        $params[] = $sucursalId;
        $st = $pdo->prepare("SELECT punto_venta FROM admin_sucursal_puntos_venta WHERE punto_venta IN ({$placeholders}) AND sucursal_id <> ?");
        $st->execute($params);
        $ocupados = array_map('intval', $st->fetchAll(\PDO::FETCH_COLUMN));
        if ($ocupados) {
            throw new \RuntimeException('Los puntos de venta ya están asignados a otra sucursal: ' . implode(', ', $ocupados));
        }

        $pdo->prepare('DELETE FROM admin_sucursal_puntos_venta WHERE sucursal_id = :s')->execute([':s' => $sucursalId]);
        $ins = $pdo->prepare('INSERT INTO admin_sucursal_puntos_venta (sucursal_id, punto_venta, created_at) VALUES (:s, :pv, NOW())');
        foreach ($puntos as $pv) {
            $ins->execute([':s' => $sucursalId, ':pv' => $pv]);
        }

        $pdo->prepare('UPDATE admin_sucursales SET punto_venta = :pv, updated_at = NOW() WHERE id = :id LIMIT 1')
            ->execute([':pv' => $puntos[0], ':id' => $sucursalId]);
    }

    public function puntosVentaPorSucursal(): array
    {
        $this->ensurePuntosVentaTable();
        $st = Db::pdo()->query('SELECT sucursal_id, punto_venta FROM admin_sucursal_puntos_venta ORDER BY punto_venta ASC');
        $map = [];
        foreach ($st->fetchAll() as $r) {
            $sid = (int)($r['sucursal_id'] ?? 0);
            $pv = (int)($r['punto_venta'] ?? 0);
            if ($sid > 0 && $pv > 0) {
                $map[$sid][] = $pv;
            }
        }
        return $map;
    }

    public function puntosVentaDeSucursal(int $sucursalId): array
    {
        $map = $this->puntosVentaPorSucursal();
        if (!empty($map[$sucursalId])) {
            return $map[$sucursalId];
        }
        $st = Db::pdo()->prepare('SELECT punto_venta FROM admin_sucursales WHERE id = :id LIMIT 1');
        $st->execute([':id' => $sucursalId]);
        $legacy = (int)$st->fetchColumn();
        return $legacy > 0 ? [$legacy] : [];
    }

    public function findAll(): array
    {
        $list = Db::pdo()->query('SELECT * FROM admin_sucursales ORDER BY nomsuc ASC')->fetchAll();
        $map = $this->puntosVentaPorSucursal();
        foreach ($list as &$s) {
            $sid = (int)($s['id'] ?? 0);
            $puntos = $map[$sid] ?? [];
            if (!$puntos) {
                $legacy = (int)($s['punto_venta'] ?? 0);
                if ($legacy > 0) {
                    $puntos = [$legacy];
                }
            }
            $s['puntos_venta'] = $puntos;
            $s['puntos_venta_csv'] = implode(',', $puntos);
        }
        unset($s);
        return $list;
    }

    public function save(?int $id, string $nomsuc, string $numsuc, array $puntosVenta, ?int $iddepo, int $activo, string $direccion = '', string $telefono = '', string $email = ''): int
    {
        $pdo = Db::pdo();
        $puntos = $this->normalizarPuntosVenta($puntosVenta);
        if (!$puntos) {
            throw new \RuntimeException('La sucursal debe tener al menos un punto de venta.');
        }

        $pdo->beginTransaction();
        try {
            if ($id) {
                $st = $pdo->prepare('UPDATE admin_sucursales SET nomsuc=:n, numsuc=:ns, punto_venta=:pv, iddepo=:depo, activo=:a, direccion=:dir, telefono=:tel, email=:em, updated_at=NOW() WHERE id=:id LIMIT 1');
                $st->execute([':n' => $nomsuc, ':ns' => $numsuc, ':pv' => $puntos[0], ':depo' => $iddepo, ':a' => $activo, ':dir' => $direccion, ':tel' => $telefono, ':em' => $email, ':id' => $id]);
                $sucursalId = $id;
            } else {
                $st = $pdo->prepare('INSERT INTO admin_sucursales (idsucemp, nomsuc, numsuc, punto_venta, iddepo, activo, direccion, telefono, email, created_at, updated_at) VALUES (0, :n, :ns, :pv, :depo, :a, :dir, :tel, :em, NOW(), NOW())');
                $st->execute([':n' => $nomsuc, ':ns' => $numsuc, ':pv' => $puntos[0], ':depo' => $iddepo, ':a' => $activo, ':dir' => $direccion, ':tel' => $telefono, ':em' => $email]);
                $sucursalId = (int)$pdo->lastInsertId();
            }

            $this->syncPuntosVenta((int)$sucursalId, $puntos);
            $pdo->commit();
            return (int)$sucursalId;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function listarDepositos(): array
    {
        $st = Db::pdo()->query('SELECT iddepo, nomdepo FROM deposito ORDER BY nomdepo ASC');
        return $st->fetchAll();
    }

    public function listarActivas(): array
    {
        $st = Db::pdo()->query("
            SELECT s.*
            FROM admin_sucursales s
            WHERE s.activo = 1 AND s.nomsuc NOT LIKE '%Roca%'
            ORDER BY s.nomsuc ASC
        ");
        return $st->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('
            SELECT s.*
            FROM admin_sucursales s
            WHERE s.id = :id LIMIT 1
        ');
        $st->execute([':id' => $id]);
        $row = $st->fetch() ?: null;
        if (!$row) {
            return null;
        }
        $puntos = $this->puntosVentaDeSucursal((int)$row['id']);
        $row['puntos_venta'] = $puntos;
        $row['puntos_venta_csv'] = implode(',', $puntos);
        return $row;
    }

    public function updatePuntoVenta(int $id, int $puntoVenta): void
    {
        $this->syncPuntosVenta($id, [$puntoVenta]);
    }

    public function vendedoresDisponibles(): array
    {
        $st = Db::pdo()->query("
            SELECT id, nombre, username, rol
            FROM admin_users
            WHERE activo = 1 AND (rol = 'superadmin' OR rol = 'ventas')
            ORDER BY nombre ASC
        ");
        return $st->fetchAll();
    }
}
