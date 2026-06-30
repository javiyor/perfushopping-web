<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class SucursalRepo
{
    public function listarActivas(): array
    {
        $st = Db::pdo()->query('
            SELECT s.*
            FROM admin_sucursales s
            WHERE s.activo = 1
            ORDER BY s.nomsuc ASC
        ');
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
        return $st->fetch() ?: null;
    }

    public function updatePuntoVenta(int $id, int $puntoVenta): void
    {
        $st = Db::pdo()->prepare('UPDATE admin_sucursales SET punto_venta = :pv, updated_at = NOW() WHERE id = :id LIMIT 1');
        $st->execute([':pv' => $puntoVenta, ':id' => $id]);
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
