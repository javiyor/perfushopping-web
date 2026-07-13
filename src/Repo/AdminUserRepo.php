<?php
declare(strict_types=1);

namespace Perfushopping\Web\Repo;

use Perfushopping\Web\Infra\Db;

final class AdminUserRepo
{
    public function findByUsername(string $username): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM admin_users WHERE username = :u LIMIT 1');
        $st->execute([':u' => $username]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findById(int $id): ?array
    {
        $st = Db::pdo()->prepare('SELECT * FROM admin_users WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
        $r = $st->fetch();
        return $r ?: null;
    }

    public function findAll(int $limit = 100): array
    {
        $st = Db::pdo()->prepare('SELECT * FROM admin_users ORDER BY rol ASC, nombre ASC LIMIT ' . max(1, min(200, $limit)));
        $st->execute();
        return $st->fetchAll();
    }

    public function create(string $username, string $hash, string $nombre, string $email, string $rol, string $permisos = ''): int
    {
        $st = Db::pdo()->prepare('INSERT INTO admin_users (username, password_hash, nombre, email, rol, permisos, activo, created_at, updated_at) VALUES (:u, :p, :n, :e, :r, :perm, 1, NOW(), NOW())');
        $st->execute([':u' => $username, ':p' => $hash, ':n' => $nombre, ':e' => $email, ':r' => $rol, ':perm' => $permisos]);
        return (int)Db::pdo()->lastInsertId();
    }

    public function update(int $id, string $nombre, string $email, string $rol, int $activo, string $permisos = ''): void
    {
        $st = Db::pdo()->prepare('UPDATE admin_users SET nombre = :n, email = :e, rol = :r, permisos = :perm, activo = :a, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':n' => $nombre, ':e' => $email, ':r' => $rol, ':perm' => $permisos, ':a' => $activo, ':i' => $id]);
    }

    public function updatePassword(int $id, string $hash): void
    {
        $st = Db::pdo()->prepare('UPDATE admin_users SET password_hash = :p, updated_at = NOW() WHERE id = :i LIMIT 1');
        $st->execute([':p' => $hash, ':i' => $id]);
    }

    public function touchLogin(int $id): void
    {
        $st = Db::pdo()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = :i');
        $st->execute([':i' => $id]);
    }

    public function delete(int $id): void
    {
        $st = Db::pdo()->prepare('DELETE FROM admin_users WHERE id = :i LIMIT 1');
        $st->execute([':i' => $id]);
    }

    public static function rolOptions(): array
    {
        return [
            'superadmin' => 'Super Admin',
            'ventas' => 'Ventas',
            'administracion' => 'Administración',
            'compras' => 'Compras',
            'caja' => 'Caja',
        ];
    }

    public static function permissionOptions(): array
    {
        return [
            'productos' => 'Productos',
            'clientes' => 'Clientes',
            'facturacion' => 'Facturación',
            'presupuestos' => 'Presupuestos',
            'remitos' => 'Remitos',
            'recibos' => 'Recibos',
            'cta_cte' => 'Cta. Cte.',
            'arca' => 'ARCA',
            'estadisticas' => 'Reportes',
            'pagos' => 'Pagos',
            'cheques' => 'Cheques',
            'usuarios_admin' => 'Usuarios Admin',
            'compras' => 'Compras',
            'pagos_proveedores' => 'Pagos Proveedores',
            'caja_movimientos' => 'Caja',
        ];
    }
}
