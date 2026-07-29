<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\NotaPedidoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class NotaPedidoController
{
    public function create(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        echo View::adminPage('admin/stock/nota-pedido.php', [
            'adminUser' => $adminUser,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Nueva nota de pedido',
        ]);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $productosJson = trim((string)($_POST['productos'] ?? ''));
        $productos = json_decode($productosJson, true);
        if (!is_array($productos) || empty($productos)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay productos en la nota.'];
            Response::redirect('/admin/stock');
        }

        $items = [];
        foreach ($productos as $p) {
            $qty = (int)($p['qty'] ?? 0);
            if ($qty <= 0) continue;
            $items[] = [
                'idprodu' => (int)($p['idprodu'] ?? 0),
                'idcodgusto' => (int)($p['idcodgusto'] ?? 0) ?: null,
                'producto' => $p['producto'] ?? '',
                'variedad' => $p['variedad'] ?? '',
                'codscan' => $p['codscan'] ?? '',
                'codprodup' => $p['codprodup'] ?? '',
                'qty' => $qty,
            ];
        }

        if (empty($items)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Ningún producto con cantidad > 0.'];
            Response::redirect('/admin/stock');
        }

        $repo = new NotaPedidoRepo();
        $data = [
            'codigo' => $repo->nextCodigo(),
            'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0) ?: null,
            'proveedor_nombre' => trim((string)($_POST['proveedor_nombre'] ?? '')),
            'transporte' => trim((string)($_POST['transporte'] ?? '')),
            'envio_direccion' => trim((string)($_POST['envio_direccion'] ?? '')),
            'envio_ciudad' => trim((string)($_POST['envio_ciudad'] ?? '')),
            'envio_telefono' => trim((string)($_POST['envio_telefono'] ?? '')),
            'notas' => trim((string)($_POST['notas'] ?? '')),
            'created_by' => (int)($adminUser['id'] ?? 0),
        ];

        $notaId = $repo->create($data, $items);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Nota de pedido generada: ' . $data['codigo']];
        Response::redirect('/admin/nota-pedido/' . $notaId);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new NotaPedidoRepo();
        $nota = $repo->findById($id);
        if (!$nota) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Nota de pedido no encontrada.'];
            Response::redirect('/admin/stock');
        }
        $items = $repo->items($id);

        echo View::adminPage('admin/stock/nota-pedido-show.php', [
            'adminUser' => $adminUser,
            'nota' => $nota,
            'items' => $items,
            'pageTitle' => 'Nota: ' . ($nota['codigo'] ?? ''),
        ]);
    }

    public function searchProveedores(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new NotaPedidoRepo())->searchProveedores($q);
        Response::json($results);
    }
}
