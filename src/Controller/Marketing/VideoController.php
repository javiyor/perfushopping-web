<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\ArticleRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\VideoRepo;
use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class VideoController
{
    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $video = (new VideoRepo())->findBySlug($slug);
        if (!$video) {
            Response::notFound();
            return;
        }

        $videoRepo = new VideoRepo();
        $productRepo = new ProductRepo();
        $products = [];
        foreach ($videoRepo->findProductIdsByVideoId((int)$video['id']) as $pid) {
            $p = $productRepo->find($pid);
            if ($p) $products[] = $p;
        }

        $articles = [];
        foreach ($products as $p) {
            foreach ((new ArticleRepo())->findByProductId((int)$p['idprodu']) as $a) {
                $articles[(int)$a['id']] = $a;
            }
        }

        $seo = (new SeoRepo())->find('video', (int)$video['id']);
        echo View::page('videos/show.php', [
            'video' => $video,
            'products' => $products,
            'articles' => array_values($articles),
            'seo' => $seo,
            'title' => $video['seo_title'] ?: $video['title'],
        ]);
    }
}
