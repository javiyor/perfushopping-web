<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Repo\AdminUserRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class AdminAuthService
{
    private static array $permisosPorRol = [
        'superadmin' => ['*'],
        'ventas' => ['productos', 'clientes', 'facturacion', 'presupuestos', 'remitos', 'recibos', 'cta_cte'],
        'administracion' => ['arca', 'estadisticas', 'pagos', 'cheques', 'usuarios_admin'],
        'compras' => ['compras', 'pagos_proveedores'],
        'caja' => ['caja_movimientos'],
    ];

    public function user(): ?array
    {
        $id = $_SESSION['admin_user_id'] ?? null;
        if (!is_int($id) && !is_string($id)) {
            return null;
        }
        $id = (int)$id;
        if ($id <= 0) {
            return null;
        }
        return (new AdminUserRepo())->findById($id);
    }

    public function requireLogin(): array
    {
        $u = $this->user();
        if (!$u) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Inicia sesion para continuar.'];
            Response::redirect('/admin/login');
        }
        if (empty($u['activo'])) {
            unset($_SESSION['admin_user_id']);
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario desactivado.'];
            Response::redirect('/admin/login');
        }
        return $u;
    }

    public function requireSesion(): array
    {
        $u = $this->requireLogin();
        if (!$this->hasSesion()) {
            $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Seleccioná sucursal y turno para empezar.'];
            Response::redirect('/admin/sesion/iniciar');
        }
        return $u;
    }

    public function requireRol(string ...$roles): array
    {
        $u = $this->requireSesion();
        $rol = (string)($u['rol'] ?? '');
        if ($rol !== 'superadmin' && !in_array($rol, $roles, true)) {
            Response::html(View::render('errors/403.php', ['message' => 'No tenes permisos para acceder a esta seccion.']), 403);
            exit;
        }
        return $u;
    }

    public function checkPermiso(string $permiso): bool
    {
        $u = $this->user();
        if (!$u) {
            return false;
        }
        $rol = (string)($u['rol'] ?? '');
        $custom = (string)($u['permisos'] ?? '');
        if ($custom !== '') {
            $userPerms = json_decode($custom, true);
            if (is_array($userPerms)) {
                return in_array('*', $userPerms, true) || in_array($permiso, $userPerms, true);
            }
        }
        $rolePerms = self::$permisosPorRol[$rol] ?? [];
        return in_array('*', $rolePerms, true) || in_array($permiso, $rolePerms, true);
    }

    public function getPermisosDelRol(string $rol): array
    {
        return self::$permisosPorRol[$rol] ?? [];
    }

    public function login(string $username, string $password): ?array
    {
        $repo = new AdminUserRepo();
        $u = $repo->findByUsername($username);
        if (!$u || empty($u['activo'])) {
            return null;
        }
        if (!password_verify($password, (string)($u['password_hash'] ?? ''))) {
            return null;
        }
        if (password_needs_rehash((string)($u['password_hash'] ?? ''), PASSWORD_DEFAULT)) {
            $repo->updatePassword((int)$u['id'], password_hash($password, PASSWORD_DEFAULT));
        }
        $_SESSION['admin_user_id'] = (int)$u['id'];
        $repo->touchLogin((int)$u['id']);
        return $u;
    }

    public function logout(): void
    {
        session_unset();
        session_destroy();
    }

    // ── Sesion (sucursal + turno) ──

    public function hasSesion(): bool
    {
        return isset($_SESSION['admin_sucursal_id']) && $_SESSION['admin_sucursal_id'] > 0
            && isset($_SESSION['admin_turno']);
    }

    public function iniciarSesion(int $sucursalId, string $turno, array $vendedores): void
    {
        $_SESSION['admin_sucursal_id'] = $sucursalId;
        $_SESSION['admin_turno'] = $turno;
        $_SESSION['admin_vendedores'] = $vendedores;
    }

    public function cerrarSesion(): void
    {
        unset($_SESSION['admin_sucursal_id']);
        unset($_SESSION['admin_turno']);
        unset($_SESSION['admin_vendedores']);
    }

    public function getSucursalId(): int
    {
        return (int)($_SESSION['admin_sucursal_id'] ?? 0);
    }

    public function getTurno(): string
    {
        return (string)($_SESSION['admin_turno'] ?? '');
    }

    public function getVendedores(): array
    {
        return (array)($_SESSION['admin_vendedores'] ?? []);
    }

    public function getPuntoVenta(): int
    {
        if (isset($_SESSION['admin_punto_venta'])) {
            return (int)$_SESSION['admin_punto_venta'];
        }
        $sid = $this->getSucursalId();
        if ($sid <= 0) return 1;
        $repo = new \Perfushopping\Web\Repo\SucursalRepo();
        $s = $repo->findById($sid);
        $pv = $s ? (int)($s['punto_venta'] ?? 1) : 1;
        $_SESSION['admin_punto_venta'] = $pv;
        return $pv;
    }

    public function getDepositoId(): int
    {
        if (isset($_SESSION['admin_deposito_id'])) {
            return (int)$_SESSION['admin_deposito_id'];
        }
        $sid = $this->getSucursalId();
        if ($sid <= 0) return 0;
        $repo = new \Perfushopping\Web\Repo\SucursalRepo();
        $s = $repo->findById($sid);
        $depo = $s ? (int)($s['iddepo'] ?? 0) : 0;
        $_SESSION['admin_deposito_id'] = $depo;
        return $depo;
    }
}
