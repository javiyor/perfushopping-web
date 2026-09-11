<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\CampaignRepo;
use Perfushopping\Web\Repo\Marketing\HomeBlockRepo;
use Perfushopping\Web\Repo\Marketing\NeedRepo;
use Perfushopping\Web\Repo\Marketing\RoutineRepo;
use Perfushopping\Web\Repo\Marketing\VideoRepo;
use Perfushopping\Web\Repo\Marketing\ArticleRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\MetaRepo;
use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CampaignController
{
    public function index(array $params): void
    {
        $campaigns = (new CampaignRepo())->findAll(true);
        echo View::page('campaigns/index.php', [
            'campaigns' => $campaigns,
            'title' => 'Campañas',
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $campaign = (new CampaignRepo())->findBySlug($slug);
        if (!$campaign) {
            Response::notFound();
            return;
        }
        $auth = new AuthService();
        $user = $auth->user();
        $isWholesale = $auth->isWholesaleApproved($user);
        $campaignId = (int)$campaign['id'];
        $seo = (new SeoRepo())->find('campaign', $campaignId);
        $blocks = (new HomeBlockRepo())->findForPage('campaign', $campaignId);

        echo View::page('campaigns/show.php', [
            'campaign' => $campaign,
            'seo' => $seo,
            'title' => $campaign['seo_title'] ?: $campaign['title'],
            'blocks' => $blocks,
            'homeNeeds' => (new NeedRepo())->findAll(true),
            'homeVideos' => (new VideoRepo())->findActive(),
            'homeArticles' => (new ArticleRepo())->findAll(true),
            'homeRoutines' => (new RoutineRepo())->findAll(true),
            'rubros' => (new MetaRepo())->rubros(),
            'products' => (new ProductRepo())->list(['limit' => 12]),
            'isWholesale' => $isWholesale,
        ]);
    }
}
