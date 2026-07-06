<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Repo\OrdenCompraRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class StockGrillaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new StockRepo();
        $rubros = $repo->grillaRubros();
        $subrubros = $repo->grillaSubrubros();
        $proveedores = $repo->grillaProveedores();

        // Filters from GET
        $q = trim((string)($_GET['q'] ?? ''));
        $codrub = (int)($_GET['codrub'] ?? 0);
        $codsub = (int)($_GET['codsub'] ?? 0);
        $codprove = (int)($_GET['codprove'] ?? 0);
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));

        $productos = $repo->grillaProductos($q, $codrub, $codsub, $codprove, $desde, $hasta);

        echo View::adminPage('admin/stock/grilla.php', [
            'adminUser' => $adminUser,
            'productos' => $productos,
            'rubros' => $rubros,
            'subrubros' => $subrubros,
            'proveedores' => $proveedores,
            'q' => $q,
            'codrub' => $codrub,
            'codsub' => $codsub,
            'codprove' => $codprove,
            'desde' => $desde,
            'hasta' => $hasta,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Grilla de reposición',
        ]);
    }

    public function generarOC(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $productos = $_POST['productos'] ?? [];
        $cantidades = $_POST['cantidad'] ?? [];

        if (!is_array($productos) || !is_array($cantidades) || count($productos) < 1) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná al menos un producto con cantidad > 0.'];
            Response::redirect('/admin/stock/grilla');
        }

        $repo = new StockRepo();
        $ocRepo = new OrdenCompraRepo();

        // Group selected items by proveedor
        $porProveedor = [];
        foreach ($productos as $idx => $idprodu) {
            $idprodu = (int)$idprodu;
            $qty = max(1, (int)($cantidades[$idx] ?? 0));
            if ($idprodu <= 0 || $qty <= 0) continue;

            $prod = $repo->productoDetalle($idprodu);
            if (!$prod) continue;

            $codprove = (int)($prod['codprove'] ?? 0);
            $proveedorNombre = '(sin proveedor)';
            $proveedorId = null;

            if ($codprove > 0) {
                // Look up supplier name
                $pv = Db::pdo()->prepare('SELECT codprove, razon AS nomprovee FROM proveedo WHERE codprove = :cp LIMIT 1');
                $pv->execute([':cp' => $codprove]);
                $pRow = $pv->fetch();
                if ($pRow) {
                    $proveedorNombre = $pRow['nomprovee'];
                    $proveedorId = (int)$pRow['codprove'];
                }
            }

            $porProveedor[$codprove][] = [
                'idprodu' => $idprodu,
                'codprodu' => $prod['codprodu'] ?? '',
                'producto' => $prod['produ'] ?? '',
                'codscan' => $prod['codscan'] ?? '',
                'codprodup' => $prod['codprodup'] ?? '',
                'qty' => $qty,
                'unit_price_cents' => (int)($prod['precomp'] ?? 0),
                'total_cents' => $qty * (int)($prod['precomp'] ?? 0),
                'proveedor_nombre' => $proveedorNombre,
                'proveedor_id' => $proveedorId,
            ];
        }

        if (!$porProveedor) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se seleccionaron productos válidos.'];
            Response::redirect('/admin/stock/grilla');
        }

        // Create one OC per proveedor
        $createdIds = [];
        foreach ($porProveedor as $codp => $items) {
            $totalCents = 0;
            $ocItems = [];
            $proveedorNombre = $items[0]['proveedor_nombre'];
            $proveedorId = $items[0]['proveedor_id'] ?: null;

            foreach ($items as $it) {
                $totalCents += $it['total_cents'];
                $ocItems[] = [
                    'idprodu' => $it['idprodu'],
                    'idcodgusto' => null,
                    'producto' => $it['producto'],
                    'variedad' => 'Cod: ' . $it['codprodu'] . ($it['codprodup'] ? ' / Prov: ' . $it['codprodup'] : '') . ($it['codscan'] ? ' / BAR: ' . $it['codscan'] : ''),
                    'qty' => $it['qty'],
                    'unit_price_cents' => $it['unit_price_cents'],
                    'total_cents' => $it['total_cents'],
                ];
            }

            $codigo = $ocRepo->nextCodigo();
            $fechaEst = trim((string)($_POST['fecha_estimada'] ?? ''));
            if ($fechaEst === '') $fechaEst = date('Y-m-d', strtotime('+7 days'));

            $id = $ocRepo->create([
                'codigo' => $codigo,
                'proveedor_id' => $proveedorId,
                'proveedor_nombre' => $proveedorNombre,
                'fecha' => date('Y-m-d'),
                'fecha_estimada' => $fechaEst,
                'total_cents' => $totalCents,
                'estado' => 'pendiente',
                'notas' => trim((string)($_POST['notas'] ?? 'Generado desde grilla de reposición')),
                'created_by' => (int)$adminUser['id'],
            ], $ocItems);

            $createdIds[] = $id;
        }

        if (count($createdIds) === 1) {
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden de compra generada correctamente.'];
            Response::redirect('/admin/ordenes-compra/' . $createdIds[0]);
        } else {
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => count($createdIds) . ' órdenes de compra generadas.'];
            Response::redirect('/admin/ordenes-compra');
        }
    }

    public function exportarPDF(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $ocRepo = new OrdenCompraRepo();
        $orden = $ocRepo->findById($id);
        if (!$orden) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Orden no encontrada.'];
            Response::redirect('/admin/ordenes-compra');
        }
        $items = $ocRepo->items($id);

        echo View::render('admin/stock/grilla_oc_pdf.php', [
            'orden' => $orden,
            'items' => $items,
        ]);
    }

    public function exportarExcel(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $ocRepo = new OrdenCompraRepo();
        $orden = $ocRepo->findById($id);
        if (!$orden) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Orden no encontrada.'];
            Response::redirect('/admin/ordenes-compra');
        }
        $items = $ocRepo->items($id);

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="OC-' . ($orden['codigo'] ?? $id) . '.csv"');

        $out = fopen('php://output', 'w');
        fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM UTF-8

        fputcsv($out, [
            'Proveedor', $orden['proveedor_nombre'] ?? '', '', '', '', ''
        ]);
        fputcsv($out, [
            'OC', $orden['codigo'] ?? '', 'Fecha', $orden['fecha'] ?? '', 'Estado', $orden['estado'] ?? ''
        ]);
        fputcsv($out, []);
        fputcsv($out, [
            'Código producto', 'Descripción', 'Código proveedor', 'Código barra', 'Cantidad', 'Precio unit.', 'Total'
        ]);

        foreach ($items as $it) {
            // Parse variedad field to extract codprove and codscan
            $variedad = $it['variedad'] ?? '';
            $codprodu = '';
            $codproveExtracted = '';
            $codscanExtracted = '';
            if (preg_match('/^Cod: (\S+)(.*)$/', $variedad, $m)) {
                $codprodu = $m[1];
                $rest = $m[2];
                if (preg_match('/Prov: (\S+)/', $rest, $pm)) {
                    $codproveExtracted = $pm[1];
                }
                if (preg_match('/BAR: (\S+)/', $rest, $bm)) {
                    $codscanExtracted = $bm[1];
                }
            }

            fputcsv($out, [
                $codprodu,
                $it['producto'] ?? '',
                $codproveExtracted,
                $codscanExtracted,
                (int)($it['qty'] ?? 0),
                number_format((int)($it['unit_price_cents'] ?? 0) / 100, 2, ',', '.'),
                number_format((int)($it['total_cents'] ?? 0) / 100, 2, ',', '.'),
            ]);
        }

        fputcsv($out, []);
        fputcsv($out, ['Total', '', '', '', '', '', number_format((int)($orden['total_cents'] ?? 0) / 100, 2, ',', '.')]);

        fclose($out);
        exit;
    }
}
