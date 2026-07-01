<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\ChequeRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ChequeController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $tipo = trim((string)($_GET['tipo'] ?? ''));
        $estado = trim((string)($_GET['estado'] ?? ''));
        $q = trim((string)($_GET['q'] ?? ''));
        $list = (new ChequeRepo())->search($tipo, $estado, $q);

        echo View::adminPage('admin/cheques/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'tipo' => $tipo,
            'estado' => $estado,
            'q' => $q,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cheques',
        ]);
    }

    public function show(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $id = (int)($params['id'] ?? 0);
        $repo = new ChequeRepo();
        $cheque = $repo->findById($id);
        if (!$cheque) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cheque no encontrado.'];
            Response::redirect('/admin/cheques');
        }
        $movimientos = $repo->movimientos($id);

        echo View::adminPage('admin/cheques/detail.php', [
            'adminUser' => $adminUser,
            'cheque' => $cheque,
            'movimientos' => $movimientos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cheque #' . $id,
        ]);
    }

    public function emitirForm(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $bancos = (new \Perfushopping\Web\Repo\BancoCuentaRepo())->findAll();

        echo View::adminPage('admin/cheques/emitir.php', [
            'adminUser' => $adminUser,
            'bancos' => $bancos,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Emitir cheque propio',
        ]);
    }

    public function emitirStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $montoCents = (int)($_POST['monto_cents'] ?? 0);
        if ($montoCents <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El monto debe ser mayor a cero.'];
            Response::redirect('/admin/cheques/emitir');
        }

        $bancoCuentaId = (int)($_POST['banco_cuenta_id'] ?? 0);
        if ($bancoCuentaId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná una cuenta bancaria.'];
            Response::redirect('/admin/cheques/emitir');
        }

        $repo = new ChequeRepo();
        $chequeId = $repo->create([
            'tipo' => 'propio',
            'estado' => 'emitido',
            'banco_emisor' => trim((string)($_POST['banco_emisor'] ?? '')),
            'numero_cheque' => trim((string)($_POST['numero_cheque'] ?? '')),
            'titular' => trim((string)($_POST['titular'] ?? '')),
            'cuit_titular' => trim((string)($_POST['cuit_titular'] ?? '')),
            'monto_cents' => $montoCents,
            'fecha_emision' => (string)($_POST['fecha_emision'] ?? date('Y-m-d')),
            'fecha_vencimiento' => trim((string)($_POST['fecha_vencimiento'] ?? '')) ?: null,
            'banco_cuenta_id' => $bancoCuentaId,
            'concepto' => trim((string)($_POST['concepto'] ?? '')),
        ], (int)$adminUser['id']);

        $repo->agregarMovimiento($chequeId, 'emitido', null, null, 'Emisión directa', (int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Cheque propio emitido correctamente.'];
        Response::redirect('/admin/cheques/' . $chequeId);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $estado = trim((string)($_POST['estado'] ?? ''));
        $repo = new ChequeRepo();
        $cheque = $repo->findById($id);
        if (!$cheque) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cheque no encontrado.'];
            Response::redirect('/admin/cheques');
        }

        $repo->updateEstado($id, $estado);

        $observaciones = trim((string)($_POST['observaciones'] ?? ''));
        if ($observaciones === '') {
            $map = ['depositado' => 'Depositado', 'cobrado' => 'Cobrado', 'rechazado' => 'Rechazado', 'entregado' => 'Entregado', 'anulado' => 'Anulado'];
            $observaciones = $map[$estado] ?? $estado;
        }
        $repo->agregarMovimiento($id, $estado, null, null, $observaciones, (int)$adminUser['id']);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado del cheque actualizado a: ' . $estado];
        Response::redirect('/admin/cheques/' . $id);
    }
}
