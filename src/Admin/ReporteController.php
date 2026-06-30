<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\ReporteRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ReporteController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $desde = (string)($_GET['desde'] ?? date('Y-m-01'));
        $hasta = (string)($_GET['hasta'] ?? date('Y-m-d'));
        $puntoVenta = (int)($auth->getPuntoVenta());

        echo View::adminPage('admin/reportes/index.php', [
            'adminUser' => $adminUser,
            'desde' => $desde,
            'hasta' => $hasta,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Reportes',
        ]);
    }

    public function data(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $desde = (string)($_GET['desde'] ?? date('Y-m-01'));
        $hasta = (string)($_GET['hasta'] ?? date('Y-m-d'));
        $puntoVenta = (int)($auth->getPuntoVenta());

        $repo = new ReporteRepo();

        $resumen = $repo->resumenVentas($desde, $hasta, $puntoVenta);
        $diarias = $repo->ventasDiarias($desde, $hasta, $puntoVenta);
        $topProductos = $repo->topProductos($desde, $hasta, 15, $puntoVenta);
        $porDepartamento = $repo->ventasPorDepartamento($desde, $hasta, $puntoVenta);
        $porFormaPago = $repo->ventasPorFormaPago($desde, $hasta, $puntoVenta);
        $recibos = $repo->resumenRecibos($desde, $hasta, $puntoVenta);
        $porTipo = $repo->facturasPorTipo($desde, $hasta, $puntoVenta);

        Response::json([
            'resumen' => $resumen,
            'diarias' => $diarias,
            'topProductos' => $topProductos,
            'porDepartamento' => $porDepartamento,
            'porFormaPago' => $porFormaPago,
            'recibos' => $recibos,
            'porTipo' => $porTipo,
        ]);
    }
}
