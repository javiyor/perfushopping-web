<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class StockController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new StockRepo();
        $q = trim((string)($_GET['q'] ?? ''));
        $codepar = (int)($_GET['codepar'] ?? 0);
        $stockFilter = trim((string)($_GET['stock'] ?? ''));
        $list = $repo->listarStock($q, $codepar, $stockFilter);
        $departamentos = $repo->departamentos();

        echo View::adminPage('admin/stock/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'codepar' => $codepar,
            'stockFilter' => $stockFilter,
            'departamentos' => $departamentos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Stock',
        ]);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new StockRepo();
        $producto = $repo->productoDetalle($id);
        if (!$producto) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect('/admin/stock');
        }
        $variantes = $repo->variantesConStock($id);
        $stockDepositos = $repo->stockPorDeposito($id);
        $movimientos = $repo->movimientos($id);

        echo View::adminPage('admin/stock/show.php', [
            'adminUser' => $adminUser,
            'producto' => $producto,
            'variantes' => $variantes,
            'stockDepositos' => $stockDepositos,
            'movimientos' => $movimientos,
            'csrf' => Csrf::token(),
            'pageTitle' => $producto['produ'] ?? 'Producto',
        ]);
    }

    public function ajuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new StockRepo();
        $depositos = $repo->depositos();

        $productoId = (int)($params['id'] ?? 0);
        $producto = null;
        $variantes = [];
        if ($productoId > 0) {
            $producto = $repo->productoDetalle($productoId);
            if ($producto) {
                $variantes = $repo->variantesPorProducto($productoId);
            }
        }

        echo View::adminPage('admin/stock/ajuste.php', [
            'adminUser' => $adminUser,
            'depositos' => $depositos,
            'producto' => $producto,
            'variantes' => $variantes,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Ajuste de stock',
        ]);
    }

    public function storeAjuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0) ?: null;
        $iddepo = (int)($_POST['iddepo'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));

        if ($idprodu <= 0 || $iddepo <= 0 || $cantidad === 0 || $motivo === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos requeridos.'];
            Response::redirect('/admin/stock/ajuste');
        }

        try {
            $repo = new StockRepo();
            $repo->registrarAjuste($idprodu, $idcodgusto, $iddepo, $cantidad, $motivo, (int)$adminUser['id']);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Ajuste de stock registrado correctamente.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error al registrar ajuste: ' . $e->getMessage()];
        }

        Response::redirect('/admin/stock/' . $idprodu);
    }

    public function recalcular(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        try {
            $rows = (new StockRepo())->recalcular();
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Stock recalculado: {$rows} filas en stock."];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error: ' . $e->getMessage()];
        }
        Response::redirect('/admin/stock');
    }

    public function searchAjusteProductos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            Response::json([]);
            return;
        }

        $repo = new StockRepo();
        $products = $repo->searchProducts($q, 15);

        foreach ($products as $i => $p) {
            $products[$i]['variants'] = $repo->variantesPorProducto((int)$p['idprodu']);
        }

        Response::json($products);
    }

    public function ajusteVariantes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) { Response::json([]); return; }

        $repo = new StockRepo();
        Response::json($repo->variantesPorProducto($id));
    }
}
