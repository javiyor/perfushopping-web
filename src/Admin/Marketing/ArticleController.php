<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\ArticleRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class ArticleController
{
    private AdminAuthService $auth;
    private ArticleRepo $repo;
    private SeoRepo $seo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new ArticleRepo();
        $this->seo = new SeoRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_articles');
        $articles = $this->repo->findAll();
        echo View::adminPage('admin/marketing/articles/list.php', [
            'adminUser' => $adminUser,
            'articles' => $articles,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Artículos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_articles');
        echo View::adminPage('admin/marketing/articles/form.php', [
            'adminUser' => $adminUser,
            'article' => null,
            'seo' => null,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo artículo',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_articles');
        $id = (int)($params['id'] ?? 0);
        $article = $this->repo->findById($id);
        if (!$article) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Artículo no encontrado.'];
            Response::redirect('/admin/marketing/articulos');
        }
        $seo = $this->seo->find('article', $id);
        echo View::adminPage('admin/marketing/articles/form.php', [
            'adminUser' => $adminUser,
            'article' => $article,
            'seo' => $seo,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar artículo',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_articles');
        Csrf::check($_POST['_csrf'] ?? null);

        $title = trim((string)($_POST['title'] ?? ''));
        if ($title === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El título es obligatorio.'];
            Response::redirect('/admin/marketing/articulos');
        }

        $id = (int)($_POST['id'] ?? 0);
        $slug = trim((string)($_POST['slug'] ?? ''));
        if ($slug === '') $slug = $title;

        $articleId = $this->repo->save([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $_POST['excerpt'] ?? '',
            'content' => $_POST['content'] ?? '',
            'status' => $_POST['status'] ?? 'draft',
            'published_at' => $_POST['published_at'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
        ]);

        $this->seo->save('article', $articleId, [
            'slug' => $this->repo->findById($articleId)['slug'] ?? '',
            'title' => $_POST['seo_title'] ?? $title,
            'meta_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
            'indexable' => (isset($_POST['active']) && ($_POST['status'] ?? '') === 'published') ? 1 : 0,
        ]);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Artículo guardado correctamente.'];
        Response::redirect('/admin/marketing/articulos');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_articles');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $this->seo->delete('article', $id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Artículo eliminado.'];
        Response::redirect('/admin/marketing/articulos');
    }
}
