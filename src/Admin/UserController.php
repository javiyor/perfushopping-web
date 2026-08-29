<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\AdminUserRepo;
use Perfushopping\Web\Repo\SucursalRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class UserController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin', 'administracion');

        $list = (new AdminUserRepo())->findAll();
        $sucursales = (new SucursalRepo())->listarActivas();

        echo View::adminPage('admin/usuarios/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Usuarios admin',
            'permOptions' => AdminUserRepo::permissionOptions(),
            'rolPermisos' => $auth->getPermisosDelRol(''),
            'sucursales' => $sucursales,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin', 'administracion');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $username = trim((string)($_POST['username'] ?? ''));
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $rol = trim((string)($_POST['rol'] ?? 'ventas'));
        $sucursalTrabajoId = (int)($_POST['sucursal_trabajo_id'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $password = (string)($_POST['password'] ?? '');

        $permKeys = array_keys(AdminUserRepo::permissionOptions());
        $selectedPerms = [];
        foreach ($permKeys as $pk) {
            if (isset($_POST['perm_' . $pk])) {
                $selectedPerms[] = $pk;
            }
        }
        $permisos = $selectedPerms ? json_encode($selectedPerms, JSON_UNESCAPED_UNICODE) : '';

        $rolesValidos = array_keys(AdminUserRepo::rolOptions());
        if ($nombre === '' || $username === '' || !in_array($rol, $rolesValidos, true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Datos invalidos.'];
            Response::redirect('/admin/usuarios');
        }

        if ($rol !== 'superadmin') {
            if ($sucursalTrabajoId <= 0 || !(new SucursalRepo())->findById($sucursalTrabajoId)) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná la sucursal de trabajo para este usuario.'];
                Response::redirect('/admin/usuarios');
            }
        } else {
            $sucursalTrabajoId = 0;
        }

        $repo = new AdminUserRepo();

        if ($id > 0) {
            $existing = $repo->findById($id);
            if (!$existing) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
                Response::redirect('/admin/usuarios');
            }
            if ((int)$existing['id'] === (int)$adminUser['id'] && $activo === 0) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No podes desactivarte a vos mismo.'];
                Response::redirect('/admin/usuarios');
            }
            $repo->update($id, $nombre, $email, $rol, $sucursalTrabajoId > 0 ? $sucursalTrabajoId : null, $activo, $permisos);
            if ($password !== '' && strlen($password) >= 6) {
                $repo->updatePassword($id, password_hash($password, PASSWORD_DEFAULT));
            }
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Usuario admin actualizado.'];
        } else {
            $existing = (new AdminUserRepo())->findByUsername($username);
            if ($existing) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre de usuario ya existe.'];
                Response::redirect('/admin/usuarios');
            }
            $hash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $repo->create($username, $hash, $nombre, $email, $rol, $sucursalTrabajoId > 0 ? $sucursalTrabajoId : null, $permisos);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Usuario admin creado.'];
        }

        Response::redirect('/admin/usuarios');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0 || $id === (int)$adminUser['id']) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No podes eliminarte a vos mismo.'];
            Response::redirect('/admin/usuarios');
        }

        (new AdminUserRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Usuario admin eliminado.'];
        Response::redirect('/admin/usuarios');
    }
}
