<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\CajaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CajaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new CajaRepo();
$sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $fecha = date('Y-m-d');
        $puntoVenta = $auth->getPuntoVenta();

        $apertura = $repo->aperturaActiva($sucursalId, $turno, $fecha);

        // Listado de cajas cerradas (aunque la caja actual esté cerrada)
        $cajasCerradas = [];
        try {
            $stmtCerradas = Db::pdo()->prepare("
                SELECT ca.*, a.nombre AS created_by_nombre
                FROM caja_aperturas ca
                LEFT JOIN admin_users a ON a.id = ca.created_by
                WHERE ca.sucursal_id = :suc
                  AND ca.turno = :tur
                  AND ca.estado = 'cerrada'
                ORDER BY ca.fecha DESC, ca.id DESC
            ");
            $stmtCerradas->execute([':suc' => $sucursalId, ':tur' => $turno]);
            $cajasCerradas = $stmtCerradas->fetchAll() ?: [];
        } catch (\Throwable $e) {
            error_log('CajaController::index cajasCerradas: ' . $e->getMessage());
            $cajasCerradas = [];
        }

        $movimientos = [];
        $totalesMov = ['total_ingresos' => 0, 'total_egresos' => 0];
        $ventasEfectivo = 0;
        $ventasTransferencia = 0;
        $totalRecibos = 0;
        $arqueos = [];

        $detalleTurno = [];
        $totalesForma = [];
        $totalesTarjetaEquipo = [];
        $egresosTurno = 0;

        if ($apertura) {
            $apId = (int)$apertura['id'];
            $apCreada = (string)($apertura['created_at'] ?? $fecha . ' 00:00:00');
            $movimientos = $repo->movimientos($apId);
            $totalesMov = $repo->totalMovimientos($apId);
            $ventasEfectivo = $repo->totalVentasEfectivoTurno($apId, $fecha, $puntoVenta, $apCreada);
            $ventasTransferencia = $repo->totalVentasTransferenciaTurno($apId, $fecha, $puntoVenta, $apCreada);
            $totalRecibos = $repo->totalRecibosTurno($apId, $fecha, $puntoVenta, $apCreada);
            $arqueos = $repo->arqueos($apId);

            foreach ($repo->ventasDetalleTurno($apId, $fecha, $puntoVenta, $apCreada) as $v) {
                $detalleTurno[] = [
                    'hora' => (string)($v['created_at'] ?? ''),
                    'tipo' => 'venta',
                    'detalle' => 'Factura ' . ($v['codigo'] ?? '') . ' — ' . ($v['cliente_nombre'] ?? 'Consumidor Final'),
                    'forma' => (string)($v['forma_pago'] ?? ''),
                    'forma_tipo' => (string)($v['forma_tipo'] ?? ''),
                    'equipo_nombre' => (string)($v['equipo_nombre'] ?? ''),
                    'monto' => (int)($v['monto_cents'] ?? 0),
                ];
            }
            foreach ($repo->recibosDetalleTurno($apId, $fecha, $puntoVenta, $apCreada) as $r) {
                $detalleTurno[] = [
                    'hora' => (string)($r['created_at'] ?? ''),
                    'tipo' => 'cobro',
                    'detalle' => 'Recibo ' . ($r['codigo'] ?? '') . ' — ' . ($r['cliente_nombre'] ?? ''),
                    'forma' => (string)($r['forma_pago'] ?? ''),
                    'monto' => (int)($r['monto_cents'] ?? 0),
                ];
            }
            foreach ($movimientos as $m) {
                $monto = (int)($m['monto_cents'] ?? 0);

                $detalleTurno[] = [
                    'hora' => (string)($m['created_at'] ?? ''),
                    'tipo' => (string)($m['tipo'] ?? 'ingreso'),
                    'detalle' => (string)($m['concepto'] ?? ''),
                    'forma' => '',
                    'monto' => ($m['tipo'] ?? '') === 'egreso' ? -$monto : $monto,
                ];
            }
            usort($detalleTurno, static fn($a, $b) => strcmp((string)$a['hora'], (string)$b['hora']));
            $detalleTurno = array_slice($detalleTurno, 0, 300);

            foreach ($detalleTurno as $d) {
                if ($d['monto'] < 0) {
                    $egresosTurno += -$d['monto'];
                } elseif ($d['forma'] !== '') {
                    $totalesForma[$d['forma']] = ($totalesForma[$d['forma']] ?? 0) + $d['monto'];
                    if (($d['forma_tipo'] ?? '') === 'tarjeta' && $d['monto'] > 0) {
                        $eq = trim((string)($d['equipo_nombre'] ?? ''));
                        if ($eq === '') {
                            $eq = 'Sin equipo';
                        }
                        $totalesTarjetaEquipo[$eq] = ($totalesTarjetaEquipo[$eq] ?? 0) + $d['monto'];
                    }
                }
            }
            ksort($totalesTarjetaEquipo);
        }

        $historial = $repo->historial($sucursalId, 10);
        $ventasPorPuntoVenta = $repo->ventasPorPuntoVenta($fecha);
        $saldoGeneral = $repo->saldoGeneral();

        $ajustePendiente = $apertura ? $repo->ajustePendienteDeCaja((int)$apertura['id']) : null;
        $esAdmin = ($adminUser['rol'] ?? '') === 'superadmin';
        $ajustesPendientesCount = $esAdmin ? count($repo->ajustesPendientes()) : 0;
        echo View::adminPage('admin/caja/index.php', [
            'adminUser' => $adminUser,
            'apertura' => $apertura,
            'movimientos' => $movimientos,
            'totalesMov' => $totalesMov,
            'ventasEfectivo' => $ventasEfectivo,
            'ventasTransferencia' => $ventasTransferencia,
            'totalRecibos' => $totalRecibos,
            'ventasPorPuntoVenta' => $ventasPorPuntoVenta,
            'saldoGeneral' => $saldoGeneral,
            'detalleTurno' => $detalleTurno,
            'totalesForma' => $totalesForma,
            'totalesTarjetaEquipo' => $totalesTarjetaEquipo,
            'egresosTurno' => $egresosTurno,
            'formasPagoLabels' => (new \Perfushopping\Web\Repo\FormaPagoRepo())->labels(),
            'ajustePendiente' => $ajustePendiente,
            'esAdmin' => $esAdmin,
            'ajustesPendientesCount' => $ajustesPendientesCount,
            'arqueos' => $arqueos,
            'historial' => $historial,
            'cajasCerradas' => $cajasCerradas,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Caja',
        ]);
    }

    public function abrirForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $fecha = date('Y-m-d');

        $apertura = $repo->aperturaActiva($sucursalId, $turno, $fecha);
        if ($apertura) {
            $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Ya hay una caja abierta para este turno.'];
            Response::redirect('/admin/caja');
        }

        echo View::adminPage('admin/caja/abrir.php', [
            'adminUser' => $adminUser,
            'saldoSugerido' => $repo->ultimoCierreConSaldo($sucursalId),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Abrir caja',
        ]);
    }

    public function abrirStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $montoInicial = (int)($_POST['monto_inicial_cents'] ?? 0);
        if ($montoInicial < 0) $montoInicial = 0;
        $obs = trim((string)($_POST['observaciones'] ?? ''));
        $detalle = trim((string)($_POST['detalle_efectivo'] ?? ''));
        if ($detalle !== '') {
            $decoded = json_decode($detalle, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $lines = [];
                foreach ($decoded as $d) {
                    $denom = (int)($d['denominacion'] ?? 0);
                    $qty = (int)($d['cantidad'] ?? 0);
                    if ($denom > 0 && $qty > 0) {
                        $lines[] = '$' . number_format($denom, 0, ',', '.') . ' x ' . $qty . ' = $' . number_format($denom * $qty, 0, ',', '.');
                    }
                }
                if ($lines) {
                    $detalleStr = 'Detalle apertura: ' . implode(' | ', $lines);
                    $obs = $obs ? $obs . "\n" . $detalleStr : $detalleStr;
                }
            }
        }

        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $fecha = date('Y-m-d');

        $repo = new CajaRepo();
        $existing = $repo->aperturaActiva($sucursalId, $turno, $fecha);
        if ($existing) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Ya hay una caja abierta.'];
            Response::redirect('/admin/caja');
        }

        $id = $repo->abrir($sucursalId, $turno, $fecha, $montoInicial, $obs ?: null, (int)$adminUser['id']);
        $_SESSION['admin_caja_id'] = $id;

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Caja abierta con $' . number_format($montoInicial / 100, 2, ',', '.') . ' iniciales.'];
        Response::redirect('/admin/caja');
    }

    public function movimientos(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'No hay caja abierta. Abrí una primero.'];
            Response::redirect('/admin/caja/abrir');
        }

        $movimientos = $repo->movimientos((int)$apertura['id']);
        $totalesMov = $repo->totalMovimientos((int)$apertura['id']);

        echo View::adminPage('admin/caja/movimientos.php', [
            'adminUser' => $adminUser,
            'apertura' => $apertura,
            'movimientos' => $movimientos,
            'totalesMov' => $totalesMov,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Movimientos de caja',
        ]);
    }

    public function storeMovimiento(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $tipo = (string)($_POST['tipo'] ?? '');
        $concepto = trim((string)($_POST['concepto'] ?? ''));
        $monto = self::pesosACents($_POST['monto_cents'] ?? 0);
        $cajaDestino = (string)($_POST['caja_destino'] ?? 'chica');

        if (!in_array($tipo, ['ingreso', 'egreso'], true) || $concepto === '' || $monto <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos.'];
            Response::redirect('/admin/caja/movimientos');
        }

        $repo = new CajaRepo();

        if ($cajaDestino === 'general') {
            $repo->agregarMovimientoGeneral($tipo, 'directo', null, $concepto, $monto, (int)$adminUser['id']);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Movimiento registrado en Caja General.'];
            Response::redirect('/admin/caja/general');
        }

        // Default: caja chica
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay caja abierta. Abrí una primero o seleccioná Caja General.'];
            Response::redirect('/admin/caja/abrir');
        }

        $repo->agregarMovimiento((int)$apertura['id'], $tipo, $concepto, $monto, (int)$adminUser['id']);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Movimiento registrado en Caja Chica.'];
        Response::redirect('/admin/caja/movimientos');
    }

    public function general(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $tipo = trim((string)($_GET['tipo'] ?? ''));
        $desde = trim((string)($_GET['desde'] ?? ''));
        $hasta = trim((string)($_GET['hasta'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));

        $repo = new CajaRepo();
        $movimientos = $repo->movimientosGenerales($tipo ?: null, $desde ?: null, $hasta ?: null, $q);
        $totales = $repo->totalMovimientosGenerales($desde ?: null, $hasta ?: null);
        $totalesControl = $repo->totalesGeneralesControl($desde ?: null, $hasta ?: null);
        $saldo = $repo->saldoGeneral();

        // Efectivo generado desde las cajas (fecha, hora, pto. vta., caja) + control
        $cierres = $repo->cierresEfectivo($desde ?: null, $hasta ?: null, 50);
        foreach ($cierres as &$c) {
            $c['efectivo'] = $repo->efectivoCierre($c, (int)($c['pto_vta'] ?? 0));
        }
        unset($c);

        // Gastos pagados con caja general discriminados por forma de pago
        $gastosPorForma = (new \Perfushopping\Web\Repo\GastoRepo())->totalesCajaGeneralPorForma($desde ?: null, $hasta ?: null);

        echo View::adminPage('admin/caja/general.php', [
            'adminUser' => $adminUser,
            'movimientos' => $movimientos,
            'totales' => $totales,
            'totalesControl' => $totalesControl,
            'cierres' => $cierres,
            'gastosPorForma' => $gastosPorForma,
            'saldo' => $saldo,
            'tipo' => $tipo,
            'desde' => $desde,
            'hasta' => $hasta,
            'q' => $q,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Caja General',
        ]);
    }

    public function storeGeneralMovimiento(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $tipo = (string)($_POST['tipo'] ?? '');
        $concepto = trim((string)($_POST['concepto'] ?? ''));
        $monto = self::pesosACents($_POST['monto_cents'] ?? 0);

        if (!in_array($tipo, ['ingreso', 'egreso'], true) || $concepto === '' || $monto <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos.'];
            Response::redirect('/admin/caja/general');
        }

        $repo = new CajaRepo();
        $repo->agregarMovimientoGeneral($tipo, 'directo', null, $concepto, $monto, (int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Movimiento registrado en Caja General.'];
        Response::redirect('/admin/caja/general');
    }

    public function controlarMovimiento(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $accion = (string)($_POST['accion'] ?? 'controlar');

        if ($id <= 0) {
            Response::redirect('/admin/caja/general');
        }

        $repo = new CajaRepo();
        if ($accion === 'descontrolar') {
            $repo->descontrolarMovimientoGeneral($id);
        } else {
            $repo->controlarMovimientoGeneral($id, (int)$adminUser['id']);
        }

        Response::redirect('/admin/caja/general');
    }

    public function controlarCierre(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $accion = (string)($_POST['accion'] ?? 'controlar');

        if ($id <= 0) {
            Response::redirect('/admin/caja/general');
        }

        $repo = new CajaRepo();
        if ($accion === 'descontrolar') {
            $repo->descontrolarCierre($id);
        } else {
            $repo->controlarCierre($id, (int)$adminUser['id']);
        }

        Response::redirect('/admin/caja/general');
    }

    public function arqueoForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'No hay caja abierta.'];
            Response::redirect('/admin/caja/abrir');
        }

        $puntoVenta = $auth->getPuntoVenta();
        $apId = (int)$apertura['id'];
        $apCreada = (string)($apertura['created_at'] ?? date('Y-m-d') . ' 00:00:00');
        $ventasEfectivo = $repo->totalVentasEfectivoTurno($apId, date('Y-m-d'), $puntoVenta, $apCreada);
        $totalesMov = $repo->totalMovimientos($apId);
        $arqueos = $repo->arqueos($apId);

        echo View::adminPage('admin/caja/arqueo.php', [
            'adminUser' => $adminUser,
            'apertura' => $apertura,
            'ventasEfectivo' => $ventasEfectivo,
            'totalesMov' => $totalesMov,
            'arqueos' => $arqueos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Arqueo de caja',
        ]);
    }

    public function storeArqueo(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $totalCents = self::pesosACents($_POST['total_cents'] ?? 0);
        $obs = trim((string)($_POST['observaciones'] ?? ''));
        $detalle = trim((string)($_POST['detalle_efectivo'] ?? ''));
        if ($detalle !== '') {
            $decoded = json_decode($detalle, true);
            if (is_array($decoded) && count($decoded) > 0) {
                $lines = [];
                foreach ($decoded as $d) {
                    $denom = (int)($d['denominacion'] ?? 0);
                    $qty = (int)($d['cantidad'] ?? 0);
                    if ($denom > 0 && $qty > 0) {
                        $lines[] = '$' . number_format($denom, 0, ',', '.') . ' x ' . $qty . ' = $' . number_format($denom * $qty, 0, ',', '.');
                    }
                }
                if ($lines) {
                    $detalleStr = 'Detalle conteo: ' . implode(' | ', $lines);
                    $obs = $obs ? $obs . "\n" . $detalleStr : $detalleStr;
                }
            }
        }

        if ($totalCents < 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El total debe ser mayor o igual a 0.'];
            Response::redirect('/admin/caja/arqueo');
        }

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay caja abierta.'];
            Response::redirect('/admin/caja/abrir');
        }

        $repo->registrarArqueo((int)$apertura['id'], $totalCents, $obs, (int)$adminUser['id']);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Arqueo registrado.'];
        Response::redirect('/admin/caja');
    }

    public function cierreForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'No hay caja abierta para cerrar.'];
            Response::redirect('/admin/caja');
        }

        $puntoVenta = $auth->getPuntoVenta();
        $apId = (int)$apertura['id'];
        $apCreada = (string)($apertura['created_at'] ?? date('Y-m-d') . ' 00:00:00');
        $fecha = date('Y-m-d');
        if (!$repo->arqueos($apId)) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'Hacé un arqueo antes de cerrar la caja.'];
            Response::redirect('/admin/caja/arqueo');
        }
        $ventasEfectivo = $repo->totalVentasEfectivoTurno($apId, $fecha, $puntoVenta, $apCreada);
        $ventasTransferencia = $repo->totalVentasTransferenciaTurno($apId, $fecha, $puntoVenta, $apCreada);
        $totalRecibos = $repo->totalRecibosTurno($apId, $fecha, $puntoVenta, $apCreada);
        $totalesMov = $repo->totalMovimientos($apId);

        $montoInicial = (int)$apertura['monto_inicial_cents'];
        $esperadoEfectivo = $montoInicial + $ventasEfectivo + (int)$totalesMov['total_ingresos'] - (int)$totalesMov['total_egresos'];

        echo View::adminPage('admin/caja/cierre.php', [
            'adminUser' => $adminUser,
            'apertura' => $apertura,
            'ventasEfectivo' => $ventasEfectivo,
            'ventasTransferencia' => $ventasTransferencia,
            'totalRecibos' => $totalRecibos,
            'totalesMov' => $totalesMov,
            'esperadoEfectivo' => $esperadoEfectivo,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cierre de caja',
        ]);
    }

    public function cierreStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $montoCierre = self::pesosACents($_POST['monto_cierre_cents'] ?? 0);
        $montoRetirado = self::pesosACents($_POST['monto_retirado_cents'] ?? 0);
        $proximaApertura = self::pesosACents($_POST['monto_proxima_cents'] ?? 0);
        if ($montoCierre < 0) $montoCierre = 0;
        if ($montoRetirado < 0) $montoRetirado = 0;
        if ($proximaApertura < 0) $proximaApertura = 0;

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $fecha = date('Y-m-d');
        $apertura = $repo->aperturaActiva($sucursalId, $turno, $fecha);

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay caja abierta.'];
            Response::redirect('/admin/caja');
        }
        if (!$repo->arqueos((int)$apertura['id'])) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se puede cerrar sin arqueo. Registrá un arqueo primero.'];
            Response::redirect('/admin/caja/arqueo');
        }

        // Efectivo disponible del turno: solo efectivo puede pasar a Caja General.
        $apId = (int)$apertura['id'];
        $apCreada = (string)($apertura['created_at'] ?? $fecha . ' 00:00:00');
        $puntoVenta = $auth->getPuntoVenta();
        $ventasEfectivo = $repo->totalVentasEfectivoTurno($apId, $fecha, $puntoVenta, $apCreada);
        $totalesMov = $repo->totalMovimientos($apId);
        $efectivoDisponible = (int)$apertura['monto_inicial_cents'] + $ventasEfectivo
            + (int)$totalesMov['total_ingresos'] - (int)$totalesMov['total_egresos'];

        if ($montoRetirado > $efectivoDisponible) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El pasaje a Caja General (solo efectivo) no puede superar el efectivo disponible: $' . number_format($efectivoDisponible / 100, 2, ',', '.') . '.'];
            Response::redirect('/admin/caja/cierre');
        }
        if ($montoRetirado + $proximaApertura > $efectivoDisponible) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Pasaje + saldo para próxima apertura no pueden superar el efectivo disponible: $' . number_format($efectivoDisponible / 100, 2, ',', '.') . '.'];
            Response::redirect('/admin/caja/cierre');
        }

        $repo->cerrar($apId, $montoCierre, (int)$adminUser['id'], $montoRetirado, $proximaApertura);

        // Register in caja general as ingreso from cierre
        if ($montoRetirado > 0) {
            $codigo = $apertura['codigo'] ?? ('CAJA-' . $apertura['id']);
            $repo->agregarMovimientoGeneral(
                'ingreso',
                'cierre_caja',
                $apId,
                'Retiro cierre caja ' . date('d/m/Y') . ' ' . ($turno === 'manana' ? 'Mañana' : 'Tarde'),
                $montoRetirado,
                (int)$adminUser['id']
            );
        }

        // Marcar facturas y recibos del turno como imputados a este cierre.
        $repo->marcarDocumentosCierre($apId, $fecha, $puntoVenta, $apCreada);

        unset($_SESSION['admin_caja_id']);

        $msg = 'Caja cerrada. Monto final: $' . number_format($montoCierre / 100, 2, ',', '.');
        if ($montoRetirado > 0) {
            $msg .= ' | Pasaje a Caja General (efectivo): $' . number_format($montoRetirado / 100, 2, ',', '.');
        }
        if ($proximaApertura > 0) {
            $msg .= ' | Saldo para próxima apertura: $' . number_format($proximaApertura / 100, 2, ',', '.');
        }
        $msg .= ' | Documentos del turno imputados al cierre #' . $apId . '.';
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => $msg];
        Response::redirect('/admin/caja');
    }

    public function solicitarAjusteForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new CajaRepo();
        $apertura = $repo->aperturaActiva($auth->getSucursalId(), $auth->getTurno(), date('Y-m-d'));
        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'No hay caja abierta.'];
            Response::redirect('/admin/caja');
        }
        if ($repo->ajustePendienteDeCaja((int)$apertura['id'])) {
            $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Ya hay una corrección pendiente de aprobación para esta apertura.'];
            Response::redirect('/admin/caja');
        }

        echo View::adminPage('admin/caja/ajuste.php', [
            'adminUser' => $adminUser,
            'apertura' => $apertura,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Solicitar corrección de apertura',
        ]);
    }

    public function solicitarAjusteStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $repo = new CajaRepo();
        $apertura = $repo->aperturaActiva($auth->getSucursalId(), $auth->getTurno(), date('Y-m-d'));
        if (!$apertura || ($apertura['estado'] ?? '') !== 'abierta') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay caja abierta para corregir.'];
            Response::redirect('/admin/caja');
        }
        if ($repo->ajustePendienteDeCaja((int)$apertura['id'])) {
            $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Ya hay una corrección pendiente de aprobación.'];
            Response::redirect('/admin/caja');
        }

        $nuevoCents = self::pesosACents($_POST['monto_nuevo_cents'] ?? 0);
        $motivo = trim((string)($_POST['motivo'] ?? ''));
        $actualCents = (int)($apertura['monto_inicial_cents'] ?? 0);

        if ($nuevoCents < 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El monto debe ser mayor o igual a 0.'];
            Response::redirect('/admin/caja/apertura/ajuste');
        }
        if ($nuevoCents === $actualCents) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El monto nuevo es igual al actual.'];
            Response::redirect('/admin/caja/apertura/ajuste');
        }
        if (mb_strlen($motivo) < 10) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Describí el error (mínimo 10 caracteres).'];
            Response::redirect('/admin/caja/apertura/ajuste');
        }

        $repo->solicitarAjusteApertura((int)$apertura['id'], $nuevoCents, $motivo, (int)$adminUser['id']);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Corrección enviada. Queda pendiente hasta que un administrador la apruebe.'];
        Response::redirect('/admin/caja');
    }

    public function solicitarAjusteCierreForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $cajaId = (int)($params['id'] ?? 0);
        $repo = new CajaRepo();
        $caja = $cajaId > 0 ? $repo->findById($cajaId) : null;
        if (!$caja || ($caja['estado'] ?? '') !== 'cerrada') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solo se puede corregir un cierre ya realizado.'];
            Response::redirect('/admin/caja');
        }

        echo View::adminPage('admin/caja/ajuste-cierre.php', [
            'adminUser' => $adminUser,
            'caja' => $caja,
            'campos' => CajaRepo::CAMPOS_AJUSTE_CIERRE,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Solicitar corrección de cierre',
        ]);
    }

    public function solicitarAjusteCierreStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $cajaId = (int)($_POST['caja_id'] ?? 0);
        $repo = new CajaRepo();
        $caja = $cajaId > 0 ? $repo->findById($cajaId) : null;
        if (!$caja || ($caja['estado'] ?? '') !== 'cerrada') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solo se puede corregir un cierre ya realizado.'];
            Response::redirect('/admin/caja');
        }

        $motivo = trim((string)($_POST['motivo'] ?? ''));
        if (mb_strlen($motivo) < 10) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Describí el error (mínimo 10 caracteres).'];
            Response::redirect('/admin/caja/cierre/' . $cajaId . '/ajuste');
        }

        $creadas = 0;
        foreach (CajaRepo::CAMPOS_AJUSTE_CIERRE as $campo => $label) {
            if (!isset($_POST[$campo])) {
                continue;
            }
            $nuevo = self::pesosACents($_POST[$campo]);
            if ($nuevo < 0) {
                continue;
            }
            $actual = (int)($caja[$campo] ?? 0);
            if ($nuevo === $actual) {
                continue;
            }
            if ($repo->ajustePendienteDeCajaPorCampo($cajaId, $campo)) {
                continue;
            }
            try {
                $repo->solicitarAjusteCierre($cajaId, $campo, $nuevo, $motivo, (int)$adminUser['id']);
                $creadas++;
            } catch (\Throwable $e) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
                Response::redirect('/admin/caja');
            }
        }

        if ($creadas > 0) {
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Corrección enviada. Queda pendiente hasta que un administrador la apruebe.'];
        } else {
            $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'No hay cambios respecto a los valores actuales o ya hay solicitudes pendientes.'];
        }
        Response::redirect('/admin/caja');
    }

    public function ajustes(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');

        $repo = new CajaRepo();
        echo View::adminPage('admin/caja/ajustes.php', [
            'adminUser' => $adminUser,
            'pendientes' => $repo->ajustesPendientes(),
            'historial' => $repo->ajustesHistorial(20),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Aprobación de correcciones de caja',
        ]);
    }

    public function cierrePrint(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $fecha = date('Y-m-d');
        $apertura = $repo->aperturaActiva($sucursalId, $turno, $fecha);

        $empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();
        $sucursalRepo = new \Perfushopping\Web\Repo\SucursalRepo();
        $sucursal = null;
        if (!empty($sucursalId)) {
            $sucursal = $sucursalRepo->findById($sucursalId);
        }

        // Totales del turno
        $apId = $apertura['id'] ?? 0;
        $ventasEfectivo = $repo->totalVentasEfectivoTurno($apId, $fecha, $puntoVenta = $auth->getPuntoVenta(), $apCreada = (string)($apertura['created_at'] ?? date('Y-m-d') . ' 00:00:00'));
        $totalesMov = $repo->totalMovimientos($apId);
        $efectivoDisponible = (int)($apertura['monto_inicial_cents'] ?? 0) + $ventasEfectivo
            + (int)($totalesMov['total_ingresos'] ?? 0) - (int)($totalesMov['total_egresos'] ?? 0);

        $montoCierre = (int)($apertura['monto_cierre_cents'] ?? 0);
        $montoRetirado = (int)($apertura['monto_retirado_cents'] ?? 0);
        $proximaApertura = (int)($apertura['monto_proxima_apertura_cents'] ?? 0);

        echo View::adminPage('admin/caja/cierre_print.php', [
            'adminUser' => $adminUser,
            'empresa' => $empresa,
            'sucursal' => $sucursal,
            'fecha' => $fecha,
            'turno' => $turno,
            'apertura' => $apertura,
            'ventasEfectivo' => $ventasEfectivo,
            'totalIngresos' => $totalesMov['total_ingresos'] ?? 0,
            'totalEgresos' => $totalesMov['total_egresos'] ?? 0,
            'efectivoDisponible' => $efectivoDisponible,
            'montoCierre' => $montoCierre,
            'montoRetirado' => $montoRetirado,
            'proximaApertura' => $proximaApertura,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Resumen de cierre de caja',
        ]);
    }

    public function resolverAjusteStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $accion = (string)($_POST['accion'] ?? '');
        $nota = trim((string)($_POST['nota'] ?? '')) ?: null;
        $estado = $accion === 'aprobar' ? 'aprobado' : ($accion === 'rechazar' ? 'rechazado' : '');

        if ($id <= 0 || $estado === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solicitud inválida.'];
            Response::redirect('/admin/caja/ajustes');
        }

        try {
            (new CajaRepo())->resolverAjuste($id, $estado, (int)$adminUser['id'], $nota);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => $estado === 'aprobado' ? 'Corrección aprobada y aplicada a la apertura.' : 'Corrección rechazada.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/caja/ajustes');
    }

    public function diagnostico(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('caja_movimientos');

        header('Content-Type: text/plain; charset=utf-8');

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $fecha = date('Y-m-d');
        $puntoVenta = $auth->getPuntoVenta();

        echo "fecha PHP: {$fecha} " . date('H:i:s') . "\n";
        echo "punto_venta sesion: {$puntoVenta} | sucursal: {$sucursalId} | turno: {$turno}\n\n";

        $apertura = $repo->aperturaActiva($sucursalId, $turno, $fecha);
        if (!$apertura) {
            echo "SIN APERTURA ACTIVA\n";
            return;
        }
        $apId = (int)$apertura['id'];
        $apCreada = (string)($apertura['created_at'] ?? '');
        echo "apertura id: {$apId} | estado: " . ($apertura['estado'] ?? '') . " | created_at: {$apCreada}\n";
        echo "monto_inicial_cents: " . ($apertura['monto_inicial_cents'] ?? '?') . "\n\n";

        $pdo = \Perfushopping\Web\Infra\Db::pdo();
        try {
            $n = $pdo->query('SELECT COUNT(*) FROM formas_pago')->fetchColumn();
            echo "formas_pago: existe, filas = {$n}\n";
        } catch (\Throwable $e) {
            echo "formas_pago: NO EXISTE (" . $e->getMessage() . ")\n";
        }

        try {
            $st = $pdo->prepare("
                SELECT COUNT(*), COALESCE(SUM(total_cents), 0)
                FROM facturas
                WHERE estado = 'emitida' AND fecha = :fec AND punto_venta = :pv
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
            $r = $st->fetch(\PDO::FETCH_NUM) ?: [0, 0];
            echo "facturas hoy+PV: cant = {$r[0]}, total = {$r[1]}\n";
        } catch (\Throwable $e) {
            echo "facturas hoy+PV: ERROR " . $e->getMessage() . "\n";
        }

        try {
            $st = $pdo->prepare("
                SELECT fp.forma_pago, COUNT(*), COALESCE(SUM(fp.monto_cents), 0)
                FROM factura_pagos fp
                INNER JOIN facturas f ON f.id = fp.factura_id
                WHERE f.estado = 'emitida' AND f.fecha = :fec AND f.punto_venta = :pv
                GROUP BY fp.forma_pago
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
            echo "--- por forma_pago ---\n";
            foreach ($st->fetchAll(\PDO::FETCH_NUM) as $x) {
                echo "  {$x[0]}: cant={$x[1]} suma={$x[2]}\n";
            }
        } catch (\Throwable $e) {
            echo "por forma_pago: ERROR " . $e->getMessage() . "\n";
        }

        try {
            $st = $pdo->prepare("
                SELECT
                    SUM(CASE WHEN caja_apertura_id IS NULL THEN 1 ELSE 0 END),
                    SUM(CASE WHEN caja_apertura_id = :caja THEN 1 ELSE 0 END)
                FROM facturas
                WHERE estado = 'emitida' AND fecha = :fec AND punto_venta = :pv
            ");
            $st->execute([':caja' => $apId, ':fec' => $fecha, ':pv' => $puntoVenta]);
            $r = $st->fetch(\PDO::FETCH_NUM) ?: [0, 0];
            echo "sin imputar: {$r[0]} | imputadas a este cierre: {$r[1]}\n";
        } catch (\Throwable $e) {
            echo "imputacion: ERROR " . $e->getMessage() . "\n";
        }

        $base = "
            FROM factura_pagos fp
            INNER JOIN facturas f ON f.id = fp.factura_id
            WHERE f.estado = 'emitida' AND f.fecha = :fec AND f.punto_venta = :pv
              AND fp.forma_pago = 'efectivo'
        ";
        $prms = [':fec' => $fecha, ':pv' => $puntoVenta];
        $variantes = [
            'V1 base' => '',
            'V2 +extra entrega' => " AND NOT (f.entrega_tipo='envio' AND f.envio_estado IN ('pendiente','en_transito') AND fp.forma_pago='efectivo')",
            'V3 +tipo' => " AND COALESCE((SELECT fpm.tipo FROM formas_pago fpm WHERE fpm.codigo = fp.forma_pago LIMIT 1), 'efectivo') = 'efectivo'",
            'V4 +turno' => " AND (f.caja_apertura_id = :caja OR (f.caja_apertura_id IS NULL AND f.created_at >= :apCreada))",
        ];
        foreach ($variantes as $nombre => $w) {
            try {
                $st = $pdo->prepare("SELECT COALESCE(SUM(fp.monto_cents), 0) " . $base . $w);
                $p = $prms;
                if (str_contains($w, ':caja')) {
                    $p[':caja'] = $apId;
                    $p[':apCreada'] = $apCreada;
                }
                $st->execute($p);
                echo "{$nombre}: " . $st->fetchColumn() . "\n";
            } catch (\Throwable $e) {
                echo "{$nombre}: ERROR " . $e->getMessage() . "\n";
            }
        }

        echo "ventasEfectivoTurno: " . $repo->totalVentasEfectivoTurno($apId, $fecha, $puntoVenta, $apCreada) . "\n";
        echo "ventasTransferenciaTurno: " . $repo->totalVentasTransferenciaTurno($apId, $fecha, $puntoVenta, $apCreada) . "\n";

        try {
            $st = $pdo->prepare("
                SELECT id, codigo, fecha, created_at, punto_venta, caja_apertura_id, total_cents
                FROM facturas
                WHERE estado = 'emitida' AND fecha = :fec AND punto_venta = :pv
                ORDER BY id DESC LIMIT 5
            ");
            $st->execute([':fec' => $fecha, ':pv' => $puntoVenta]);
            echo "--- ultimas 5 ---\n";
            foreach ($st->fetchAll(\PDO::FETCH_NUM) as $x) {
                echo "  id={$x[0]} cod={$x[1]} fecha={$x[2]} created={$x[3]} pv={$x[4]} caja=" . ($x[5] ?? 'NULL') . " total={$x[6]}\n";
            }
        } catch (\Throwable $e) {
            echo "ultimas: ERROR " . $e->getMessage() . "\n";
        }
        exit;
    }

    /**
     * Los formularios de caja trabajan en pesos; la DB guarda centavos.
     * Acepta "43550", "43550.50" o "43.550,50".
     */
    private static function pesosACents(mixed $value): int
    {
        $s = trim((string)$value);
        if ($s === '') {
            return 0;
        }
        $s = str_replace(' ', '', $s);
        if (str_contains($s, ',')) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        }
        return (int)round((float)$s * 100);
    }
}
