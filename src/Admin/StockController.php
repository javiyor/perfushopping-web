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
        $codprove = trim((string)($_GET['codprove'] ?? ''));
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $iddepo = (int)($_GET['iddepo'] ?? 0);
        if ($desde === '') $desde = date('Y-m-01', strtotime('first day of last month'));
        if ($hasta === '') $hasta = date('Y-m-t', strtotime('last day of last month'));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = 80;
        $list = $repo->listarStock($q, $codepar, $stockFilter, $codrub, $codsub, $codprove, $perPage, $iddepo ?: null, $desde, $hasta, $page);
        $total = $repo->contarStock($q, $codepar, $stockFilter, $codrub, $codsub, $codprove, $iddepo ?: null, $desde, $hasta);
        $lastPage = (int)ceil($total / $perPage);
        $rubros = $repo->grillaRubros();
        $subrubros = $repo->grillaSubrubros();
        $proveedores = $repo->grillaProveedores();
        $depositos = $repo->grillaDepositos();

        echo View::adminPage('admin/stock/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'codepar' => $codepar,
            'stockFilter' => $stockFilter,
            'codrub' => $codrub,
            'codsub' => $codsub,
            'codprove' => $codprove,
            'iddepo' => $iddepo,
            'desde' => $desde,
            'hasta' => $hasta,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $total,
            'lastPage' => $lastPage,
            'rubros' => $rubros,
            'subrubros' => $subrubros,
            'proveedores' => $proveedores,
            'depositos' => $depositos,
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
        $codprove = trim((string)($_GET['codprove'] ?? ''));
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $iddepo = (int)($_GET['iddepo'] ?? 0);
        if ($desde === '') $desde = date('Y-m-01', strtotime('first day of last month'));
        if ($hasta === '') $hasta = date('Y-m-t', strtotime('last day of last month'));
        $list = $repo->listarStock($q, $codepar, $stockFilter, $codrub, $codsub, $codprove, 1000, $iddepo ?: null, $desde, $hasta);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="stock.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($out, [
            'Producto', 'Variedad', 'Sucursal', 'Código', 'Cód. barra', 'Cód. proveedor',
            'Proveedor', 'Marca', 'Categoría',
            'Precio', 'Costo', 'Stock', 'Ventas',
        ]);

        foreach ($list as $p) {
            fputcsv($out, [
                $p['produ'] ?? '',
                $p['nomgusto'] ?? '',
                $p['nomdepo'] ?? '',
                $p['codprodu'] ?? '',
                $p['codscan'] ?? '',
                $p['codprodup'] ?? '',
                $p['nomprovee'] ?? '',
                $p['nomsub'] ?? '',
                $p['nomrub'] ?? '',
                number_format((float)($p['precio'] ?? 0), 2, ',', '.'),
                number_format((float)($p['precomp'] ?? 0), 2, ',', '.'),
                (int)($p['stock_deposito'] ?? 0),
                (int)($p['total_vendido'] ?? 0),
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
        $solicitudesPendientes = $repo->solicitudesAjustePendientes(40);
        $misSolicitudes = $repo->solicitudesAjustePorSolicitante((int)$adminUser['id'], 20);

        $productoId = (int)($params['id'] ?? 0);
        $producto = null;
        $variantes = [];
        $initialAjusteItems = [];
        if ($productoId > 0) {
            $producto = $repo->productoDetalle($productoId);
            if ($producto) {
                $variantes = $repo->variantesPorProducto($productoId);
                $initialAjusteItems[] = [
                    'idprodu' => (int)$producto['idprodu'],
                    'produ' => (string)($producto['produ'] ?? ''),
                    'codprodu' => (string)($producto['codprodu'] ?? ''),
                    'precio' => (float)($producto['precio'] ?? 0),
                    'stocact' => (int)($producto['stocact'] ?? 0),
                    'variants' => $variantes,
                ];
            }
        }

        echo View::adminPage('admin/stock/ajuste.php', [
            'adminUser' => $adminUser,
            'depositos' => $depositos,
            'producto' => $producto,
            'variantes' => $variantes,
            'initialAjusteItems' => $initialAjusteItems,
            'solicitudesPendientes' => $solicitudesPendientes,
            'misSolicitudes' => $misSolicitudes,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Ajuste de stock',
        ]);
    }

    public function storeAjuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $iddepodesde = (int)($_POST['iddepodesde'] ?? 0);
        $iddepohasta = (int)($_POST['iddepohasta'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));

        $idproduRaw = $_POST['idprodu'] ?? [];
        $idcodgustoRaw = $_POST['idcodgusto'] ?? [];
        $cantidadRaw = $_POST['cantidad'] ?? [];

        $idproduList = is_array($idproduRaw) ? $idproduRaw : [$idproduRaw];
        $idcodgustoList = is_array($idcodgustoRaw) ? $idcodgustoRaw : [$idcodgustoRaw];
        $cantidadList = is_array($cantidadRaw) ? $cantidadRaw : [$cantidadRaw];

        $items = [];
        $max = max(count($idproduList), count($cantidadList), count($idcodgustoList));
        for ($i = 0; $i < $max; $i++) {
            $idprodu = (int)($idproduList[$i] ?? 0);
            $cantidad = (int)($cantidadList[$i] ?? 0);
            $idcodgusto = (int)($idcodgustoList[$i] ?? 0);
            if ($idprodu <= 0 || $cantidad <= 0) {
                continue;
            }
            $items[] = [
                'idprodu' => $idprodu,
                'idcodgusto' => $idcodgusto > 0 ? $idcodgusto : null,
                'cantidad' => $cantidad,
            ];
        }

        if (!$items || $motivo === '' || ($iddepodesde <= 0 && $iddepohasta <= 0)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos requeridos.'];
            Response::redirect('/admin/stock/ajuste');
        }

        try {
            $repo = new StockRepo();
            $requiereAuth = $repo->requiereAutorizacionAjuste($iddepodesde, $iddepohasta, (string)($adminUser['rol'] ?? ''));

            if ($requiereAuth) {
                $solicitudes = 0;
                foreach ($items as $it) {
                    $repo->crearSolicitudAjuste(
                        (int)$it['idprodu'],
                        $it['idcodgusto'] !== null ? (int)$it['idcodgusto'] : null,
                        $iddepodesde,
                        $iddepohasta,
                        (int)$it['cantidad'],
                        $motivo,
                        (int)$adminUser['id'],
                        (string)($adminUser['nombre'] ?? '')
                    );
                    $solicitudes++;
                }
                $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Se enviaron ' . $solicitudes . ' solicitud(es) de autorización.'];
                Response::redirect('/admin/stock/ajuste');
            }

            $aplicados = 0;
            foreach ($items as $it) {
                $repo->registrarAjuste(
                    (int)$it['idprodu'],
                    $it['idcodgusto'] !== null ? (int)$it['idcodgusto'] : null,
                    $iddepodesde,
                    $iddepohasta,
                    (int)$it['cantidad'],
                    $motivo,
                    (int)$adminUser['id']
                );
                $aplicados++;
            }
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Ajuste de stock registrado. Productos procesados: ' . $aplicados . '.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error al registrar ajuste: ' . $e->getMessage()];
        }

        Response::redirect('/admin/stock/ajuste');
    }

    public function aprobarSolicitudAjuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        if ($solicitudId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solicitud inválida.'];
            Response::redirect('/admin/stock/ajuste');
        }

        try {
            (new StockRepo())->aprobarSolicitudAjuste($solicitudId, (int)$adminUser['id'], (string)($adminUser['nombre'] ?? ''));
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Solicitud aprobada y ajuste aplicado.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo aprobar: ' . $e->getMessage()];
        }
        Response::redirect('/admin/stock/ajuste');
    }

    public function rechazarSolicitudAjuste(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $solicitudId = (int)($_POST['solicitud_id'] ?? 0);
        $nota = trim((string)($_POST['nota_rechazo'] ?? ''));
        if ($solicitudId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solicitud inválida.'];
            Response::redirect('/admin/stock/ajuste');
        }
        if ($nota === '') {
            $nota = 'Rechazado por administración.';
        }

        try {
            (new StockRepo())->rechazarSolicitudAjuste($solicitudId, (int)$adminUser['id'], (string)($adminUser['nombre'] ?? ''), $nota);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Solicitud rechazada.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo rechazar: ' . $e->getMessage()];
        }
        Response::redirect('/admin/stock/ajuste');
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

    public function toggleDiscont(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $idcodgusto = (int)($_POST['idcodgusto'] ?? 0);
        $discont = isset($_POST['discont']) ? 1 : 0;
        if ($idcodgusto <= 0) {
            Response::json(['ok' => false, 'error' => 'ID inválido']);
            return;
        }

        try {
            (new StockRepo())->setDiscont($idcodgusto, $discont);
            Response::json(['ok' => true]);
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function eliminarDiscontinuadas(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $ids = $_POST['ids'] ?? [];
        if (!is_array($ids) || empty($ids)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná variedades discontinuadas para eliminar.'];
            Response::redirect('/admin/stock');
        }

        try {
            $count = (new StockRepo())->eliminarDiscontinuadas($ids);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Variedades eliminadas: {$count}"];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Error al eliminar: ' . $e->getMessage()];
        }
        Response::redirect('/admin/stock');
    }
}
