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
        $adminUser = $auth->requirePermiso('usuarios_admin');

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
        $adminUser = $auth->requirePermiso('usuarios_admin');

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
        $auth->requirePermiso('usuarios_admin');
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
        $auth->requirePermiso('usuarios_admin');
        Csrf::check($_POST['_csrf'] ?? null);

        $adminUserId = (int)($_POST['admin_user_id'] ?? 0);
        $codsub = (int)($_POST['codsub'] ?? 0);
        $porcentaje = (float)($_POST['porcentaje'] ?? 0);

        if ($adminUserId <= 0 || $codsub <= 0) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        $repo = new EmpleadoRepo();
        $marcasIds = array_map('intval', array_column($repo->listMarcas(), 'codsub'));
        if ($porcentaje <= 0 || $porcentaje > 100 || !in_array($codsub, $marcasIds, true)) {
            Response::json(['ok' => false, 'error' => 'Elegí una marca válida e ingresá una comisión entre 0 y 100%.']);
            return;
        }
        $repo->saveComision($adminUserId, $codsub, $porcentaje);
        Response::json(['ok' => true]);
    }

    public function deleteComision(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('usuarios_admin');
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

    public function compartirComisiones(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('usuarios_admin');
        Csrf::check($_POST['_csrf'] ?? null);

        $adminUserId = (int)($_POST['admin_user_id'] ?? 0);
        if ($adminUserId <= 0) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        $repo = new EmpleadoRepo();
        if (!$repo->getComisiones($adminUserId)) {
            Response::json(['ok' => false, 'error' => 'Este empleado no tiene comisiones para compartir.']);
            return;
        }

        $empleados = $repo->compartirComisiones($adminUserId);
        Response::json(['ok' => true, 'empleados' => $empleados]);
    }

    public function comisionesInforme(array $params): void
    {
        $auth = new AdminAuthService();
        $user = $auth->requireLogin();

        $esAdmin = ($user['rol'] ?? '') === 'superadmin' || $auth->checkPermiso('usuarios_admin');

        $periodo = trim((string)($_GET['periodo'] ?? ''));
        if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)) {
            $periodo = (new \DateTimeImmutable('first day of last month'))->format('Y-m');
        }

        $uidSel = (int)($_GET['uid'] ?? 0);
        if (!$esAdmin) {
            $uidSel = (int)($user['id'] ?? 0);
        }

        $repo = new EmpleadoRepo();
        $informe = $repo->comisionesGanadas($periodo, $uidSel > 0 ? $uidSel : null);
        $empleadosSel = $esAdmin ? $repo->listConfig() : [];

        echo View::adminPage('admin/empleados/comisiones.php', [
            'adminUser' => $user,
            'informe' => $informe,
            'empleadosSel' => $empleadosSel,
            'periodo' => $periodo,
            'uidSel' => $uidSel,
            'esAdmin' => $esAdmin,
            'pageTitle' => $esAdmin ? 'Comisiones ganadas' : 'Mis comisiones',
        ]);
    }

    public function horas(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('usuarios_admin');

        $repo = new EmpleadoRepo();
        $vendedores = $repo->listVendedoresDisponibles();

        $vendedorId = (int)($_GET['vendedor_id'] ?? 0);
        $periodo = trim((string)($_GET['periodo'] ?? date('Y-m')));
        $horas = [];
        $turnosPresentes = [];
        $horasManuales = 0.0;
        $horasTurnos = 0.0;
        $turnosIncluidos = 0;
        $totalHoras = 0;
        $config = null;

        if ($vendedorId > 0) {
            $horas = $repo->horasDelMes($vendedorId, $periodo);
            $turnosPresentes = $repo->turnosPresentesMes($vendedorId, $periodo);
            $resumenHoras = $repo->resumenHorasMes($vendedorId, $periodo);
            $horasManuales = $resumenHoras['horas_manuales'];
            $horasTurnos = $resumenHoras['horas_turnos'];
            $turnosIncluidos = $resumenHoras['turnos_incluidos'];
            $totalHoras = $resumenHoras['total_horas'];
            $config = $repo->findConfig($vendedorId);
        }

        echo View::adminPage('admin/empleados/horas.php', [
            'adminUser' => $adminUser,
            'vendedores' => $vendedores,
            'vendedorId' => $vendedorId,
            'periodo' => $periodo,
            'horas' => $horas,
            'turnosPresentes' => $turnosPresentes,
            'horasManuales' => $horasManuales,
            'horasTurnos' => $horasTurnos,
            'turnosIncluidos' => $turnosIncluidos,
            'totalHoras' => $totalHoras,
            'config' => $config,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Horas trabajadas',
        ]);
    }

    public function horasStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('usuarios_admin');
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

    public function horasUpdate(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('usuarios_admin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $adminUserId = (int)($_POST['admin_user_id'] ?? 0);
        $fecha = trim((string)($_POST['fecha'] ?? ''));
        $horas = (float)($_POST['horas'] ?? 0);
        $concepto = trim((string)($_POST['concepto'] ?? ''));
        $periodoActual = trim((string)($_POST['periodo_actual'] ?? ''));
        $back = '/admin/empleados/horas?vendedor_id=' . $adminUserId
            . ($periodoActual !== '' ? '&periodo=' . urlencode($periodoActual) : '');

        $fechaValida = preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1
            && strtotime($fecha) !== false
            && date('Y-m-d', strtotime($fecha)) === $fecha;
        if ($id <= 0 || $adminUserId <= 0 || !$fechaValida || $horas < 0.5 || $horas > 24) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Revisá fecha y horas; el rango permitido es de 0,5 a 24 horas.'];
            Response::redirect($back);
        }

        $actualizado = (new EmpleadoRepo())->actualizarHoras($id, $adminUserId, $fecha, $horas, $concepto);
        if (!$actualizado) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se encontró ese registro de horas para el empleado.'];
            Response::redirect($back);
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Registro de horas actualizado.'];
        Response::redirect('/admin/empleados/horas?vendedor_id=' . $adminUserId . '&periodo=' . substr($fecha, 0, 7));
    }

    public function liquidar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('usuarios_admin');

        $repo = new EmpleadoRepo();
        $vendedores = $repo->listVendedoresDisponibles();

        $vendedorId = (int)($_GET['vendedor_id'] ?? 0);
        $periodoAnterior = (new \DateTimeImmutable('first day of last month'))->format('Y-m');
        $periodo = trim((string)($_GET['periodo'] ?? $periodoAnterior));
        $resultado = null;
        $config = null;
        $gastosDesde = trim((string)($_GET['gastos_desde'] ?? ''));
        $gastosHasta = trim((string)($_GET['gastos_hasta'] ?? date('Y-m-d')));

        if ($vendedorId > 0) {
            $config = $repo->findConfig($vendedorId);
            if ($config) {
                if ($gastosDesde === '' || !$this->fechaValida($gastosDesde)) {
                    $gastosDesde = $repo->fechaInicioAdelantos($vendedorId);
                }
                if (!$this->fechaValida($gastosHasta)) {
                    $gastosHasta = date('Y-m-d');
                }
                if ($gastosDesde > $gastosHasta) $gastosDesde = $gastosHasta;
                $adelantos = $repo->adelantosDisponibles($vendedorId, $gastosDesde, $gastosHasta);
                $adelantoIds = array_map(static fn(array $a): int => (int)$a['id'], $adelantos);
                $resultado = $repo->calcularLiquidacion($vendedorId, $periodo, $adelantoIds, $gastosDesde, $gastosHasta);
            } else {
                $adelantos = [];
            }
        } else {
            $adelantos = [];
        }

        echo View::adminPage('admin/empleados/liquidacion.php', [
            'adminUser' => $adminUser,
            'vendedores' => $vendedores,
            'vendedorId' => $vendedorId,
            'periodo' => $periodo,
            'resultado' => $resultado,
            'config' => $config,
            'adelantos' => $adelantos,
            'gastosDesde' => $gastosDesde,
            'gastosHasta' => $gastosHasta,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Liquidar sueldo',
        ]);
    }

    public function liquidarStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('usuarios_admin');
        Csrf::check($_POST['_csrf'] ?? null);

        $vendedorId = (int)($_POST['vendedor_id'] ?? 0);
        $periodo = trim((string)($_POST['periodo'] ?? ''));
        $gastosDesde = trim((string)($_POST['gastos_desde'] ?? ''));
        $gastosHasta = trim((string)($_POST['gastos_hasta'] ?? ''));
        $adelantoIds = is_array($_POST['adelanto_ids'] ?? null) ? array_map('intval', $_POST['adelanto_ids']) : [];

        if ($vendedorId <= 0 || !preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $periodo)
            || !$this->fechaValida($gastosDesde) || !$this->fechaValida($gastosHasta) || $gastosDesde > $gastosHasta) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Datos inválidos.'];
            Response::redirect('/admin/empleados/liquidar');
        }

        try {
            $repo = new EmpleadoRepo();
            $adelantosDisponibles = $repo->adelantosDisponibles($vendedorId, $gastosDesde, $gastosHasta);
            $disponiblesIds = array_map(static fn(array $a): int => (int)$a['id'], $adelantosDisponibles);
            $seleccionadosIds = array_values(array_unique(array_filter($adelantoIds, static fn(int $id): bool => $id > 0)));
            if (array_diff($seleccionadosIds, $disponiblesIds)) {
                throw new \RuntimeException('Uno o más gastos seleccionados ya no están disponibles. Volvé a revisar la selección.');
            }
            $liq = $repo->calcularLiquidacion($vendedorId, $periodo, $seleccionadosIds, $gastosDesde, $gastosHasta);
            $repo->guardarLiquidacion($liq, (int)$adminUser['id']);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Liquidación calculada y guardada.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }

        Response::redirect('/admin/empleados/liquidar?vendedor_id=' . $vendedorId . '&periodo=' . $periodo
            . '&gastos_desde=' . urlencode($gastosDesde) . '&gastos_hasta=' . urlencode($gastosHasta));
    }

    public function liquidaciones(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('usuarios_admin');

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

    public function imprimirLiquidacion(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('usuarios_admin');
        $id = (int)($params['id'] ?? 0);
        $repo = new EmpleadoRepo();
        $liquidacion = $id > 0 ? $repo->findLiquidacion($id) : null;
        if (!$liquidacion) {
            http_response_code(404);
            echo 'Liquidación no encontrada.';
            return;
        }
        $detalle = json_decode((string)($liquidacion['detalle'] ?? ''), true);
        if (!is_array($detalle)) $detalle = [];
        echo View::render('admin/empleados/recibo.php', [
            'liquidacion' => $liquidacion,
            'detalle' => $detalle,
            'adelantos' => $repo->adelantosDeLiquidacion($id),
            'empresa' => (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault(),
            'fechaImpresion' => date('d/m/Y'),
        ]);
    }

    public function liquidacionPagada(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('usuarios_admin');
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
        $adminUser = $auth->requirePermiso('usuarios_admin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) Response::redirect('/admin/empleados/liquidaciones');

        (new EmpleadoRepo())->anularLiquidacion($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Liquidación anulada.'];
        Response::redirect('/admin/empleados/liquidaciones');
    }

    public function liquidacionEliminar(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('usuarios_admin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) {
            $_SESSION['admin_flash'] = ['type'=>'danger', 'text'=>'Liquidación inválida.'];
            Response::redirect('/admin/empleados/liquidaciones');
        }

        $eliminada = (new EmpleadoRepo())->eliminarLiquidacionAnulada($id);
        $_SESSION['admin_flash'] = $eliminada
            ? ['type'=>'ok', 'text'=>'Liquidación anulada eliminada.']
            : ['type'=>'danger', 'text'=>'Solo se pueden eliminar liquidaciones anuladas.'];
        Response::redirect('/admin/empleados/liquidaciones');
    }

    private function fechaValida(string $fecha): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) return false;
        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $fecha);
        return $date !== false && $date->format('Y-m-d') === $fecha;
    }
}
