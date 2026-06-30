<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\CustomerRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CustomerController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $list = (new CustomerRepo())->search($q);

        echo View::adminPage('admin/clientes/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Clientes',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function detail(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new CustomerRepo();

        $customer = $repo->findById($id);
        if (!$customer) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cliente no encontrado.'];
            Response::redirect('/admin/clientes');
        }

        $orders = $repo->orders($id);

        $itemsByOrder = [];
        foreach ($orders as $o) {
            $oid = (int)($o['id'] ?? 0);
            if ($oid > 0) {
                $itemsByOrder[$oid] = $repo->orderItems($oid);
            }
        }

        $clienteErp = null;
        if (!empty($customer['cliente_id'])) {
            $clienteErp = $repo->clienteErp((int)$customer['cliente_id']);
        }

        $notas = $repo->notas($id);

        echo View::adminPage('admin/clientes/detail.php', [
            'adminUser' => $adminUser,
            'customer' => $customer,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'clienteErp' => $clienteErp,
            'notas' => $notas,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Cliente: ' . htmlspecialchars(mb_substr((string)($customer['name'] ?? $customer['email'] ?? ''), 0, 40)),
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function addNota(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $userId = (int)($_POST['user_id'] ?? 0);
        $texto = trim((string)($_POST['nota'] ?? ''));

        if ($userId <= 0 || $texto === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Datos invalidos para la nota.'];
            Response::redirect('/admin/clientes/' . $userId);
        }

        (new CustomerRepo())->addNota($userId, (int)$adminUser['id'], $texto);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Nota agregada.'];
        Response::redirect('/admin/clientes/' . $userId);
    }
}
