<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\OrdenCompraRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class OrdenCompraController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $list = (new OrdenCompraRepo())->search($q, $estado);

        echo View::adminPage('admin/ordenes-compra/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'estado' => $estado,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Órdenes de compra',
        ]);
    }

    public function create(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        echo View::adminPage('admin/ordenes-compra/form.php', [
            'adminUser' => $adminUser,
            'orden' => null,
            'items' => [],
            'csrf' => Csrf::token(),
            'pageTitle' => 'Nueva orden de compra',
        ]);
    }

    public function store(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $proveedorNombre = trim((string)($_POST['proveedor_nombre'] ?? ''));
        if ($proveedorNombre === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El proveedor es obligatorio.'];
            Response::redirect('/admin/ordenes-compra/nueva');
        }

        $productos = $_POST['producto'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];
        $precios = $_POST['precio_cents'] ?? [];
        $variedades = $_POST['variedad'] ?? [];
        $idpros = $_POST['idprodu'] ?? [];
        $idgustos = $_POST['idcodgusto'] ?? [];

        if (!is_array($productos) || count($productos) < 1) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Agregá al menos un producto.'];
            Response::redirect('/admin/ordenes-compra/nueva');
        }

        $items = [];
        $total = 0;
        foreach ($productos as $idx => $prodName) {
            if (trim((string)$prodName) === '') continue;
            $qty = max(1, (int)($cantidades[$idx] ?? 1));
            $unitPrice = max(0, (int)($precios[$idx] ?? 0));
            $lineTotal = $qty * $unitPrice;
            $items[] = [
                'idprodu' => (int)($idpros[$idx] ?? 0) ?: null,
                'idcodgusto' => (int)($idgustos[$idx] ?? 0) ?: null,
                'producto' => trim((string)$prodName),
                'variedad' => trim((string)($variedades[$idx] ?? '')),
                'qty' => $qty,
                'unit_price_cents' => $unitPrice,
                'total_cents' => $lineTotal,
            ];
            $total += $lineTotal;
        }

        if (!$items) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Agregá al menos un producto con cantidad.'];
            Response::redirect('/admin/ordenes-compra/nueva');
        }

        $repo = new OrdenCompraRepo();
        $codigo = $repo->nextCodigo();

        $fechaEstimada = trim((string)($_POST['fecha_estimada'] ?? ''));
        if ($fechaEstimada === '') $fechaEstimada = null;

        $id = $repo->create([
            'codigo' => $codigo,
            'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0) ?: null,
            'proveedor_nombre' => $proveedorNombre,
            'fecha' => (string)($_POST['fecha'] ?? date('Y-m-d')),
            'fecha_estimada' => $fechaEstimada,
            'total_cents' => $total,
            'estado' => 'pendiente',
            'notas' => trim((string)($_POST['notas'] ?? '')),
            'created_by' => (int)$adminUser['id'],
        ], $items);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Orden {$codigo} creada."];
        Response::redirect('/admin/ordenes-compra/' . $id);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new OrdenCompraRepo();
        $orden = $repo->findById($id);
        if (!$orden) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Orden no encontrada.'];
            Response::redirect('/admin/ordenes-compra');
        }
        $items = $repo->items($id);

        echo View::adminPage('admin/ordenes-compra/detail.php', [
            'adminUser' => $adminUser,
            'orden' => $orden,
            'items' => $items,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Orden ' . ($orden['codigo'] ?? ''),
        ]);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $estado = (string)($_POST['estado'] ?? '');

        if (!in_array($estado, ['pendiente', 'aprobada', 'recibida', 'anulada'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Estado inválido.'];
            Response::redirect('/admin/ordenes-compra');
        }

        $repo = new OrdenCompraRepo();
        $o = $repo->findById($id);
        if (!$o) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Orden no encontrada.'];
            Response::redirect('/admin/ordenes-compra');
        }

        $repo->updateEstado($id, $estado);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado a: ' . $estado];
        Response::redirect('/admin/ordenes-compra/' . $id);
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/ordenes-compra');

        (new OrdenCompraRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden eliminada.'];
        Response::redirect('/admin/ordenes-compra');
    }

    public function searchProducts(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new OrdenCompraRepo())->searchProducts($q);

        Response::json($results);
    }

    public function searchProveedores(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new OrdenCompraRepo())->findProveedores($q);

        Response::json($results);
    }
}
