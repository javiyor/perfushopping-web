<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\ArticleRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\VideoRepo;
use Perfushopping\Web\Repo\ProductRepo;
use Perfushopping\Web\Repo\WebStatsRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class LearnController
{
    public function index(array $params): void
    {
        $videos = (new VideoRepo())->findActive();
        $videoViews = (new WebStatsRepo())->vistasVideos(array_map(static fn(array $video): string => (string)($video['slug'] ?? ''), $videos));
        foreach ($videos as &$video) {
            $video['vistas'] = $videoViews[mb_strtolower((string)($video['slug'] ?? ''))] ?? 0;
        }
        unset($video);
        $articles = (new ArticleRepo())->findAll(true);
        echo View::page('videos/index.php', [
            'videos' => $videos,
            'articles' => $articles,
            'title' => 'Aprendé con Perfushopping',
        ]);
    }
}
