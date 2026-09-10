<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller\Marketing;

use Perfushopping\Web\Repo\Marketing\NeedRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\TopicRepo;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class TopicController
{
    public function index(array $params): void
    {
        $topics = (new TopicRepo())->findAll(true);
        echo View::page('topics/index.php', [
            'topics' => $topics,
            'title' => 'Temas',
        ]);
    }

    public function show(array $params): void
    {
        $slug = (string)($params['slug'] ?? '');
        $topic = (new TopicRepo())->findBySlug($slug);
        if (!$topic) {
            Response::notFound();
            return;
        }

        $repo = new TopicRepo();
        $id = (int)$topic['id'];
        $seo = (new SeoRepo())->find('topic', $id);
        echo View::page('topics/show.php', [
            'topic' => $topic,
            'products' => $repo->findProducts($id),
            'videos' => $repo->findVideos($id),
            'articles' => $repo->findArticles($id),
            'faqs' => $repo->findFaqs($id),
            'seo' => $seo,
            'title' => $topic['seo_title'] ?: $topic['name'],
        ]);
    }
}
