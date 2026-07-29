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
        $codrub = (int)($_GET['codrub'] ?? 0);
        $codsub = (int)($_GET['codsub'] ?? 0);
        $codprove = (int)($_GET['codprove'] ?? 0);
        $list = $repo->listarStock($q, $codepar, $stockFilter, $codrub, $codsub, $codprove);
        $rubros = $repo->grillaRubros();
        $subrubros = $repo->grillaSubrubros();
        $proveedores = $repo->grillaProveedores();

        echo View::adminPage('admin/stock/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'codepar' => $codepar,
            'stockFilter' => $stockFilter,
            'codrub' => $codrub,
            'codsub' => $codsub,
            'codprove' => $codprove,
            'rubros' => $rubros,
            'subrubros' => $subrubros,
            'proveedores' => $proveedores,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Stock',
        ]);
    }

    public function exportarExcel(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new StockRepo();
        $q = trim((string)($_GET['q'] ?? ''));
        $codepar = (int)($_GET['codepar'] ?? 0);
        $stockFilter = trim((string)($_GET['stock'] ?? ''));
        $codrub = (int)($_GET['codrub'] ?? 0);
        $codsub = (int)($_GET['codsub'] ?? 0);
        $codprove = (int)($_GET['codprove'] ?? 0);
        $list = $repo->listarStock($q, $codepar, $stockFilter, $codrub, $codsub, $codprove, 1000);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stock.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Producto', 'Variedad', 'Código', 'Cód. barra', 'Cód. proveedor',
            'Proveedor', 'Marca', 'Categoría',
            'Precio', 'Costo', 'Stock',
        ]);

        foreach ($list as $p) {
            fputcsv($out, [
                $p['produ'] ?? '',
                $p['nomgusto'] ?? '',
                $p['codprodu'] ?? '',
                $p['codscan'] ?? '',
                $p['codprodup'] ?? '',
                $p['nomprovee'] ?? '',
                $p['nomsub'] ?? '',
                $p['nomrub'] ?? '',
                number_format((float)($p['precio'] ?? 0), 2, ',', '.'),
                number_format((float)($p['precomp'] ?? 0), 2, ',', '.'),
                (int)($p['stock_variante'] ?? 0),
            ]);
        }

        fclose($out);
        exit;
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
        $comprasVentas = $repo->comprasVentasPorDeposito($id);
        $movCompras = $repo->movimientosPorTipo($id, null, 'compra');
        $movVentas = $repo->movimientosPorTipo($id, null, 'venta');

        echo View::adminPage('admin/stock/show.php', [
            'adminUser' => $adminUser,
            'producto' => $producto,
            'variantes' => $variantes,
            'stockDepositos' => $stockDepositos,
            'movimientos' => $movimientos,
            'comprasVentas' => $comprasVentas,
            'movCompras' => $movCompras,
            'movVentas' => $movVentas,
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
        $iddepodesde = (int)($_POST['iddepodesde'] ?? 0);
        $iddepohasta = (int)($_POST['iddepohasta'] ?? 0);
        $cantidad = (int)($_POST['cantidad'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));

        if ($idprodu <= 0 || $cantidad <= 0 || $motivo === '' || ($iddepodesde <= 0 && $iddepohasta <= 0)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos requeridos.'];
            Response::redirect('/admin/stock/ajuste');
        }

        try {
            $repo = new StockRepo();
            $repo->registrarAjuste($idprodu, $idcodgusto, $iddepodesde, $iddepohasta, $cantidad, $motivo, (int)$adminUser['id']);
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
            $info = (new StockRepo())->recalcular();
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Stock recalculado: {$info}"];
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
