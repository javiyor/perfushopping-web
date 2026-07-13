<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\EmpleadoRepo;
use Perfushopping\Web\Repo\AdminUserRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class EmpleadoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new EmpleadoRepo();
        $empleados = $repo->listConfig();

        echo View::adminPage('admin/empleados/list.php', [
            'adminUser' => $adminUser,
            'empleados' => $empleados,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Empleados',
        ]);
    }

    public function edit(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new EmpleadoRepo();
        $vendedores = $repo->listVendedoresDisponibles();

        $config = $id > 0 ? $repo->findConfig($id) : null;
        $comisiones = $id > 0 ? $repo->getComisiones($id) : [];
        $marcas = $repo->listMarcas();

        echo View::adminPage('admin/empleados/edit.php', [
            'adminUser' => $adminUser,
            'vendedores' => $vendedores,
            'config' => $config,
            'editId' => $id,
            'comisiones' => $comisiones,
            'marcas' => $marcas,
            'csrf' => Csrf::token(),
            'pageTitle' => $config ? 'Configurar ' . ($config['nombre'] ?? '') : 'Nueva configuración',
        ]);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $adminUserId = (int)($_POST['admin_user_id'] ?? 0);
        if ($adminUserId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná un vendedor.'];
            Response::redirect('/admin/empleados');
        }

        $data = $_POST;
        $data['sueldo_base_cents'] = (int)((float)($data['sueldo_base'] ?? 0) * 100);
        $data['valor_hora_cents'] = (int)((float)($data['valor_hora'] ?? 0) * 100);
        (new EmpleadoRepo())->saveConfig($adminUserId, $data);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Configuración guardada.'];
        Response::redirect('/admin/empleados');
    }

    public function saveComision(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $adminUserId = (int)($_POST['admin_user_id'] ?? 0);
        $codsub = (int)($_POST['codsub'] ?? 0);
        $porcentaje = (float)($_POST['porcentaje'] ?? 0);

        if ($adminUserId <= 0 || $codsub <= 0) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        (new EmpleadoRepo())->saveComision($adminUserId, $codsub, $porcentaje);
        Response::json(['ok' => true]);
    }

    public function deleteComision(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $adminUserId = (int)($_POST['admin_user_id'] ?? 0);
        $codsub = (int)($_POST['codsub'] ?? 0);

        if ($adminUserId <= 0 || $codsub <= 0) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        (new EmpleadoRepo())->deleteComision($adminUserId, $codsub);
        Response::json(['ok' => true]);
    }

    public function horas(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new EmpleadoRepo();
        $vendedores = $repo->listVendedoresDisponibles();

        $vendedorId = (int)($_GET['vendedor_id'] ?? 0);
        $periodo = trim((string)($_GET['periodo'] ?? date('Y-m')));
        $horas = [];
        $totalHoras = 0;
        $config = null;

        if ($vendedorId > 0) {
            $horas = $repo->horasDelMes($vendedorId, $periodo);
            $totalHoras = $repo->totalHorasMes($vendedorId, $periodo);
            $config = $repo->findConfig($vendedorId);
        }

        echo View::adminPage('admin/empleados/horas.php', [
            'adminUser' => $adminUser,
            'vendedores' => $vendedores,
            'vendedorId' => $vendedorId,
            'periodo' => $periodo,
            'horas' => $horas,
            'totalHoras' => $totalHoras,
            'config' => $config,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Horas trabajadas',
        ]);
    }

    public function horasStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $adminUserId = (int)($_POST['admin_user_id'] ?? 0);
        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $horas = (float)($_POST['horas'] ?? 0);
        $concepto = trim((string)($_POST['concepto'] ?? ''));

        if ($adminUserId <= 0 || $fecha === '' || $horas <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos.'];
            Response::redirect('/admin/empleados/horas?vendedor_id=' . $adminUserId);
        }

        (new EmpleadoRepo())->guardarHoras($adminUserId, $fecha, $horas, $concepto, (int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Horas registradas.'];
        Response::redirect('/admin/empleados/horas?vendedor_id=' . $adminUserId . '&periodo=' . substr($fecha, 0, 7));
    }

    public function liquidar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $repo = new EmpleadoRepo();
        $vendedores = $repo->listVendedoresDisponibles();

        $vendedorId = (int)($_GET['vendedor_id'] ?? 0);
        $periodo = trim((string)($_GET['periodo'] ?? date('Y-m')));
        $resultado = null;
        $config = null;
        $facturas = [];
        $comisiones = [];

        if ($vendedorId > 0) {
            $config = $repo->findConfig($vendedorId);
            if ($config) {
                $resultado = $repo->calcularLiquidacion($vendedorId, $periodo);
                $facturas = $repo->facturasVendedorMes($vendedorId, $periodo);
                $comisiones = $repo->getComisiones($vendedorId);
            }
        }

        echo View::adminPage('admin/empleados/liquidacion.php', [
            'adminUser' => $adminUser,
            'vendedores' => $vendedores,
            'vendedorId' => $vendedorId,
            'periodo' => $periodo,
            'resultado' => $resultado,
            'config' => $config,
            'facturas' => $facturas,
            'comisiones' => $comisiones,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Liquidar sueldo',
        ]);
    }

    public function liquidarStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $vendedorId = (int)($_POST['vendedor_id'] ?? 0);
        $periodo = trim((string)($_POST['periodo'] ?? ''));

        if ($vendedorId <= 0 || $periodo === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Datos inválidos.'];
            Response::redirect('/admin/empleados/liquidar');
        }

        try {
            $repo = new EmpleadoRepo();
            $liq = $repo->calcularLiquidacion($vendedorId, $periodo);
            $repo->guardarLiquidacion($liq, (int)$adminUser['id']);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Liquidación calculada y guardada.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }

        Response::redirect('/admin/empleados/liquidar?vendedor_id=' . $vendedorId . '&periodo=' . $periodo);
    }

    public function liquidaciones(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $periodo = trim((string)($_GET['periodo'] ?? ''));
        $list = (new EmpleadoRepo())->listLiquidaciones($periodo);

        echo View::adminPage('admin/empleados/liquidaciones.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'periodo' => $periodo,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Liquidaciones',
        ]);
    }

    public function liquidacionPagada(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/empleados/liquidaciones');

        (new EmpleadoRepo())->marcarPagada($id, (int)$adminUser['id']);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Liquidación marcada como pagada.'];
        Response::redirect('/admin/empleados/liquidaciones');
    }

    public function liquidacionAnular(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/empleados/liquidaciones');

        (new EmpleadoRepo())->anularLiquidacion($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Liquidación anulada.'];
        Response::redirect('/admin/empleados/liquidaciones');
    }
}
