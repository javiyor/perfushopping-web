<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\OrdenCompraRepo;
use Perfushopping\Web\Repo\CtaCteProveedorRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;
use Perfushopping\Web\Support\Format;

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

    public function guardarRecepcion(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $repo = new OrdenCompraRepo();
        $orden = $repo->findById($id);
        if (!$orden) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Orden no encontrada.'];
            Response::redirect('/admin/ordenes-compra');
        }
        if ($orden['estado'] === 'anulada' || $orden['estado'] === 'recibida') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se puede modificar una orden ' . $orden['estado'] . '.'];
            Response::redirect('/admin/ordenes-compra/' . $id);
        }

        $fechaRecepcion = trim((string)($_POST['fecha_recepcion'] ?? ''));
        if ($fechaRecepcion === '') $fechaRecepcion = date('Y-m-d');

        $fletePagado = (int)(($_POST['flete_pagado'] ?? '0') === '1');
        $fleteCents = (int)($_POST['flete_cents'] ?? 0);

        $repo->updateRecepcion($id, [
            'fecha_recepcion' => $fechaRecepcion,
            'bultos_recibidos' => $_POST['bultos_recibidos'] !== '' ? (int)$_POST['bultos_recibidos'] : null,
            'controlado_por' => $_POST['controlado_por'] !== '' ? (int)$_POST['controlado_por'] : null,
            'valor_declarado_cents' => (int)($_POST['valor_declarado_cents'] ?? 0),
            'flete_cents' => $fleteCents,
            'flete_pagado' => $fletePagado,
            'estado' => 'recibida',
        ]);

        // Handle comprobante upload
        $comprobanteFile = '';
        if (isset($_FILES['flete_comprobante']) && $_FILES['flete_comprobante']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = APP_BASE_DIR . '/storage/oc/fletes';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $ext = pathinfo($_FILES['flete_comprobante']['name'], PATHINFO_EXTENSION);
            $comprobanteFile = $orden['codigo'] . '_' . time() . '.' . $ext;
            move_uploaded_file($_FILES['flete_comprobante']['tmp_name'], $uploadDir . '/' . $comprobanteFile);
            $repo->updateFleteComprobante($id, $comprobanteFile);
        }

        // Register flete payment
        if ($fleteCents > 0) {
            if ($fletePagado) {
                try {
                    $cajaRepo = new \Perfushopping\Web\Repo\CajaRepo();
                    $sucursalId = $auth->getSucursalId();
                    $turno = $auth->getTurno();
                    $apertura = $cajaRepo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));
                    if ($apertura) {
                        $cajaRepo->agregarMovimiento(
                            (int)$apertura['id'],
                            'egreso',
                            'Flete OC ' . $orden['codigo'] . ' — ' . $orden['proveedor_nombre'],
                            $fleteCents,
                            (int)$adminUser['id']
                        );
                    }
                } catch (\Throwable $e) {
                    // Log but don't fail the reception
                }
            } else {
                try {
                    $ctacteRepo = new CtaCteProveedorRepo();
                    $ctacteRepo->agregarMovimiento(
                        'debito',
                        'oc_flete',
                        $id,
                        (int)$orden['proveedor_id'],
                        (string)$orden['proveedor_nombre'],
                        $fleteCents,
                        'Flete OC ' . $orden['codigo'],
                        (int)$adminUser['id']
                    );
                } catch (\Throwable $e) {
                    // Log but don't fail
                }
            }
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Recepción registrada. Orden marcada como recibida.'];
        Response::redirect('/admin/ordenes-compra/' . $id);
    }

    public function fletes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $proveedor = trim((string)($_GET['proveedor'] ?? ''));
        $list = (new OrdenCompraRepo())->searchFletes($q, $proveedor);

        $totalFletes = 0;
        foreach ($list as $o) {
            $totalFletes += (int)$o['flete_cents'];
        }

        echo View::adminPage('admin/ordenes-compra/fletes.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'proveedor' => $proveedor,
            'totalFletes' => $totalFletes,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Fletes — Costos comparativos',
        ]);
    }

    public function descargarComprobante(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $orden = (new OrdenCompraRepo())->findById($id);
        if (!$orden || !$orden['flete_comprobante']) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Archivo no encontrado.'];
            Response::redirect('/admin/ordenes-compra/' . $id);
        }

        $filePath = APP_BASE_DIR . '/storage/oc/fletes/' . basename((string)$orden['flete_comprobante']);
        if (!is_file($filePath)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El archivo ya no existe en el servidor.'];
            Response::redirect('/admin/ordenes-compra/' . $id);
        }

        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename((string)$orden['flete_comprobante']) . '"');
        header('Content-Length: ' . filesize($filePath));
        readfile($filePath);
        exit;
    }
}
