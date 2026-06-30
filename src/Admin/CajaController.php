<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

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
        $adminUser = $auth->requireSesion();

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $fecha = date('Y-m-d');
        $puntoVenta = $auth->getPuntoVenta();

        $apertura = $repo->aperturaActiva($sucursalId, $turno, $fecha);
        $movimientos = [];
        $totalesMov = ['total_ingresos' => 0, 'total_egresos' => 0];
        $ventasEfectivo = 0;
        $ventasTransferencia = 0;
        $totalRecibos = 0;
        $arqueos = [];

        if ($apertura) {
            $movimientos = $repo->movimientos((int)$apertura['id']);
            $totalesMov = $repo->totalMovimientos((int)$apertura['id']);
            $ventasEfectivo = $repo->totalVentasEfectivo($fecha, $puntoVenta);
            $ventasTransferencia = $repo->totalVentasTransferencia($fecha, $puntoVenta);
            $totalRecibos = $repo->totalRecibos($fecha, $puntoVenta);
            $arqueos = $repo->arqueos((int)$apertura['id']);
        }

        $historial = $repo->historial($sucursalId, 10);

        echo View::adminPage('admin/caja/index.php', [
            'adminUser' => $adminUser,
            'apertura' => $apertura,
            'movimientos' => $movimientos,
            'totalesMov' => $totalesMov,
            'ventasEfectivo' => $ventasEfectivo,
            'ventasTransferencia' => $ventasTransferencia,
            'totalRecibos' => $totalRecibos,
            'arqueos' => $arqueos,
            'historial' => $historial,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Caja',
        ]);
    }

    public function abrirForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

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
            'csrf' => Csrf::token(),
            'pageTitle' => 'Abrir caja',
        ]);
    }

    public function abrirStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $montoInicial = (int)($_POST['monto_inicial_cents'] ?? 0);
        if ($montoInicial < 0) $montoInicial = 0;
        $obs = trim((string)($_POST['observaciones'] ?? ''));

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
        $adminUser = $auth->requireSesion();

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
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $tipo = (string)($_POST['tipo'] ?? '');
        $concepto = trim((string)($_POST['concepto'] ?? ''));
        $monto = (int)($_POST['monto_cents'] ?? 0);

        if (!in_array($tipo, ['ingreso', 'egreso'], true) || $concepto === '' || $monto <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos.'];
            Response::redirect('/admin/caja/movimientos');
        }

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay caja abierta.'];
            Response::redirect('/admin/caja/abrir');
        }

        $repo->agregarMovimiento((int)$apertura['id'], $tipo, $concepto, $monto, (int)$adminUser['id']);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Movimiento registrado.'];
        Response::redirect('/admin/caja/movimientos');
    }

    public function arqueoForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'No hay caja abierta.'];
            Response::redirect('/admin/caja/abrir');
        }

        $puntoVenta = $auth->getPuntoVenta();
        $ventasEfectivo = $repo->totalVentasEfectivo(date('Y-m-d'), $puntoVenta);
        $totalesMov = $repo->totalMovimientos((int)$apertura['id']);
        $arqueos = $repo->arqueos((int)$apertura['id']);

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
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $totalCents = (int)($_POST['total_cents'] ?? 0);
        $obs = trim((string)($_POST['observaciones'] ?? ''));

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
        $adminUser = $auth->requireSesion();

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'No hay caja abierta para cerrar.'];
            Response::redirect('/admin/caja');
        }

        $puntoVenta = $auth->getPuntoVenta();
        $ventasEfectivo = $repo->totalVentasEfectivo(date('Y-m-d'), $puntoVenta);
        $ventasTransferencia = $repo->totalVentasTransferencia(date('Y-m-d'), $puntoVenta);
        $totalRecibos = $repo->totalRecibos(date('Y-m-d'), $puntoVenta);
        $totalesMov = $repo->totalMovimientos((int)$apertura['id']);

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
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $montoCierre = (int)($_POST['monto_cierre_cents'] ?? 0);
        if ($montoCierre < 0) $montoCierre = 0;

        $repo = new CajaRepo();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();
        $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));

        if (!$apertura) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No hay caja abierta.'];
            Response::redirect('/admin/caja');
        }

        $repo->cerrar((int)$apertura['id'], $montoCierre, (int)$adminUser['id']);
        unset($_SESSION['admin_caja_id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Caja cerrada. Monto final: $' . number_format($montoCierre / 100, 2, ',', '.')];
        Response::redirect('/admin/caja');
    }
}
