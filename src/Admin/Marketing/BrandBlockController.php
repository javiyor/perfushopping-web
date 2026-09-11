<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\BrandPageRepo;
use Perfushopping\Web\Repo\Marketing\HomeBlockRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class BrandBlockController
{
    private AdminAuthService $auth;
    private HomeBlockRepo $repo;
    private BrandPageRepo $pageRepo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new HomeBlockRepo();
        $this->pageRepo = new BrandPageRepo();
    }

    private function requirePage(array $params): array
    {
        $pageId = (int)($params['page_id'] ?? 0);
        $page = $this->pageRepo->findById($pageId);
        if (!$page) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Página no encontrada.'];
            Response::redirect('/admin/marketing/marcas');
        }
        return $page;
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_brands');
        $page = $this->requirePage($params);
        $blocks = $this->repo->findAllAdminForPage('brand', (int)$page['brand_id']);
        echo View::adminPage('admin/marketing/brands/blocks/list.php', [
            'adminUser' => $adminUser,
            'page' => $page,
            'blocks' => $blocks,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Bloques: ' . $page['brand_name'],
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_brands');
        $page = $this->requirePage($params);
        echo View::adminPage('admin/marketing/home/form.php', [
            'adminUser' => $adminUser,
            'block' => null,
            'pageType' => 'brand',
            'pageId' => (int)$page['brand_id'],
            'formAction' => '/admin/marketing/marcas/' . (int)$page['id'] . '/bloques/guardar',
            'cancelUrl' => '/admin/marketing/marcas/' . (int)$page['id'] . '/bloques',
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo bloque: ' . $page['brand_name'],
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_brands');
        $page = $this->requirePage($params);
        $id = (int)($params['id'] ?? 0);
        $block = $this->repo->findById($id);
        if (!$block || (int)$block['page_id'] !== (int)$page['brand_id'] || $block['page_type'] !== 'brand') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Bloque no encontrado.'];
            Response::redirect('/admin/marketing/marcas/' . (int)$page['id'] . '/bloques');
        }
        echo View::adminPage('admin/marketing/home/form.php', [
            'adminUser' => $adminUser,
            'block' => $block,
            'pageType' => 'brand',
            'pageId' => (int)$page['brand_id'],
            'formAction' => '/admin/marketing/marcas/' . (int)$page['id'] . '/bloques/guardar',
            'cancelUrl' => '/admin/marketing/marcas/' . (int)$page['id'] . '/bloques',
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar bloque: ' . $page['brand_name'],
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_brands');
        Csrf::check($_POST['_csrf'] ?? null);
        $page = $this->requirePage($params);

        $blockType = trim((string)($_POST['block_type'] ?? ''));
        if ($blockType === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El tipo de bloque es obligatorio.'];
            Response::redirect('/admin/marketing/marcas/' . (int)$page['id'] . '/bloques');
        }

        $settings = [];
        foreach (array_keys($_POST['settings'] ?? []) as $k) {
            $settings[$k] = $_POST['settings'][$k];
        }

        $this->repo->save([
            'id' => (int)($_POST['id'] ?? 0),
            'page_type' => 'brand',
            'page_id' => (int)$page['brand_id'],
            'block_type' => $blockType,
            'position' => (int)($_POST['position'] ?? 0),
            'active' => isset($_POST['active']) ? 1 : 0,
            'start_at' => $_POST['start_at'] ?? null,
            'end_at' => $_POST['end_at'] ?? null,
            'title' => $_POST['title'] ?? '',
            'settings' => $settings,
            'content' => $_POST['content'] ?? '',
            'target_entity_type' => $_POST['target_entity_type'] ?? '',
            'target_entity_id' => (int)($_POST['target_entity_id'] ?? 0),
            'segment' => $_POST['segment'] ?? '',
            'tracking_data' => $_POST['tracking_data'] ?? '',
        ]);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Bloque guardado correctamente.'];
        Response::redirect('/admin/marketing/marcas/' . (int)$page['id'] . '/bloques');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_brands');
        Csrf::check($_POST['_csrf'] ?? null);
        $page = $this->requirePage($params);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Bloque eliminado.'];
        Response::redirect('/admin/marketing/marcas/' . (int)$page['id'] . '/bloques');
    }

    public function reorder(array $params): void
    {
        $this->auth->requirePermiso('marketing_brands');
        Csrf::check($_POST['_csrf'] ?? null);
        $raw = $_POST['ids'] ?? [];
        if (is_string($raw) && $raw !== '') {
            $ids = array_filter(array_map('intval', explode(',', $raw)));
        } else {
            $ids = array_filter(array_map('intval', (array)$raw));
        }
        $this->repo->reorder($ids);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden actualizado.'];
        Response::redirect('/admin/marketing/marcas/' . (int)($params['page_id'] ?? 0) . '/bloques');
    }
}
