<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\SucursalRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class SucursalController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');

        $repo = new SucursalRepo();
        $list = $repo->findAll();
        $depositos = $repo->listarDepositos();

        echo View::adminPage('admin/sucursales/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'depositos' => $depositos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Sucursales',
        ]);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0) ?: null;
        $nomsuc = trim((string)($_POST['nomsuc'] ?? ''));
        $numsuc = trim((string)($_POST['numsuc'] ?? ''));
        $puntoVenta = (int)($_POST['punto_venta'] ?? 0);
        $iddepo = (int)($_POST['iddepo'] ?? 0) ?: null;
        $activo = (int)($_POST['activo'] ?? 0);

        if ($nomsuc === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre de la sucursal es obligatorio.'];
            Response::redirect('/admin/sucursales');
        }

        $repo = new SucursalRepo();
        $repo->save($id, $nomsuc, $numsuc, $puntoVenta, $iddepo, $activo);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => $id ? 'Sucursal actualizada.' : 'Sucursal creada.'];
        Response::redirect('/admin/sucursales');
    }
}
