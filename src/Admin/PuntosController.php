<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\PuntosRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class PuntosController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');

        $q = trim((string)($_GET['q'] ?? ''));
        $repo = new PuntosRepo();
        $cuentas = $repo->listarCuentas($q);

        echo View::adminPage('admin/puntos/index.php', [
            'adminUser' => $adminUser,
            'cuentas' => $cuentas,
            'q' => $q,
            'pctGeneral' => $repo->pctGeneral(),
            'marcas' => $repo->listarMarcas(),
            'productos' => $repo->listarProductos(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Puntos',
        ]);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');

        $idclien = (int)($params['id'] ?? 0);
        if ($idclien <= 0) {
            Response::redirect('/admin/puntos');
        }

        $repo = new PuntosRepo();
        $st = Db::pdo()->prepare('SELECT idclien, razon, cuit, tele AS phone, mail AS email, Localidad AS city FROM clientes WHERE idclien = :c LIMIT 1');
        $st->execute([':c' => $idclien]);
        $cliente = $st->fetch();
        if (!$cliente) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cliente no encontrado.'];
            Response::redirect('/admin/puntos');
        }

        $cuenta = $repo->getCuenta($idclien);
        $movimientos = $repo->movimientos($idclien);

        echo View::adminPage('admin/puntos/show.php', [
            'adminUser' => $adminUser,
            'cliente' => $cliente,
            'cuenta' => $cuenta,
            'movimientos' => $movimientos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Puntos — ' . ($cliente['razon'] ?? ''),
        ]);
    }

    public function ajustar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);

        $idclien = (int)($_POST['idclien'] ?? 0);
        $tipo = (string)($_POST['tipo'] ?? '');
        $puntos = (int)($_POST['puntos'] ?? 0);
        $concepto = trim((string)($_POST['concepto'] ?? ''));

        if ($idclien <= 0 || !in_array($tipo, ['sumar', 'quitar'], true) || $puntos <= 0 || $concepto === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completá todos los campos.'];
            Response::redirect('/admin/puntos/' . $idclien);
        }

        $repo = new PuntosRepo();
        $signo = $tipo === 'quitar' ? -1 : 1;
        $repo->registrar('ajuste', $idclien, $signo * $puntos, null, null, $concepto, (int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Ajuste registrado.'];
        Response::redirect('/admin/puntos/' . $idclien);
    }

    public function saveConfig(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);

        $pct = max(0.0, (float)str_replace(',', '.', (string)($_POST['general_pct'] ?? '1')));
        $pct = min($pct, 100.0);
        (new PuntosRepo())->setConfig('general_pct', (string)$pct);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Porcentaje general actualizado a ' . $pct . '%.'];
        Response::redirect('/admin/puntos');
    }

    public function saveMarca(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);

        $codsub = (int)($_POST['codsub'] ?? 0);
        $pct = max(0.0, (float)str_replace(',', '.', (string)($_POST['porcentaje'] ?? '0')));
        $eliminar = (string)($_POST['eliminar'] ?? '');

        $repo = new PuntosRepo();
        if ($eliminar !== '') {
            $repo->deletePctMarca($codsub);
        } else {
            $repo->setPctMarca($codsub, $pct);
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Bonus por marca actualizado.'];
        Response::redirect('/admin/puntos');
    }

    public function saveProducto(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $pct = max(0.0, (float)str_replace(',', '.', (string)($_POST['porcentaje'] ?? '0')));
        $eliminar = (string)($_POST['eliminar'] ?? '');

        $repo = new PuntosRepo();
        if ($eliminar !== '') {
            $repo->deletePctProducto($idprodu);
        } else {
            $repo->setPctProducto($idprodu, $pct);
        }

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Bonus por producto actualizado.'];
        Response::redirect('/admin/puntos');
    }

    public function saldo(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('clientes');

        $idclien = (int)($_GET['idclien'] ?? 0);
        Response::json(['saldo' => (new PuntosRepo())->saldo($idclien)]);
    }
}
