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
        $adminUser = $auth->requirePermiso('estadisticas');

        $desde = (string)($_GET['desde'] ?? date('Y-m-d'));
        $hasta = (string)($_GET['hasta'] ?? date('Y-m-d'));
        $puntoVenta = (int)($_GET['punto_venta'] ?? 0);

        if (!isset($_GET['desde']) && !isset($_GET['hasta'])) {
            $inicioMes = date('Y-m-01');
            $hoy = date('Y-m-d');
            if (!(new ReporteRepo())->periodoTieneMovimientos($inicioMes, $hoy, $puntoVenta)) {
                $primerDiaMesAnterior = date('Y-m-01', strtotime('first day of last month'));
                $desde = $primerDiaMesAnterior;
                $hasta = date('Y-m-t', strtotime($primerDiaMesAnterior));
            }
        }

        echo View::adminPage('admin/reportes/index.php', [
            'adminUser' => $adminUser,
            'desde' => $desde,
            'hasta' => $hasta,
            'puntoVenta' => $puntoVenta,
            'puntosVenta' => (new ReporteRepo())->puntosVentaDisponibles(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Reportes',
        ]);
    }

    private static function formasPagoLabels(): array
    {
        try {
            return (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels();
        } catch (\Throwable $e) {
            error_log('ReporteController::formasPagoLabels error: ' . $e->getMessage());
            return [];
        }
    }

    public function data(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('estadisticas');

        $desde = (string)($_GET['desde'] ?? date('Y-m-d'));
        $hasta = (string)($_GET['hasta'] ?? date('Y-m-d'));
        // 0 = todos los puntos de venta
        $puntoVenta = (int)($_GET['punto_venta'] ?? 0);

        $repo = new ReporteRepo();
        // La primera consulta de reportes del mes guarda una foto del inventario total,
        // incluso si otro cálculo de reportes falla después.
        $inventario = ['snapshot' => null, 'historial' => []];
        try {
            $inventarioRepo = new \Perfushopping\Web\Repo\ReporteInventarioRepo();
            $inventario['snapshot'] = $inventarioRepo->capturarMesActual((int)$adminUser['id']);
            $inventario['historial'] = $inventarioRepo->historial(24);
        } catch (\Throwable $e) {
            error_log('ReporteController::data inventario error: ' . $e->getMessage());
            $inventario['error'] = 'No se pudo guardar o consultar la captura mensual de inventario.';
        }

        try {
            $step = 'resumen';
            $resumen = $repo->resumenVentas($desde, $hasta, $puntoVenta);
            $step = 'diarias';
            $diarias = $repo->ventasDiarias($desde, $hasta, $puntoVenta);
            $diasConVentas = array_sum(array_map(static fn(array $dia): int => (int)($dia['hubo_ventas'] ?? 0), $diarias));
            $step = 'topProductos';
            $topProductos = $repo->topProductos($desde, $hasta, 15, $puntoVenta);
            $step = 'porDepartamento';
            $porDepartamento = $repo->ventasPorDepartamento($desde, $hasta, $puntoVenta);
            $step = 'porFormaPago';
            $porFormaPago = $repo->ventasPorFormaPago($desde, $hasta, $puntoVenta);
            $step = 'porEquipoTarjeta';
            $porEquipoTarjeta = $repo->ventasTarjetasPorEquipo($desde, $hasta, $puntoVenta);
            $step = 'recibos';
            $recibos = $repo->resumenRecibos($desde, $hasta, $puntoVenta);
            $step = 'porTipo';
            $porTipo = $repo->facturasPorTipo($desde, $hasta, $puntoVenta);
            $step = 'autorizacion';
            $autorizacion = $repo->resumenAutorizacion($desde, $hasta, $puntoVenta);
            $step = 'porVendedor';
            $porVendedor = $repo->ventasPorVendedor($desde, $hasta, $puntoVenta);
            $step = 'porSucursal';
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
            $step = 'comparativas:' . $key;
            $r = $repo->resumenVentas($d, $h, $puntoVenta);
            $comparativas[$key] = [
                'desde' => $d,
                'hasta' => $h,
                'cantidad' => (int)($r['cantidad'] ?? 0),
                'total_cents' => (int)($r['total_cents'] ?? 0),
            ];
        }

        // Serie mensual de 24 meses para el comparativo interanual.
        $step = 'mensuales';
        $desde24 = (clone $ref)->modify('first day of this month')->modify('-23 months')->format('Y-m-d');
        $mensuales = $repo->ventasMensuales($desde24, $ref->format('Y-m-t'), $puntoVenta);

        $step = 'ganancia';
        $ganancia = $repo->ganancia($desde, $hasta, $puntoVenta);
        $step = 'topGanancia';
        $topGanancia = $repo->topGanancia($desde, $hasta, 15, $puntoVenta);
        $step = 'margenDepto';
        $margenDepto = $repo->margenPorDepartamento($desde, $hasta, $puntoVenta);

        $step = 'comprasYGastos';
        $comprasYGastos = $repo->comprasYGastos($desde, $hasta);

        $step = 'total';
        $ticket = ((int)($resumen['cantidad'] ?? 0) > 0)
            ? (int)round((int)($resumen['total_cents'] ?? 0) / (int)$resumen['cantidad'])
            : 0;

        Response::json([
            'ok' => true,
            'resumen' => $resumen,
            'diarias' => $diarias,
            'topProductos' => $topProductos,
            'porDepartamento' => $porDepartamento,
            'porFormaPago' => $porFormaPago,
            'porEquipoTarjeta' => $porEquipoTarjeta,
            'recibos' => $recibos,
            'porTipo' => $porTipo,
            'autorizacion' => $autorizacion ?? ['autorizadas' => [], 'no_autorizadas' => []],
            'porVendedor' => $porVendedor ?? [],
            'dias_con_ventas' => $diasConVentas,
            'porSucursal' => $porSucursal,
            'comparativas' => $comparativas,
            'mensuales' => $mensuales,
            'ganancia' => $ganancia,
            'topGanancia' => $topGanancia,
            'margenDepto' => $margenDepto,
            'comprasYGastos' => $comprasYGastos,
            'inventario' => $inventario,
            'ticket' => $ticket,
            'formasPagoLabels' => self::formasPagoLabels(),
        ]);
        } catch (\Throwable $e) {
            error_log('ReporteController::data error en paso ' . ($step ?? '?') . ': ' . $e->getMessage());
            Response::json(['ok' => false, 'step' => $step ?? '?', 'error' => $e->getMessage()], 500);
        }
    }
}
