<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\CompraRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CuentaContableController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('compras', 'caja_movimientos');

        $repo = new CompraRepo();
        $grupos = $repo->cuentasGrupo();
        $subs = $repo->cuentas();
        $porGrupo = [];
        foreach ($subs as $s) {
            $porGrupo[(int)$s['idcta']][] = $s;
        }

        echo View::adminPage('admin/cuentas-contables/list.php', [
            'adminUser' => $adminUser,
            'grupos' => $grupos,
            'porGrupo' => $porGrupo,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cuentas contables',
        ]);
    }

    public function saveCuenta(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('compras', 'caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idcta = (int)($_POST['idcta'] ?? 0);
        $nomcta = trim((string)($_POST['nomcta'] ?? ''));
        if ($nomcta === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre de la cuenta es obligatorio.'];
            Response::redirect('/admin/cuentas-contables');
        }

        $repo = new CompraRepo();
        if ($idcta > 0) {
            $repo->actualizarCuenta($idcta, $nomcta);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Cuenta actualizada.'];
        } else {
            $repo->crearCuenta($nomcta);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Cuenta creada.'];
        }
        Response::redirect('/admin/cuentas-contables');
    }

    public function saveSubcuenta(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('compras', 'caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idcta1 = (int)($_POST['idcta1'] ?? 0);
        $idcta = (int)($_POST['idcta'] ?? 0);
        $nomcta1 = trim((string)($_POST['nomcta1'] ?? ''));
        if ($nomcta1 === '' || $idcta <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá el nombre y la cuenta principal.'];
            Response::redirect('/admin/cuentas-contables');
        }

        $repo = new CompraRepo();
        if ($idcta1 > 0) {
            $repo->actualizarSubcuenta($idcta1, $nomcta1, $idcta);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Subcuenta actualizada.'];
        } else {
            $repo->crearSubcuenta($nomcta1, $idcta);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Subcuenta creada.'];
        }
        Response::redirect('/admin/cuentas-contables');
    }

    public function deleteCuenta(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('compras', 'caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idcta = (int)($_POST['idcta'] ?? 0);
        if ($idcta > 0) {
            if ((new CompraRepo())->eliminarCuenta($idcta)) {
                $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Cuenta eliminada.'];
            } else {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se puede eliminar: la cuenta tiene subcuentas.'];
            }
        }
        Response::redirect('/admin/cuentas-contables');
    }

    public function deleteSubcuenta(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('compras', 'caja_movimientos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idcta1 = (int)($_POST['idcta1'] ?? 0);
        if ($idcta1 > 0) {
            if ((new CompraRepo())->eliminarSubcuenta($idcta1)) {
                $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Subcuenta eliminada.'];
            } else {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se puede eliminar: la subcuenta está usada en comprobantes o gastos.'];
            }
        }
        Response::redirect('/admin/cuentas-contables');
    }
}
