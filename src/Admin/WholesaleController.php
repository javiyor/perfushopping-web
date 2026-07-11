<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Repo\WholesaleRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class WholesaleController
{
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $list = (new WholesaleRepo())->pendingList();
        echo View::adminPage('admin/wholesale_list.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'customerCategories' => UserRepo::customerCategoryOptions(),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function approve(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $wr = (new WholesaleRepo())->find($id);
        if (!$wr) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solicitud no encontrada.'];
            Response::redirect('/admin/wholesale');
        }

        $clienteId = $this->upsertCliente((string)$wr['cuit'], (string)$wr['email'], (string)$wr['razon_social'], (string)$wr['address'], (string)$wr['phone'], (string)$wr['postal_code']);

        (new WholesaleRepo())->decide($id, (int)$adminUser['id'], 'approved', trim((string)($_POST['notes'] ?? '')) ?: null);
        (new UserRepo())->setWholesaleStatus((int)$wr['user_id'], 'approved', $clienteId);
        (new UserRepo())->setCustomerCategory((int)$wr['user_id'], (string)($wr['customer_category'] ?? $wr['user_customer_category'] ?? 'none'));
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Mayorista aprobado.'];
        Response::redirect('/admin/wholesale');
    }

    public function reject(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $wr = (new WholesaleRepo())->find($id);
        if (!$wr) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Solicitud no encontrada.'];
            Response::redirect('/admin/wholesale');
        }
        (new WholesaleRepo())->decide($id, (int)$adminUser['id'], 'rejected', trim((string)($_POST['notes'] ?? '')) ?: null);
        (new UserRepo())->setWholesaleStatus((int)$wr['user_id'], 'rejected', (int)($wr['cliente_id'] ?? 0) ?: null);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Solicitud rechazada.'];
        Response::redirect('/admin/wholesale');
    }

    private function upsertCliente(string $cuit, string $mail, string $razon, string $direc, string $tele, string $codpost): ?int
    {
        $pdo = Db::pdo();
        $cuitNum = preg_replace('/[^0-9]/', '', $cuit) ?? '';
        $mail = trim($mail);

        if ($cuitNum !== '') {
            $st = $pdo->prepare('SELECT idclien FROM clientes WHERE cuit=:c LIMIT 1');
            $st->execute([':c' => $cuitNum]);
            $id = $st->fetchColumn();
            if ($id !== false) {
                $upd = $pdo->prepare('UPDATE clientes SET razon=:r, direc=:d, tele=:t, codpost=:cp, mail=:m, activo=1 WHERE idclien=:i');
                $upd->execute([':r' => $razon, ':d' => $direc, ':t' => $tele, ':cp' => $codpost, ':m' => $mail, ':i' => (int)$id]);
                return (int)$id;
            }
        }

        if ($mail !== '') {
            $st = $pdo->prepare('SELECT idclien FROM clientes WHERE mail=:m LIMIT 1');
            $st->execute([':m' => $mail]);
            $id = $st->fetchColumn();
            if ($id !== false) {
                $upd = $pdo->prepare('UPDATE clientes SET razon=:r, direc=:d, tele=:t, codpost=:cp, activo=1 WHERE idclien=:i');
                $upd->execute([':r' => $razon, ':d' => $direc, ':t' => $tele, ':cp' => $codpost, ':i' => (int)$id]);
                return (int)$id;
            }
        }

        $ins = $pdo->prepare('INSERT INTO clientes (razon,cuit,direc,tele,codpost,mail,activo,fealta) VALUES (:r,:c,:d,:t,:cp,:m,1,CURDATE())');
        $ins->execute([
            ':r' => $razon,
            ':c' => $cuitNum !== '' ? $cuitNum : 0,
            ':d' => $direc,
            ':t' => $tele,
            ':cp' => $codpost,
            ':m' => $mail,
        ]);
        return (int)$pdo->lastInsertId();
    }
}
