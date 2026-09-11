<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\BrandPageRepo;
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

final class BrandPageController
{
    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $page = (new BrandPageRepo())->findBySlug($slug);
        if (!$page) {
            Response::notFound();
            return;
        }
        $auth = new AuthService();
        $user = $auth->user();
        $isWholesale = $auth->isWholesaleApproved($user);
        $brandId = (int)$page['brand_id'];
        $seo = (new SeoRepo())->find('brand', $brandId);
        $blocks = (new HomeBlockRepo())->findForPage('brand', $brandId);

        $meta = new MetaRepo();
        $products = (new ProductRepo())->list(['codsub' => $brandId, 'limit' => 12]);

        echo View::page('brands/show.php', [
            'page' => $page,
            'seo' => $seo,
            'title' => $page['seo_title'] ?: $page['title'],
            'blocks' => $blocks,
            'homeNeeds' => (new NeedRepo())->findAll(true),
            'homeVideos' => (new VideoRepo())->findActive(),
            'homeArticles' => (new ArticleRepo())->findAll(true),
            'homeRoutines' => (new RoutineRepo())->findAll(true),
            'rubros' => $meta->rubros(),
            'products' => $products,
            'isWholesale' => $isWholesale,
        ]);
    }
}
