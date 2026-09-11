<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\CampaignRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
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
        $seo = (new SeoRepo())->find('campaign', (int)$campaign['id']);
        echo View::page('campaigns/show.php', [
            'campaign' => $campaign,
            'seo' => $seo,
            'title' => $campaign['seo_title'] ?: $campaign['title'],
        ]);
    }
}
