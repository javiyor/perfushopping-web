<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\NeedRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\TopicRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class TopicController
{
    private AdminAuthService $auth;
    private TopicRepo $repo;
    private SeoRepo $seo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new TopicRepo();
        $this->seo = new SeoRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_topics');
        $topics = $this->repo->findAll();
        echo View::adminPage('admin/marketing/topics/list.php', [
            'adminUser' => $adminUser,
            'topics' => $topics,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Temas',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_topics');
        echo View::adminPage('admin/marketing/topics/form.php', [
            'adminUser' => $adminUser,
            'topic' => null,
            'seo' => null,
            'products' => [],
            'videos' => [],
            'articles' => [],
            'faqs' => [],
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo tema',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_topics');
        $id = (int)($params['id'] ?? 0);
        $topic = $this->repo->findById($id);
        if (!$topic) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Tema no encontrado.'];
            Response::redirect('/admin/marketing/temas');
        }
        $seo = $this->seo->find('topic', $id);
        echo View::adminPage('admin/marketing/topics/form.php', [
            'adminUser' => $adminUser,
            'topic' => $topic,
            'seo' => $seo,
            'products' => $this->repo->findProducts($id),
            'videos' => $this->repo->findVideos($id),
            'articles' => $this->repo->findArticles($id),
            'faqs' => $this->repo->findFaqs($id),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar tema',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_topics');
        Csrf::check($_POST['_csrf'] ?? null);

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre es obligatorio.'];
            Response::redirect('/admin/marketing/temas');
        }

        $id = (int)($_POST['id'] ?? 0);
        $slug = trim((string)($_POST['slug'] ?? ''));

        $topicId = $this->repo->save([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'image' => $_POST['image'] ?? '',
            'cover' => $_POST['cover'] ?? '',
            'description_short' => $_POST['description_short'] ?? '',
            'description_long' => $_POST['description_long'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
        ]);

        $saved = $this->repo->findById($topicId);
        $this->seo->save('topic', $topicId, [
            'slug' => $saved['slug'] ?? '',
            'title' => $_POST['seo_title'] ?? $name,
            'meta_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
            'indexable' => (isset($_POST['active']) ? 1 : 0),
        ]);

        $this->repo->setProducts($topicId, (array)($_POST['products'] ?? []));
        $this->repo->setVideos($topicId, (array)($_POST['videos'] ?? []));
        $this->repo->setArticles($topicId, (array)($_POST['articles'] ?? []));
        $this->repo->setFaqs($topicId, (array)($_POST['faqs'] ?? []));

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Tema guardado correctamente.'];
        Response::redirect('/admin/marketing/temas');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_topics');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $this->seo->delete('topic', $id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Tema eliminado.'];
        Response::redirect('/admin/marketing/temas');
    }
}
