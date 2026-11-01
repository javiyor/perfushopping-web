<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Repo\StockRepo;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Response;

final class BadgeController
{
    public function badges(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireLogin();

        $orderRepo = new OrderRepo();
        $userRepo = new UserRepo();
        $stockRepo = new StockRepo();
        $facturaRepo = new \Perfushopping\Web\Repo\FacturaRepo();
        $puedeAutorizarFacturas = \Perfushopping\Web\Service\AdminAuthService::adminPuedeAutorizar($adminUser);
        $edicionesFacturaPendientes = 0;
        if ($puedeAutorizarFacturas) {
            try {
                $edicionesFacturaPendientes = (new \Perfushopping\Web\Repo\FacturaEdicionAutorizacionRepo())->countPendientesPara((int)$adminUser['id']);
            } catch (\Throwable $e) {
                error_log('BadgeController invoice edit authorization count: ' . $e->getMessage());
            }
        }

        Response::json([
            'pedidos_nuevos' => $orderRepo->countPendingPayment(),
            'pedidos_abandonados' => $orderRepo->countAbandoned(),
            'usuarios_nuevos' => $userRepo->countNewToday(),
            'stock_ajustes_pendientes' => (($adminUser['rol'] ?? '') === 'superadmin') ? $stockRepo->countSolicitudesAjustePendientes() : 0,
            'envios_pendientes' => $facturaRepo->countEnviosPendientes(),
            'ediciones_factura_pendientes' => $edicionesFacturaPendientes,
        ]);
    }
}
