<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\AffiliateLedgerRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;

final class AffiliateController
{
    public function release(array $params): void
    {
        (new AdminAuthService())->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $n = 0;
        try {
            $n = (new AffiliateLedgerRepo())->releaseDueCommissions();
        } catch (\Throwable $e) {
            $n = 0;
        }
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Comisiones liberadas: ' . $n];
        Response::redirect('/admin');
    }
}
