<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Repo\WebStatsRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class DashboardController
{
    public function index(array $params): void
    {
        $adminUser = $this->resolveAdmin();

        if (!$adminUser) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Inicia sesion para continuar.'];
            Response::redirect('/admin/login');
        }

        if (isset($adminUser['rol']) && !isset($adminUser['role'])) {
            $adminUser['role'] = $adminUser['rol'];
        }

        $pdo = Db::pdo();

        $pendingOrders = (new OrderRepo())->adminList('', 'pending_payment', 5);
        $paidOrders = (new OrderRepo())->adminList('', 'paid', 5);

        $webStats = new WebStatsRepo();
        $abandonedCarts = [];
        $topProducts = [];
        try {
            $abandonedCarts = (new OrderRepo())->findAbandonedCarts();
            $topProducts = $webStats->topProductos(5, 30);
        } catch (\Throwable $e) {
            $abandonedCarts = [];
            $topProducts = [];
        }

        $stats = [];
        try {
            $st = $pdo->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()");
            $stats['orders_today'] = (int)$st->fetchColumn();

            $st = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stats['orders_7d'] = (int)$st->fetchColumn();

            $st = $pdo->query("SELECT COUNT(*) FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stats['orders_30d'] = (int)$st->fetchColumn();

            $st = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending_payment'");
            $stats['pending_payment'] = (int)$st->fetchColumn();

            $st = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='paid'");
            $stats['paid'] = (int)$st->fetchColumn();

            $st = $pdo->query("SELECT COUNT(*) FROM orders WHERE status='pending_transfer'");
            $stats['pending_transfer'] = (int)$st->fetchColumn();

            $st = $pdo->query("SELECT COUNT(*) FROM web_users WHERE disabled_at IS NULL AND DATE(created_at) = CURDATE()");
            $stats['users_today'] = (int)$st->fetchColumn();

            $st = $pdo->query("SELECT COUNT(*) FROM admin_users WHERE activo = 1");
            $stats['admins'] = (int)$st->fetchColumn();
        } catch (\Throwable $e) {
            $stats = [];
        }

        $webStatsBlock = [];
        try {
            $webStatsBlock['visitas_hoy'] = $webStats->visitasHoy();
            $webStatsBlock['visitantes_7d'] = $webStats->visitantesUnicosEnDias(7);
            $webStatsBlock['abandoned'] = count($abandonedCarts);
        } catch (\Throwable $e) {
            $webStatsBlock = [
                'visitas_hoy' => 0,
                'visitantes_7d' => 0,
                'abandoned' => 0,
            ];
        }
        $stats = array_merge($stats, $webStatsBlock);

        echo View::adminPage('admin/dashboard.php', [
            'adminUser' => $adminUser,
            'stats' => $stats,
            'pendingOrders' => $pendingOrders,
            'paidOrders' => $paidOrders,
            'abandonedCarts' => $abandonedCarts,
            'topProducts' => $topProducts,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Panel Principal',
        ]);
        unset($_SESSION['admin_flash']);
    }

    private function resolveAdmin(): ?array
    {
        $adminAuth = new AdminAuthService();
        $u = $adminAuth->user();
        if ($u) {
            $u['role'] = $u['rol'] ?? '';
            return $u;
        }

        $oldAuth = new AuthService();
        $u2 = $oldAuth->user();
        if ($u2 && ($u2['role'] ?? '') === 'admin') {
            return [
                'id' => $u2['id'],
                'username' => $u2['email'],
                'nombre' => $u2['name'],
                'email' => $u2['email'],
                'rol' => 'superadmin',
                'role' => 'admin',
                'activo' => empty($u2['disabled_at']),
            ];
        }

        return null;
    }
}
