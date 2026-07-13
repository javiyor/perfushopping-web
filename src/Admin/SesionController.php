<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\CajaRepo;
use Perfushopping\Web\Repo\SucursalRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class SesionController
{
    public function iniciar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireLogin();

        // If already have active session, redirect to dashboard
        if ($auth->hasSesion()) {
            Response::redirect('/admin');
        }

        $sucursales = (new SucursalRepo())->listarActivas();
        $vendedores = (new SucursalRepo())->vendedoresDisponibles();

        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $isMobile = preg_match('/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|IEMobile|Opera Mini/i', $ua) === 1;

        $lastVendedores = [];
        if (isset($_SESSION['last_vendedores']) && is_array($_SESSION['last_vendedores'])) {
            $lastVendedores = $_SESSION['last_vendedores'];
        }

        echo View::adminPage('admin/sesion/iniciar.php', [
            'adminUser' => $adminUser,
            'sucursales' => $sucursales,
            'vendedores' => $vendedores,
            'isMobile' => $isMobile,
            'lastVendedores' => $lastVendedores,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Iniciar turno',
        ]);
    }

    public function guardar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);

        $sucursalId = (int)($_POST['sucursal_id'] ?? 0);
        $turno = (string)($_POST['turno'] ?? '');
        $vendedores = array_map('intval', (array)($_POST['vendedores'] ?? []));

        if ($sucursalId <= 0 || !in_array($turno, ['manana', 'tarde'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná sucursal y turno.'];
            Response::redirect('/admin/sesion/iniciar');
        }

        $sucursal = (new SucursalRepo())->findById($sucursalId);
        if (!$sucursal) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Sucursal inválida.'];
            Response::redirect('/admin/sesion/iniciar');
        }

        $auth->iniciarSesion($sucursalId, $turno, $vendedores);

        $_SESSION['last_vendedores'] = $vendedores;

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Turno iniciado — ' . htmlspecialchars($sucursal['nomsuc'] ?? '')];
        Response::redirect('/admin');
    }

    public function cerrar(array $params): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        $auth = new AdminAuthService();
        $sucursalId = $auth->getSucursalId();
        $turno = $auth->getTurno();

        // Check if there's an open caja
        if ($sucursalId > 0 && $turno) {
            $repo = new CajaRepo();
            $apertura = $repo->aperturaActiva($sucursalId, $turno, date('Y-m-d'));
            if ($apertura) {
                $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'Cerá la caja antes de cerrar el turno.'];
                Response::redirect('/admin/caja/cierre');
            }
        }

        $auth->cerrarSesion();
        $_SESSION['admin_flash'] = ['type' => 'info', 'text' => 'Turno cerrado.'];
        Response::redirect('/admin/sesion/iniciar');
    }
}
