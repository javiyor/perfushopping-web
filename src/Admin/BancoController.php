<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\BancoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class BancoController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        $list = (new BancoRepo())->findAll();
        echo View::adminPage('admin/bancos/list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Bancos',
        ]);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);
        $idban = (int)($_POST['idban'] ?? 0);
        $nombanc = trim((string)($_POST['nombanc'] ?? ''));
        $numbancRaw = trim((string)($_POST['numbanc'] ?? ''));
        $numbanc = $numbancRaw === '' ? null : (int)$numbancRaw;
        if ($nombanc === '') {
            $_SESSION['admin_flash'] = ['type'=>'danger','text'=>'El nombre del banco es obligatorio.'];
            Response::redirect('/admin/bancos');
        }
        $repo = new BancoRepo();
        if ($idban > 0) {
            $repo->update($idban, $nombanc, $numbanc);
            $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Banco actualizado.'];
        } else {
            $repo->create($nombanc, $numbanc);
            $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Banco creado.'];
        }
        Response::redirect('/admin/bancos');
    }

    public function delete(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('cheques');
        Csrf::check($_POST['_csrf'] ?? null);
        $idban = (int)($_POST['idban'] ?? 0);
        if ($idban > 0) {
            (new BancoRepo())->delete($idban);
            $_SESSION['admin_flash'] = ['type'=>'ok','text'=>'Banco eliminado.'];
        }
        Response::redirect('/admin/bancos');
    }
}
