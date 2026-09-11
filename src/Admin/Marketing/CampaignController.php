<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\CampaignRepo;
use Perfushopping\Web\Repo\Marketing\HomeBlockRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CampaignController
{
    private AdminAuthService $auth;
    private CampaignRepo $repo;
    private SeoRepo $seo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new CampaignRepo();
        $this->seo = new SeoRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_campaigns');
        $campaigns = $this->repo->findAll();
        echo View::adminPage('admin/marketing/campaigns/list.php', [
            'adminUser' => $adminUser,
            'campaigns' => $campaigns,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Campañas',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_campaigns');
        echo View::adminPage('admin/marketing/campaigns/form.php', [
            'adminUser' => $adminUser,
            'campaign' => null,
            'seo' => null,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nueva campaña',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_campaigns');
        $id = (int)($params['id'] ?? 0);
        $campaign = $this->repo->findById($id);
        if (!$campaign) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Campaña no encontrada.'];
            Response::redirect('/admin/marketing/campanas');
        }
        $seo = $this->seo->find('campaign', $id);
        echo View::adminPage('admin/marketing/campaigns/form.php', [
            'adminUser' => $adminUser,
            'campaign' => $campaign,
            'seo' => $seo,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar campaña',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_campaigns');
        Csrf::check($_POST['_csrf'] ?? null);

        $name = trim((string)($_POST['name'] ?? ''));
        $title = trim((string)($_POST['title'] ?? ''));
        if ($name === '' || $title === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Nombre y título son obligatorios.'];
            Response::redirect('/admin/marketing/campanas');
        }

        $id = $this->repo->save([
            'id' => (int)($_POST['id'] ?? 0),
            'name' => $name,
            'slug' => trim((string)($_POST['slug'] ?? '')),
            'title' => $title,
            'description' => $_POST['description'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'start_at' => $_POST['start_at'] ?? null,
            'end_at' => $_POST['end_at'] ?? null,
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
        ]);

        $saved = $this->repo->findById($id);
        $this->seo->save('campaign', $id, [
            'slug' => $saved['slug'] ?? '',
            'title' => $_POST['seo_title'] ?? $title,
            'meta_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
            'indexable' => (isset($_POST['active']) ? 1 : 0),
        ]);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Campaña guardada.'];
        Response::redirect('/admin/marketing/campanas');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_campaigns');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $this->seo->delete('campaign', $id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Campaña eliminada.'];
        Response::redirect('/admin/marketing/campanas');
    }
}
