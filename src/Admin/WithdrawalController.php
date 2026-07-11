<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\AffiliateWithdrawalRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class WithdrawalController
{
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $list = [];
        try {
            $list = (new AffiliateWithdrawalRepo())->listRequested();
        } catch (\Throwable $e) {
            $list = [];
        }
        echo View::adminPage('admin/withdrawals.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function approve(array $params): void
    {
        $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        try {
            (new AffiliateWithdrawalRepo())->setStatus($id, 'approved');
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Retiro aprobado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/withdrawals');
    }

    public function paid(array $params): void
    {
        $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        try {
            (new AffiliateWithdrawalRepo())->setStatus($id, 'paid');
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Retiro marcado como pagado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/withdrawals');
    }

    public function reject(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        try {
            (new AffiliateWithdrawalRepo())->rejectAndRefund($id, (int)$adminUser['id'], $reason);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Retiro rechazado y reintegrado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/withdrawals');
    }
}
