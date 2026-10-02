<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\BancoRepo;
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
        $adminUser = $auth->requirePermiso('cheques');

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
        $adminUser = $auth->requirePermiso('cheques');

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
        $adminUser = $auth->requirePermiso('cheques');

        $bancos = (new BancoCuentaRepo())->findAll();
        $bancosLista = (new BancoRepo())->findAll();
        $tipo = trim((string)($_GET['tipo'] ?? 'propio'));
        if (!in_array($tipo, ['propio', 'tercero'], true)) {
            $tipo = 'propio';
        }

        echo View::adminPage('admin/cheques/emitir.php', [
            'adminUser' => $adminUser,
            'bancos' => $bancos,
            'bancosLista' => $bancosLista,
            'tipo' => $tipo,
            'csrf' => Csrf::token(),
            'pageTitle' => $tipo === 'tercero' ? 'Cargar cheque de tercero' : 'Emitir cheque propio',
        ]);
    }

    public function emitirStore(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);

        $tipo = trim((string)($_POST['tipo'] ?? 'propio'));
        if (!in_array($tipo, ['propio', 'tercero'], true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Tipo de cheque inválido.'];
            Response::redirect('/admin/cheques/emitir');
        }

        $montoCents = (int)($_POST['monto_cents'] ?? 0);
        if ($montoCents <= 0 && isset($_POST['monto']) && trim((string)$_POST['monto']) !== '') {
            $montoCents = (int)round(((float)str_replace(',', '.', (string)$_POST['monto'])) * 100);
        }
        if ($montoCents <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El monto debe ser mayor a cero.'];
            Response::redirect('/admin/cheques/emitir?tipo=' . $tipo);
        }

        $repo = new ChequeRepo();
        if ($tipo === 'propio') {
            $bancoCuentaId = (int)($_POST['banco_cuenta_id'] ?? 0);
            if ($bancoCuentaId <= 0) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Seleccioná una cuenta bancaria.'];
                Response::redirect('/admin/cheques/emitir?tipo=propio');
            }
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
        } else {
            $bancoId = (int)($_POST['banco_id'] ?? 0);
            $bancoEmisor = '';
            if ($bancoId > 0) {
                $bancoRow = (new BancoRepo())->findById($bancoId);
                $bancoEmisor = trim((string)($bancoRow['nombanc'] ?? ''));
            }
            $chequeId = $repo->create([
                'tipo' => 'tercero',
                'estado' => 'en_cartera',
                'numero_cheque' => trim((string)($_POST['numero_cheque'] ?? '')),
                'banco_emisor' => $bancoEmisor !== '' ? $bancoEmisor : null,
                'banco_cuenta_id' => (int)($_POST['banco_cuenta_id'] ?? 0) ?: null,
                'monto_cents' => $montoCents,
                'fecha_vencimiento' => trim((string)($_POST['fecha_vencimiento'] ?? '')) ?: null,
                'quien_entrego' => trim((string)($_POST['quien_entrego'] ?? '')),
                'fecha_emision' => (string)($_POST['fecha_emision'] ?? date('Y-m-d')),
                'concepto' => trim((string)($_POST['concepto'] ?? '')),
            ], (int)$adminUser['id']);
            $repo->agregarMovimiento($chequeId, 'en_cartera', null, null, 'Ingreso a cartera de cheques', (int)$adminUser['id']);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Cheque de tercero agregado a cartera.'];
        }
        Response::redirect('/admin/cheques/' . $chequeId);
    }

    public function estado(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $estado = trim((string)($_POST['estado'] ?? ''));
        $repo = new ChequeRepo();
        $cheque = $repo->findById($id);
        if (!$cheque) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cheque no encontrado.'];
            Response::redirect('/admin/cheques');
        }

        $prevEstado = $cheque['estado'] ?? '';
        $repo->updateEstado($id, $estado);

        // Generar movimiento bancario automático para acreditación/débito
        try {
            $bancoCuentaId = (int)($cheque['banco_cuenta_id'] ?? 0);
            $monto = (int)($cheque['monto_cents'] ?? 0);
            $bm = new \Perfushopping\Web\Repo\BancoMovimientoRepo();
            if ($bancoCuentaId && $monto > 0) {
                if ($cheque['tipo'] === 'tercero' && in_array($estado, ['acreditado','cobrado','depositado'], true) && $prevEstado !== $estado) {
                    $bm->create($bancoCuentaId, 'credito', 'cheque', $id, 'Acreditación cheque #' . ($cheque['numero_cheque'] ?? $id), $monto, date('Y-m-d'), (int)$adminUser['id']);
                }
                if ($cheque['tipo'] === 'propio' && in_array($estado, ['debitado','cobrado','compensado'], true) && $prevEstado !== $estado) {
                    $bm->create($bancoCuentaId, 'debito', 'cheque', $id, 'Débito cheque #' . ($cheque['numero_cheque'] ?? $id), $monto, date('Y-m-d'), (int)$adminUser['id']);
                }
            }
        } catch (\Throwable $e) { error_log('BancoMov cheque: '.$e->getMessage()); }

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
