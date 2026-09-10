<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\ArticleRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\VideoRepo;
use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class LearnController
{
    public function index(array $params): void
    {
        $videos = (new VideoRepo())->findActive();
        $articles = (new ArticleRepo())->findAll(true);
        echo View::page('videos/index.php', [
            'videos' => $videos,
            'articles' => $articles,
            'title' => 'Aprendé con Perfushopping',
        ]);
    }
}
