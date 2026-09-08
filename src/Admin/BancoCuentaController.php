<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\BancoCuentaRepo;
use Perfushopping\Web\Repo\CobroCuentaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class BancoCuentaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        $repo = new BancoCuentaRepo();
        $cobroRepo = new CobroCuentaRepo();
        $list = $repo->findAll(false);
        $transferCuentaId = $cobroRepo->getTransferenciaCuentaId();
        $cobros = $cobroRepo->all();
        // tarjetas para mapeo
        $tarjetas = [];
        try {
            $tarjetas = \Perfushopping\Web\Infra\Db::pdo()->query('SELECT idtarje, nomtar FROM tarjeta ORDER BY nomtar ASC')->fetchAll();
        } catch (\Throwable $e) {}
        echo View::adminPage('admin/banco_cuentas/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'transferCuentaId' => $transferCuentaId,
            'cobros' => $cobros,
            'tarjetas' => $tarjetas,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Cuentas propias',
        ]);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'banco' => trim((string)($_POST['banco'] ?? '')),
            'tipo_cuenta' => trim((string)($_POST['tipo_cuenta'] ?? 'corriente')),
            'numero_cuenta' => trim((string)($_POST['numero_cuenta'] ?? '')),
            'cbu' => trim((string)($_POST['cbu'] ?? '')),
            'titular' => trim((string)($_POST['titular'] ?? '')),
            'saldo_inicial_cents' => (int)round((float)str_replace(',', '.', (string)($_POST['saldo_inicial'] ?? '0')) * 100),
            'activo' => isset($_POST['activo']) ? 1 : 0,
        ];
        if ($data['banco'] === '') {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'El banco es obligatorio.'];
            Response::redirect('/admin/banco-cuentas');
        }
        $repo = new BancoCuentaRepo();
        if ($id > 0) $repo->update($id, $data);
        else $repo->create($data);
        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>$id>0?'Cuenta actualizada.':'Cuenta creada.'];
        Response::redirect('/admin/banco-cuentas');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        if ($id>0) (new BancoCuentaRepo())->delete($id);
        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Cuenta eliminada.'];
        Response::redirect('/admin/banco-cuentas');
    }

    public function saveCobro(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);
        $tipo = trim((string)($_POST['tipo'] ?? ''));
        $repo = new CobroCuentaRepo();
        if ($tipo === 'transferencia') {
            $bancoCuentaId = (int)($_POST['banco_cuenta_id'] ?? 0);
            if ($bancoCuentaId>0) {
                $repo->setTransferenciaCuenta($bancoCuentaId);
                $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Cuenta para transferencias guardada.'];
            }
        } elseif ($tipo === 'tarjeta') {
            $idtarje = (int)($_POST['idtarje'] ?? 0);
            $bancoCuentaId = (int)($_POST['banco_cuenta_id'] ?? 0);
            if ($idtarje>0 && $bancoCuentaId>0) {
                $repo->setTarjetaCuenta($idtarje, $bancoCuentaId);
                $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Cuenta para tarjeta guardada.'];
            } else {
                $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'Seleccioná tarjeta y cuenta.'];
            }
        }
        Response::redirect('/admin/banco-cuentas');
    }

    public function deleteCobroTarjeta(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);
        $idtarje = (int)($_POST['idtarje'] ?? 0);
        if ($idtarje>0) (new CobroCuentaRepo())->deleteTarjetaCuenta($idtarje);
        $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Mapeo eliminado.'];
        Response::redirect('/admin/banco-cuentas');
    }
}
