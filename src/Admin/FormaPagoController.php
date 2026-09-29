<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\FormaPagoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class FormaPagoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('facturacion');

        echo View::adminPage('admin/formas-pago/list.php', [
            'adminUser' => $adminUser,
            'list' => (new FormaPagoRepo())->findAll(),
            'tipos' => FormaPagoRepo::TIPOS,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Formas de pago',
        ]);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0) ?: null;
        try {
            (new FormaPagoRepo())->save($id, [
                'codigo' => trim((string)($_POST['codigo'] ?? '')),
                'nombre' => trim((string)($_POST['nombre'] ?? '')),
                'tipo' => trim((string)($_POST['tipo'] ?? '')),
                'moneda' => trim((string)($_POST['moneda'] ?? '')),
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'orden' => (int)($_POST['orden'] ?? 0),
            ]);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Forma de pago guardada.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/formas-pago');
    }

    public function toggle(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new FormaPagoRepo())->toggle($id);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado.'];
        }
        Response::redirect('/admin/formas-pago');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('facturacion');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            try {
                (new FormaPagoRepo())->delete($id);
                $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Forma de pago eliminada.'];
            } catch (\Throwable $e) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
            }
        }
        Response::redirect('/admin/formas-pago');
    }
}
