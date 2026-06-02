<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\AffiliateLedgerRepo;
use Perfushopping\Web\Repo\AffiliateRepo;
use Perfushopping\Web\Repo\AffiliateWithdrawalRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class AffiliateController
{
    public function dashboard(array $params): void
    {
        $auth = new AuthService();
        $u = $auth->requireLogin();
        $ledger = new AffiliateLedgerRepo();
        $balance = 0;
        $movs = [];
        try {
            $balance = $ledger->balanceAvailableCents((int)$u['id']);
            $movs = $ledger->listRecent((int)$u['id'], 80);
        } catch (\Throwable $e) {
            $balance = 0;
            $movs = [];
        }
        $refCode = '';
        try {
            $refCode = (new AffiliateRepo())->ensureForUser((int)$u['id']);
        } catch (\Throwable $e) {
            $refCode = '';
        }
        $withdrawals = [];
        try {
            $withdrawals = (new AffiliateWithdrawalRepo())->listForUser((int)$u['id']);
        } catch (\Throwable $e) {
            $withdrawals = [];
        }

        echo View::page('affiliate/dashboard.php', [
            'user' => $u,
            'csrf' => Csrf::token(),
            'balance' => $balance,
            'refCode' => $refCode,
            'withdrawals' => $withdrawals,
            'movs' => $movs,
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function requestWithdraw(array $params): void
    {
        $auth = new AuthService();
        $u = $auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);

        $amountRaw = trim((string)($_POST['amount'] ?? ''));
        $cbu = trim((string)($_POST['cbu'] ?? ''));
        $alias = trim((string)($_POST['alias'] ?? ''));
        $titular = trim((string)($_POST['titular'] ?? ''));

        $amount = (float)str_replace([',', ' '], ['.', ''], $amountRaw);
        $amountCents = (int)round($amount * 100);

        if ($amountCents <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Importe invalido.'];
            Response::redirect('/affiliate');
        }
        if ($titular === '' || ($cbu === '' && $alias === '')) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Completa titular y CBU o alias.'];
            Response::redirect('/affiliate');
        }

        $dest = 'Titular: ' . $titular;
        if ($cbu !== '') {
            $dest .= ' | CBU: ' . $cbu;
        }
        if ($alias !== '') {
            $dest .= ' | Alias: ' . $alias;
        }

        try {
            $id = (new AffiliateWithdrawalRepo())->createRequest((int)$u['id'], $amountCents, $dest);
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Solicitud enviada. Retiro #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/affiliate');
    }
}
