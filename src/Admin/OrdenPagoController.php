<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\OrdenPagoRepo;
use Perfushopping\Web\Repo\CtaCteProveedorRepo;
use Perfushopping\Web\Repo\ChequeRepo;
use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class OrdenPagoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $list = (new OrdenPagoRepo())->search($q, $estado);

        echo View::adminPage('admin/ordenes-pago/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'estado' => $estado,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Órdenes de pago',
        ]);
    }

    public function create(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $bancos = (new BancoCuentaRepo())->findAll();

        echo View::adminPage('admin/ordenes-pago/form.php', [
            'adminUser' => $adminUser,
            'orden' => null,
            'pagos' => [],
            'bancos' => $bancos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Nueva orden de pago',
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
            Response::redirect('/admin/ordenes-pago/nueva');
        }

        $formasPago = $_POST['forma_pago'] ?? [];
        $montosPago = $_POST['pago_monto_cents'] ?? [];
        if (!is_array($formasPago) || count($formasPago) < 1) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Agregá al menos un medio de pago.'];
            Response::redirect('/admin/ordenes-pago/nueva');
        }

        $fechaVenc = trim((string)($_POST['fecha_vencimiento'] ?? '')) ?: null;
        $repo = new OrdenPagoRepo();
        $chequeRepo = new ChequeRepo();

        $pagos = [];
        $total = 0;
        foreach ($formasPago as $idx => $fp) {
            $fp = trim((string)$fp);
            if ($fp === '') continue;
            $monto = max(0, (int)($montosPago[$idx] ?? 0));
            if ($monto <= 0) continue;

            $chequeId = null;
            if ($fp === 'cheque_propio') {
                $bancoCuentaId = (int)($_POST['pago_banco_cuenta_id'][$idx] ?? 0);
                $chequeData = [
                    'tipo' => 'propio',
                    'estado' => 'emitido',
                    'banco_emisor' => trim((string)($_POST['pago_banco_emisor'][$idx] ?? '')),
                    'numero_cheque' => trim((string)($_POST['pago_numero_cheque'][$idx] ?? '')),
                    'titular' => $proveedorNombre,
                    'monto_cents' => $monto,
                    'fecha_emision' => (string)($_POST['fecha'] ?? date('Y-m-d')),
                    'fecha_vencimiento' => $fechaVenc,
                    'banco_cuenta_id' => $bancoCuentaId ?: null,
                    'concepto' => 'OP a ' . $proveedorNombre,
                ];
                $chequeId = $chequeRepo->create($chequeData, (int)$adminUser['id']);
                $chequeRepo->agregarMovimiento($chequeId, 'emitido', null, null, 'Emitido para OP', (int)$adminUser['id']);
            }

            $pagos[] = [
                'forma_pago' => $fp,
                'cheque_id' => $chequeId,
                'monto_cents' => $monto,
            ];
            $total += $monto;
        }

        if (!$pagos) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Agregá al menos un medio de pago con monto válido.'];
            Response::redirect('/admin/ordenes-pago/nueva');
        }

        $codigo = $repo->nextCodigo();
        $id = $repo->create([
            'codigo' => $codigo,
            'proveedor_id' => (int)($_POST['proveedor_id'] ?? 0) ?: null,
            'proveedor_nombre' => $proveedorNombre,
            'fecha' => (string)($_POST['fecha'] ?? date('Y-m-d')),
            'monto_cents' => $total,
            'estado' => 'pagada',
            'concepto' => trim((string)($_POST['concepto'] ?? '')),
        ], $pagos, (int)$adminUser['id']);

        // Register credit in supplier CTACTE
        try {
            $ctacteRepo = new CtaCteProveedorRepo();
            $ctacteRepo->agregarMovimiento(
                'credito',
                'op',
                $id,
                (int)($_POST['proveedor_id'] ?? 0) ?: null,
                $proveedorNombre,
                $total,
                'Pago OP ' . $codigo,
                (int)$adminUser['id']
            );
        } catch (\Throwable $e) {
            // Log but don't fail
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Orden de pago {$codigo} registrada."];
        Response::redirect('/admin/ordenes-pago/' . $id);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new OrdenPagoRepo();
        $orden = $repo->findById($id);
        if (!$orden) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Orden no encontrada.'];
            Response::redirect('/admin/ordenes-pago');
        }
        $pagos = $repo->pagos($id);

        echo View::adminPage('admin/ordenes-pago/detail.php', [
            'adminUser' => $adminUser,
            'orden' => $orden,
            'pagos' => $pagos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'OP ' . ($orden['codigo'] ?? ''),
        ]);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $estado = (string)($_POST['estado'] ?? '');
        if (!in_array($estado, ['pendiente', 'pagada', 'anulada'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Estado inválido.'];
            Response::redirect('/admin/ordenes-pago');
        }

        $repo = new OrdenPagoRepo();
        $o = $repo->findById($id);
        if (!$o) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Orden no encontrada.'];
            Response::redirect('/admin/ordenes-pago');
        }

        $repo->updateEstado($id, $estado);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado a: ' . $estado];
        Response::redirect('/admin/ordenes-pago/' . $id);
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/ordenes-pago');
        (new OrdenPagoRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden eliminada.'];
        Response::redirect('/admin/ordenes-pago');
    }

    public function searchProveedores(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $results = (new OrdenPagoRepo())->findProveedores($q);
        Response::json($results);
    }

    public function deudaProveedor(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $proveedorId = (int)($_GET['proveedor_id'] ?? 0);
        $proveedorNombre = trim((string)($_GET['proveedor_nombre'] ?? ''));

        $ctacteRepo = new CtaCteProveedorRepo();
        $saldo = $ctacteRepo->saldoActual($proveedorId ?: null);
        $movs = $ctacteRepo->movimientos($proveedorId ?: null, '', 20);

        Response::json(['saldo_cents' => $saldo, 'movimientos' => $movs]);
    }
}
