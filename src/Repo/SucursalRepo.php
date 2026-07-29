<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class SucursalRepo
{
    public function findAll(): array
    {
        $st = Db::pdo()->query('SELECT * FROM admin_sucursales ORDER BY nomsuc ASC');
        return $st->fetchAll();
    }

    public function save(?int $id, string $nomsuc, string $numsuc, int $puntoVenta, ?int $iddepo, int $activo, string $direccion = '', string $telefono = '', string $email = ''): int
    {
        $pdo = Db::pdo();
        if ($id) {
            $st = $pdo->prepare('UPDATE admin_sucursales SET nomsuc=:n, numsuc=:ns, punto_venta=:pv, iddepo=:depo, activo=:a, direccion=:dir, telefono=:tel, email=:em, updated_at=NOW() WHERE id=:id LIMIT 1');
            $st->execute([':n' => $nomsuc, ':ns' => $numsuc, ':pv' => $puntoVenta, ':depo' => $iddepo, ':a' => $activo, ':dir' => $direccion, ':tel' => $telefono, ':em' => $email, ':id' => $id]);
            return $id;
        }
        $st = $pdo->prepare('INSERT INTO admin_sucursales (idsucemp, nomsuc, numsuc, punto_venta, iddepo, activo, direccion, telefono, email, created_at, updated_at) VALUES (0, :n, :ns, :pv, :depo, :a, :dir, :tel, :em, NOW(), NOW())');
        $st->execute([':n' => $nomsuc, ':ns' => $numsuc, ':pv' => $puntoVenta, ':depo' => $iddepo, ':a' => $activo, ':dir' => $direccion, ':tel' => $telefono, ':em' => $email]);
        return (int)$pdo->lastInsertId();
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
