<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\NeedRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class NeedController
{
    public function index(array $params): void
    {
        $needs = (new NeedRepo())->findAll(true);
        echo View::page('needs/index.php', [
            'needs' => $needs,
            'title' => 'Soluciones',
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $need = (new NeedRepo())->findBySlug($slug);
        if (!$need) {
            Response::notFound();
            return;
        }

        $repo = new NeedRepo();
        $id = (int)$need['id'];
        $seo = (new SeoRepo())->find('need', $id);
        echo View::page('needs/show.php', [
            'need' => $need,
            'products' => $repo->findProducts($id),
            'videos' => $repo->findVideos($id),
            'articles' => $repo->findArticles($id),
            'faqs' => $repo->findFaqs($id),
            'topics' => $repo->findTopics($id),
            'seo' => $seo,
            'title' => $need['seo_title'] ?: $need['name'],
        ]);
    }
}
