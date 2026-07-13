<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Response;

final class BadgeController
{
    public function badges(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireLogin();

        $orderRepo = new OrderRepo();
        $userRepo = new UserRepo();

        Response::json([
            'pedidos_nuevos' => $orderRepo->countPendingPayment(),
            'pedidos_abandonados' => $orderRepo->countAbandoned(),
            'usuarios_nuevos' => $userRepo->countNewToday(),
        ]);
    }
}
