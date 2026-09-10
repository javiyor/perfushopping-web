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

final class NeedController
{
    private AdminAuthService $auth;
    private NeedRepo $repo;
    private SeoRepo $seo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new NeedRepo();
        $this->seo = new SeoRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_needs');
        $needs = $this->repo->findAll();
        echo View::adminPage('admin/marketing/needs/list.php', [
            'adminUser' => $adminUser,
            'needs' => $needs,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Necesidades',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_needs');
        echo View::adminPage('admin/marketing/needs/form.php', [
            'adminUser' => $adminUser,
            'need' => null,
            'seo' => null,
            'products' => [],
            'videos' => [],
            'articles' => [],
            'faqs' => [],
            'topics' => [],
            'allTopics' => (new TopicRepo())->findAll(true),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nueva necesidad',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_needs');
        $id = (int)($params['id'] ?? 0);
        $need = $this->repo->findById($id);
        if (!$need) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Necesidad no encontrada.'];
            Response::redirect('/admin/marketing/necesidades');
        }
        $seo = $this->seo->find('need', $id);
        echo View::adminPage('admin/marketing/needs/form.php', [
            'adminUser' => $adminUser,
            'need' => $need,
            'seo' => $seo,
            'products' => $this->repo->findProducts($id),
            'videos' => $this->repo->findVideos($id),
            'articles' => $this->repo->findArticles($id),
            'faqs' => $this->repo->findFaqs($id),
            'topics' => $this->repo->findTopics($id),
            'allTopics' => (new TopicRepo())->findAll(true),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar necesidad',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_needs');
        Csrf::check($_POST['_csrf'] ?? null);

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre es obligatorio.'];
            Response::redirect('/admin/marketing/necesidades');
        }

        $id = (int)($_POST['id'] ?? 0);
        $slug = trim((string)($_POST['slug'] ?? ''));

        $needId = $this->repo->save([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'icon' => $_POST['icon'] ?? '',
            'image' => $_POST['image'] ?? '',
            'description_short' => $_POST['description_short'] ?? '',
            'description_long' => $_POST['description_long'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
        ]);

        $saved = $this->repo->findById($needId);
        $this->seo->save('need', $needId, [
            'slug' => $saved['slug'] ?? '',
            'title' => $_POST['seo_title'] ?? $name,
            'meta_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
            'indexable' => (isset($_POST['active']) ? 1 : 0),
        ]);

        $this->repo->setProducts($needId, (array)($_POST['products'] ?? []));
        $this->repo->setVideos($needId, (array)($_POST['videos'] ?? []));
        $this->repo->setArticles($needId, (array)($_POST['articles'] ?? []));
        $this->repo->setFaqs($needId, (array)($_POST['faqs'] ?? []));
        $this->repo->setTopics($needId, (array)($_POST['topics'] ?? []));

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Necesidad guardada correctamente.'];
        Response::redirect('/admin/marketing/necesidades');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_needs');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $this->seo->delete('need', $id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Necesidad eliminada.'];
        Response::redirect('/admin/marketing/necesidades');
    }
}
