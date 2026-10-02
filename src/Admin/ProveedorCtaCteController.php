<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\CtaCteProveedorRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ProveedorCtaCteController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');

        $q = trim((string)($_GET['q'] ?? ''));
        $list = (new CtaCteProveedorRepo())->listarConSaldo($q);
        $saldoTotal = 0;
        foreach ($list as $item) {
            $saldoTotal += (int)($item['saldo_cents'] ?? 0);
        }

        echo View::adminPage('admin/proveedores/ctacte/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'q' => $q,
            'saldoTotal' => $saldoTotal,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cta Cte — Proveedores',
        ]);
    }

    public function sincronizar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');
        Csrf::check($_POST['_csrf'] ?? null);

        $res = (new CtaCteProveedorRepo())->sincronizarCompras((int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Sincronizadas ' . $res['insertadas'] . ' facturas de compra con cuenta corriente.' . ($res['omitidas'] > 0 ? ' Omitidas ' . $res['omitidas'] . ' sin importe.' : '')];
        Response::redirect('/admin/proveedores/ctacte');
    }

    public function movimientos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras');

        $proveedorId = isset($params['id']) ? (int)$params['id'] : null;
        $q = trim((string)($_GET['q'] ?? ''));

        $repo = new CtaCteProveedorRepo();
        try {
            // Backfill idempotente: asigna OPs viejas (sin orden_pago_compras) a facturas impagas
            $repo->sincronizarAsignaciones($proveedorId);
        } catch (\Throwable $e) {
            error_log('sincronizarAsignaciones: ' . $e->getMessage());
        }
        $movimientos = $repo->movimientos($proveedorId, $q);
        $saldo = $repo->saldoActual($proveedorId);
        $comprobantes = $proveedorId !== null ? $repo->comprobantesConPlazo($proveedorId) : [];

        $proveedorNombre = '';
        if ($movimientos) {
            $proveedorNombre = $movimientos[0]['proveedor_nombre'] ?? '';
        }

        echo View::adminPage('admin/proveedores/ctacte/show.php', [
            'adminUser' => $adminUser,
            'movimientos' => $movimientos,
            'comprobantes' => $comprobantes,
            'proveedorId' => $proveedorId,
            'proveedorNombre' => $proveedorNombre,
            'saldo' => $saldo,
            'q' => $q,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cta Cte — ' . ($proveedorNombre ?: 'Proveedores'),
        ]);
    }
}
