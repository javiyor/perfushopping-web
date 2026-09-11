<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\BrandPageRepo;
use Perfushopping\Web\Repo\MetaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class BrandPageController
{
    private AdminAuthService $auth;
    private BrandPageRepo $repo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new BrandPageRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_brands');
        $pages = $this->repo->findAll();
        $brands = (new MetaRepo())->marcas();
        echo View::adminPage('admin/marketing/brands/list.php', [
            'adminUser' => $adminUser,
            'pages' => $pages,
            'brands' => $brands,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Páginas de marca',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_brands');
        $brands = (new MetaRepo())->marcas();
        echo View::adminPage('admin/marketing/brands/form.php', [
            'adminUser' => $adminUser,
            'page' => null,
            'brands' => $brands,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nueva página de marca',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_brands');
        $id = (int)($params['id'] ?? 0);
        $page = $this->repo->findById($id);
        if (!$page) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Página no encontrada.'];
            Response::redirect('/admin/marketing/marcas');
        }
        $brands = (new MetaRepo())->marcas();
        echo View::adminPage('admin/marketing/brands/form.php', [
            'adminUser' => $adminUser,
            'page' => $page,
            'brands' => $brands,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar página de marca',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_brands');
        Csrf::check($_POST['_csrf'] ?? null);
        $brandId = (int)($_POST['brand_id'] ?? 0);
        $title = trim((string)($_POST['title'] ?? ''));
        if ($brandId <= 0 || $title === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Marca y título son obligatorios.'];
            Response::redirect('/admin/marketing/marcas');
        }
        $this->repo->save([
            'id' => (int)($_POST['id'] ?? 0),
            'brand_id' => $brandId,
            'slug' => trim((string)($_POST['slug'] ?? '')),
            'title' => $title,
            'description' => $_POST['description'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
        ]);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Página de marca guardada.'];
        Response::redirect('/admin/marketing/marcas');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_brands');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Página eliminada.'];
        Response::redirect('/admin/marketing/marcas');
    }
}
