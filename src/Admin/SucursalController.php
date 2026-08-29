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
        $puntosRaw = trim((string)($_POST['puntos_venta'] ?? ''));
        $puntosVenta = [];
        if ($puntosRaw !== '') {
            foreach (preg_split('/[^0-9]+/', $puntosRaw) as $pv) {
                $n = (int)$pv;
                if ($n > 0) {
                    $puntosVenta[] = $n;
                }
            }
        }
        $iddepo = (int)($_POST['iddepo'] ?? 0) ?: null;
        $activo = (int)($_POST['activo'] ?? 0);
        $direccion = trim((string)($_POST['direccion'] ?? ''));
        $telefono = trim((string)($_POST['telefono'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));

        if ($nomsuc === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre de la sucursal es obligatorio.'];
            Response::redirect('/admin/sucursales');
        }
        if (!$puntosVenta) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cargá al menos un punto de venta (ej: 1, 2, 10).'];
            Response::redirect('/admin/sucursales');
        }

        $repo = new SucursalRepo();
        try {
            $repo->save($id, $nomsuc, $numsuc, $puntosVenta, $iddepo, $activo, $direccion, $telefono, $email);
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
            Response::redirect('/admin/sucursales');
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => $id ? 'Sucursal actualizada.' : 'Sucursal creada.'];
        Response::redirect('/admin/sucursales');
    }
}
