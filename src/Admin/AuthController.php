<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\SucursalRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class AuthController
{
    public function loginForm(array $params): void
    {
        $auth = new AdminAuthService();
        if ($auth->user()) {
            if ($auth->hasSesion()) {
                Response::redirect('/admin');
            } else {
                Response::redirect('/admin/sesion/iniciar');
            }
        }

        $sucursales = (new SucursalRepo())->listarActivas();

        echo View::adminPage('admin/auth/login.php', [
            'csrf' => Csrf::token(),
            'sucursales' => $sucursales,
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Iniciar sesión',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function login(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $username = trim((string)($_POST['username'] ?? ''));
        $password = (string)($_POST['password'] ?? '');
        $sucursalId = (int)($_POST['sucursal_id'] ?? 0);

        if ($username === '' || $password === '' || $sucursalId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá usuario, clave y sucursal.'];
            Response::redirect('/admin/login');
        }

        $auth = new AdminAuthService();
        $u = $auth->login($username, $password);
        if (!$u) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario o clave incorrectos.'];
            Response::redirect('/admin/login');
        }

        // Save chosen sucursal in session
        $sucursal = (new SucursalRepo())->findById($sucursalId);
        if (!$sucursal) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Sucursal inválida.'];
            $auth->logout();
            Response::redirect('/admin/login');
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Bienvenido, ' . htmlspecialchars($u['nombre'] ?? '') . '.'];
        Response::redirect('/admin/sesion/iniciar');
    }

    public function logout(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);
        (new AdminAuthService())->logout();
        $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Sesión cerrada.'];
        Response::redirect('/admin/login');
    }
}
