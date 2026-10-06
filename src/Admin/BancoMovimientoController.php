<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\BancoMovimientoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\View;

final class BancoMovimientoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques', 'caja_movimientos');

        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $cuenta = (int)($_GET['cuenta'] ?? 0);
        $tipo = trim((string)($_GET['tipo'] ?? ''));
        if ($desde === '' && $hasta === '') {
            $desde = date('Y-m-01');
            $hasta = date('Y-m-t');
        }
        if ($tipo !== 'credito' && $tipo !== 'debito') {
            $tipo = '';
        }

        $repo = new BancoMovimientoRepo();
        $movimientos = $repo->allMovimientos($cuenta > 0 ? $cuenta : null, $desde !== '' ? $desde : null, $hasta !== '' ? $hasta : null, $tipo !== '' ? $tipo : null);
        $totales = $repo->totales($cuenta > 0 ? $cuenta : null, $desde !== '' ? $desde : null, $hasta !== '' ? $hasta : null, $tipo !== '' ? $tipo : null);

        $cuentasRepo = new BancoCuentaRepo();
        $cuentas = $cuentasRepo->findAll(false);
        $saldos = [];
        foreach ($cuentas as $c) {
            try {
                $saldos[(int)$c['id']] = $repo->saldo((int)$c['id']);
            } catch (\Throwable $e) {
                $saldos[(int)$c['id']] = 0;
            }
        }

        echo View::adminPage('admin/banco_movimientos/list.php', [
            'adminUser' => $adminUser,
            'movimientos' => $movimientos,
            'totales' => $totales,
            'cuentas' => $cuentas,
            'saldos' => $saldos,
            'desde' => $desde,
            'hasta' => $hasta,
            'cuenta' => $cuenta,
            'tipo' => $tipo,
            'pageTitle' => 'Movimientos bancarios',
        ]);
    }
}
