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
        $adminUser = $auth->requireRol('superadmin');

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
        $adminUser = $auth->requireRol('superadmin');

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
        $porSucursal = $repo->ventasPorSucursal($desde, $hasta);

        // Comparativas tomando como referencia el mes de la fecha "hasta".
        // Se compara del día 1 al mismo día N en cada período para que sean equivalentes.
        $ref = \DateTime::createFromFormat('Y-m-d', $hasta) ?: new \DateTime();
        $y = (int)$ref->format('Y');
        $m = (int)$ref->format('m');
        $dia = min((int)$ref->format('d'), 28);
        $rangoMes = static function (int $yy, int $mm) use ($dia): array {
            $ini = sprintf('%04d-%02d-01', $yy, $mm);
            $fin = sprintf('%04d-%02d-%02d', $yy, $mm, $dia);
            return [$ini, $fin];
        };
        $mp = $m === 1 ? [$y - 1, 12] : [$y, $m - 1];
        $periodos = [
            'mesActual' => [$ref->format('Y-m-01'), $ref->format('Y-m-d')],
            'mesAnterior' => $rangoMes($mp[0], $mp[1]),
            'hace1Anio' => $rangoMes($y - 1, $m),
            'hace2Anios' => $rangoMes($y - 2, $m),
        ];
        $comparativas = [];
        foreach ($periodos as $key => [$d, $h]) {
            $r = $repo->resumenVentas($d, $h, $puntoVenta);
            $comparativas[$key] = [
                'desde' => $d,
                'hasta' => $h,
                'cantidad' => (int)($r['cantidad'] ?? 0),
                'total_cents' => (int)($r['total_cents'] ?? 0),
            ];
        }

        // Serie mensual de 24 meses para el comparativo interanual.
        $desde24 = (clone $ref)->modify('first day of this month')->modify('-23 months')->format('Y-m-d');
        $mensuales = $repo->ventasMensuales($desde24, $ref->format('Y-m-t'), $puntoVenta);

        $ganancia = $repo->ganancia($desde, $hasta, $puntoVenta);
        $topGanancia = $repo->topGanancia($desde, $hasta, 15, $puntoVenta);
        $margenDepto = $repo->margenPorDepartamento($desde, $hasta, $puntoVenta);

        $ticket = ((int)($resumen['cantidad'] ?? 0) > 0)
            ? (int)round((int)($resumen['total_cents'] ?? 0) / (int)$resumen['cantidad'])
            : 0;

        Response::json([
            'resumen' => $resumen,
            'diarias' => $diarias,
            'topProductos' => $topProductos,
            'porDepartamento' => $porDepartamento,
            'porFormaPago' => $porFormaPago,
            'recibos' => $recibos,
            'porTipo' => $porTipo,
            'porSucursal' => $porSucursal,
            'comparativas' => $comparativas,
            'mensuales' => $mensuales,
            'ganancia' => $ganancia,
            'topGanancia' => $topGanancia,
            'margenDepto' => $margenDepto,
            'ticket' => $ticket,
            'formasPagoLabels' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels(),
        ]);
    }
}
