<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\PrintJobRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class PrintConfigController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        echo View::adminPage('admin/impresion/config.php', [
            'adminUser' => $adminUser,
            'pageTitle' => 'Configuración de impresión',
        ]);
    }

    /** ABM de impresoras de tickets por punto de venta. */
    public function impresoras(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $repo = new PrintJobRepo();
        $sucursales = [];
        try {
            $sucursales = (new \Perfushopping\Web\Repo\SucursalRepo())->findAll();
        } catch (\Throwable $e) {
        }

        echo View::adminPage('admin/impresion/impresoras.php', [
            'adminUser' => $adminUser,
            'list' => $repo->impresorasTodas(),
            'sucursales' => $sucursales,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Impresoras de tickets',
        ]);
    }

    public function impresoraSave(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0) ?: null;
        try {
            (new PrintJobRepo())->guardarImpresora(
                $id,
                trim((string)($_POST['nombre'] ?? '')),
                (int)($_POST['punto_venta'] ?? 0),
                (int)($_POST['sucursal_id'] ?? 0) ?: null,
                isset($_POST['activo']) ? 1 : 0,
                trim((string)($_POST['formato'] ?? '80mm'))
            );
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Impresora guardada.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/impresion/impresoras');
    }

    public function impresoraToggle(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new PrintJobRepo())->toggleImpresora($id);
        }
        Response::redirect('/admin/impresion/impresoras');
    }

    public function impresoraDelete(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new PrintJobRepo())->eliminarImpresora($id);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Impresora eliminada.'];
        }
        Response::redirect('/admin/impresion/impresoras');
    }

    /**
     * Spooler: página que se deja abierta en la PC del punto de venta.
     * Toma los pendientes de la cola e imprime solo cada factura.
     */
    public function spooler(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('caja_movimientos');

        $puntoVenta = $auth->getPuntoVenta();
        $repo = new PrintJobRepo();

        echo View::adminPage('admin/impresion/spooler.php', [
            'adminUser' => $adminUser,
            'puntoVenta' => $puntoVenta,
            'impresora' => $repo->impresoraActivaPorPV($puntoVenta),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Spooler de tickets — PV ' . $puntoVenta,
        ]);
    }

    public function colaData(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('caja_movimientos');

        $puntoVenta = $auth->getPuntoVenta();
        $repo = new PrintJobRepo();
        Response::json([
            'ok' => true,
            'punto_venta' => $puntoVenta,
            'impresora' => $repo->impresoraActivaPorPV($puntoVenta),
            'pendientes' => $repo->pendientesPorPV($puntoVenta, 20),
        ]);
    }

    public function colaAck(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('caja_movimientos');

        $input = json_decode(file_get_contents('php://input'), true);
        Csrf::check($input['_csrf'] ?? null);
        if (!is_array($input)) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        $jobId = (int)($input['job_id'] ?? 0);
        $estado = (string)($input['estado'] ?? '');
        if ($jobId <= 0 || !in_array($estado, ['impreso', 'error', 'pendiente'], true)) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }
        (new PrintJobRepo())->marcar($jobId, $estado, trim((string)($input['mensaje'] ?? '')) ?: null);
        Response::json(['ok' => true]);
    }

    /**
     * API para agentes locales de impresión (autenticación por token de impresora).
     * GET /api/print/jobs?token=XXX — pendientes del punto de venta.
     */
    public function apiJobs(array $params): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $imp = (new PrintJobRepo())->impresoraPorToken($token);
        if (!$imp) {
            Response::json(['ok' => false, 'error' => 'Token inválido.'], 401);
            return;
        }

        $repo = new PrintJobRepo();
        $facturaRepo = new \Perfushopping\Web\Repo\FacturaRepo();
        $empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();
        $jobs = [];
        foreach ($repo->pendientesPorPV((int)$imp['punto_venta'], 20) as $j) {
            $factura = $facturaRepo->findById((int)$j['factura_id']);
            if (!$factura) {
                continue;
            }
            $sucursal = null;
            try {
                $sucRepo = new \Perfushopping\Web\Repo\SucursalRepo();
                if (!empty($factura['sucursal_id'])) {
                    $sucursal = $sucRepo->findById((int)$factura['sucursal_id']);
                }
                if (!$sucursal) {
                    $sucursal = $sucRepo->findByPuntoVenta((int)($factura['punto_venta'] ?? 0));
                }
            } catch (\Throwable $e) {
            }
            $jobs[] = [
                'job_id' => (int)$j['id'],
                'factura' => $factura,
                'items' => $facturaRepo->items((int)$j['factura_id']),
                'pagos' => $facturaRepo->pagos((int)$j['factura_id']),
                'empresa' => $empresa,
                'sucursal' => $sucursal,
            ];
        }
        Response::json(['ok' => true, 'punto_venta' => (int)$imp['punto_venta'], 'jobs' => $jobs]);
    }

    /** POST /api/print/ack {token, job_id, estado: impreso|error, mensaje?} */
    public function apiAck(array $params): void
    {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!is_array($input)) {
            $input = $_POST;
        }
        $token = trim((string)($input['token'] ?? $_GET['token'] ?? ''));
        $imp = (new PrintJobRepo())->impresoraPorToken($token);
        if (!$imp) {
            Response::json(['ok' => false, 'error' => 'Token inválido.'], 401);
            return;
        }

        $jobId = (int)($input['job_id'] ?? 0);
        $estado = (string)($input['estado'] ?? '');
        if ($jobId <= 0 || !in_array($estado, ['impreso', 'error'], true)) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }
        (new PrintJobRepo())->marcar($jobId, $estado, trim((string)($input['mensaje'] ?? '')) ?: null);
        Response::json(['ok' => true]);
    }
}
