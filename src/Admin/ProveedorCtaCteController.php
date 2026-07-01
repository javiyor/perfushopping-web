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
        $adminUser = $auth->requireSesion();

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

    public function movimientos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $proveedorId = isset($params['id']) ? (int)$params['id'] : null;
        $q = trim((string)($_GET['q'] ?? ''));

        $repo = new CtaCteProveedorRepo();
        $movimientos = $repo->movimientos($proveedorId, $q);
        $saldo = $repo->saldoActual($proveedorId);

        $proveedorNombre = '';
        if ($movimientos) {
            $proveedorNombre = $movimientos[0]['proveedor_nombre'] ?? '';
        }

        echo View::adminPage('admin/proveedores/ctacte/show.php', [
            'adminUser' => $adminUser,
            'movimientos' => $movimientos,
            'proveedorId' => $proveedorId,
            'proveedorNombre' => $proveedorNombre,
            'saldo' => $saldo,
            'q' => $q,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cta Cte — ' . ($proveedorNombre ?: 'Proveedores'),
        ]);
    }
}
