<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\DepartamentoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class DepartamentoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new DepartamentoRepo();
        $list = $repo->findAll();

        $productCounts = [];
        foreach ($list as $d) {
            $id = (int)($d['id'] ?? 0);
            $productCounts[$id] = $id > 0 ? $repo->productCount($id) : 0;
        }

        echo View::adminPage('admin/departamentos/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'productCounts' => $productCounts,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Departamentos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $nombre = trim((string)($_POST['nombre'] ?? ''));

        if ($nombre === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre es obligatorio.'];
            Response::redirect('/admin/departamentos');
        }

        $repo = new DepartamentoRepo();

        if ($id > 0) {
            $repo->update($id, $nombre);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Departamento actualizado.'];
        } else {
            $repo->create($nombre);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Departamento creado.'];
        }

        Response::redirect('/admin/departamentos');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'ID invalido.'];
            Response::redirect('/admin/departamentos');
        }

        $repo = new DepartamentoRepo();
        $repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Departamento eliminado. Productos desvinculados.'];
        Response::redirect('/admin/departamentos');
    }
}
