<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\ProveedorRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ProveedorController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $list = (new ProveedorRepo())->findAll();

        echo View::adminPage('admin/proveedores/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Proveedores',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $codprove = trim((string)($_POST['codprove'] ?? ''));
        $razon = trim((string)($_POST['razon'] ?? ''));
        $cuit = trim((string)($_POST['cuit'] ?? ''));
        $direc = trim((string)($_POST['direc'] ?? ''));
        $tele = trim((string)($_POST['tele'] ?? ''));
        $codpost = trim((string)($_POST['codpost'] ?? ''));
        $mail = trim((string)($_POST['mail'] ?? ''));
        $localidad = trim((string)($_POST['localidad'] ?? ''));
        $activo = isset($_POST['activo']) ? 1 : 0;

        if ($codprove === '' || $razon === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Código de proveedor y razón social son obligatorios.'];
            Response::redirect('/admin/proveedores');
        }

        $repo = new ProveedorRepo();

        if ($id > 0) {
            $repo->update($id, $codprove, $razon, $cuit, $direc, $tele, $codpost, $mail, $localidad, $activo);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Proveedor actualizado.'];
        } else {
            $existing = $repo->findByCodprove($codprove);
            if ($existing) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Ya existe un proveedor con ese código.'];
                Response::redirect('/admin/proveedores');
            }
            $repo->create($codprove, $razon, $cuit, $direc, $tele, $codpost, $mail, $localidad);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Proveedor creado.'];
        }

        Response::redirect('/admin/proveedores');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'ID inválido.'];
            Response::redirect('/admin/proveedores');
        }

        $repo = new ProveedorRepo();
        $repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Proveedor eliminado. Productos desvinculados.'];
        Response::redirect('/admin/proveedores');
    }
}
