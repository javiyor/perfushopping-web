<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\Marketing\AnalyticsRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\View;

final class AnalyticsController
{
    private AdminAuthService $auth;
    private AnalyticsRepo $repo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new AnalyticsRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_analytics');
        $days = max(1, min(90, (int)($_GET['days'] ?? 30)));
        $since = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        $summary = $this->repo->summaryByType($since);
        $topPaths = $this->repo->topPaths($since);
        $topProductsRaw = $this->repo->topProducts($since);
        $pageviewsDaily = $this->repo->dailyCounts($since, 'pageview');
        $funnel = $this->repo->funnelSteps();

        $productIds = array_column(array_slice($topProductsRaw, 0, 20), 'product_id');
        $products = [];
        if ($productIds) {
            $in = implode(',', array_map('intval', $productIds));
            $st = Db::pdo()->query("SELECT idprodu, produ FROM producto WHERE idprodu IN ({$in})");
            foreach ($st->fetchAll() as $p) {
                $products[(int)$p['idprodu']] = $p['produ'];
            }
        }

        echo View::adminPage('admin/marketing/analytics/dashboard.php', [
            'adminUser' => $adminUser,
            'summary' => $summary,
            'topPaths' => $topPaths,
            'topProducts' => $topProductsRaw,
            'products' => $products,
            'pageviewsDaily' => $pageviewsDaily,
            'funnel' => $funnel,
            'days' => $days,
            'pageTitle' => 'Analytics',
        ]);
    }
}
