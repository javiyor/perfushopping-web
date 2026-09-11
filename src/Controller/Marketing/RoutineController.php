<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\RoutineRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class RoutineController
{
    public function index(array $params): void
    {
        $routines = (new RoutineRepo())->findAll(true);
        echo View::page('routines/index.php', [
            'routines' => $routines,
            'title' => 'Rutinas',
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $routine = (new RoutineRepo())->findBySlug($slug);
        if (!$routine) {
            Response::notFound();
            return;
        }

        $repo = new RoutineRepo();
        $id = (int)$routine['id'];
        $seo = (new SeoRepo())->find('routine', $id);
        echo View::page('routines/show.php', [
            'routine' => $routine,
            'items' => $repo->findItems($id),
            'needs' => $repo->findNeeds($id),
            'topics' => $repo->findTopics($id),
            'videos' => $repo->findVideos($id),
            'articles' => $repo->findArticles($id),
            'seo' => $seo,
            'title' => $routine['seo_title'] ?: $routine['name'],
        ]);
    }
}
