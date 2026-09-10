<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\ArticleRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\VideoRepo;
use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ArticleController
{
    public function index(array $params): void
    {
        $articles = (new ArticleRepo())->findAll(true);
        echo View::page('articles/index.php', [
            'articles' => $articles,
            'title' => 'Artículos y guías',
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $article = (new ArticleRepo())->findBySlug($slug);
        if (!$article) {
            Response::notFound();
            return;
        }

        $articleRepo = new ArticleRepo();
        $productRepo = new ProductRepo();
        $products = [];
        foreach ($articleRepo->findProductIdsByArticleId((int)$article['id']) as $pid) {
            $p = $productRepo->find($pid);
            if ($p) $products[] = $p;
        }

        $videos = [];
        foreach ($products as $p) {
            foreach ((new VideoRepo())->findByProductId((int)$p['idprodu']) as $v) {
                $videos[(int)$v['id']] = $v;
            }
        }

        $seo = (new SeoRepo())->find('article', (int)$article['id']);
        echo View::page('articles/show.php', [
            'article' => $article,
            'products' => $products,
            'videos' => array_values($videos),
            'seo' => $seo,
            'title' => $article['seo_title'] ?: $article['title'],
        ]);
    }
}
