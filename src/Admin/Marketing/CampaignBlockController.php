<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\CampaignRepo;
use Perfushopping\Web\Repo\Marketing\HomeBlockRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CampaignBlockController
{
    private AdminAuthService $auth;
    private HomeBlockRepo $repo;
    private CampaignRepo $campaignRepo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new HomeBlockRepo();
        $this->campaignRepo = new CampaignRepo();
    }

    private function requireCampaign(array $params): array
    {
        $campaignId = (int)($params['campaign_id'] ?? 0);
        $campaign = $this->campaignRepo->findById($campaignId);
        if (!$campaign) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Campaña no encontrada.'];
            Response::redirect('/admin/marketing/campanas');
        }
        return $campaign;
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_campaigns');
        $campaign = $this->requireCampaign($params);
        $blocks = $this->repo->findAllAdminForPage('campaign', (int)$campaign['id']);
        echo View::adminPage('admin/marketing/campaigns/blocks/list.php', [
            'adminUser' => $adminUser,
            'campaign' => $campaign,
            'blocks' => $blocks,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Bloques: ' . $campaign['name'],
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_campaigns');
        $campaign = $this->requireCampaign($params);
        echo View::adminPage('admin/marketing/home/form.php', [
            'adminUser' => $adminUser,
            'block' => null,
            'pageType' => 'campaign',
            'pageId' => (int)$campaign['id'],
            'formAction' => '/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques/guardar',
            'cancelUrl' => '/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques',
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo bloque: ' . $campaign['name'],
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_campaigns');
        $campaign = $this->requireCampaign($params);
        $id = (int)($params['id'] ?? 0);
        $block = $this->repo->findById($id);
        if (!$block || (int)$block['page_id'] !== (int)$campaign['id'] || $block['page_type'] !== 'campaign') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Bloque no encontrado.'];
            Response::redirect('/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques');
        }
        echo View::adminPage('admin/marketing/home/form.php', [
            'adminUser' => $adminUser,
            'block' => $block,
            'pageType' => 'campaign',
            'pageId' => (int)$campaign['id'],
            'formAction' => '/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques/guardar',
            'cancelUrl' => '/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques',
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar bloque: ' . $campaign['name'],
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_campaigns');
        Csrf::check($_POST['_csrf'] ?? null);
        $campaign = $this->requireCampaign($params);

        $blockType = trim((string)($_POST['block_type'] ?? ''));
        if ($blockType === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El tipo de bloque es obligatorio.'];
            Response::redirect('/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques');
        }

        $settings = [];
        foreach (array_keys($_POST['settings'] ?? []) as $k) {
            $settings[$k] = $_POST['settings'][$k];
        }

        $this->repo->save([
            'id' => (int)($_POST['id'] ?? 0),
            'page_type' => 'campaign',
            'page_id' => (int)$campaign['id'],
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
        Response::redirect('/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_campaigns');
        Csrf::check($_POST['_csrf'] ?? null);
        $campaign = $this->requireCampaign($params);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Bloque eliminado.'];
        Response::redirect('/admin/marketing/campanas/' . (int)$campaign['id'] . '/bloques');
    }

    public function toggle(array $params): void
    {
        $this->auth->requirePermiso('marketing_campaigns');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->toggleActive($id);
        Response::json(['ok' => true]);
    }

    public function reorder(array $params): void
    {
        $this->auth->requirePermiso('marketing_campaigns');
        Csrf::check($_POST['_csrf'] ?? null);
        $raw = $_POST['ids'] ?? [];
        if (is_string($raw) && $raw !== '') {
            $ids = array_filter(array_map('intval', explode(',', $raw)));
        } else {
            $ids = array_filter(array_map('intval', (array)$raw));
        }
        $this->repo->reorder($ids);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden actualizado.'];
        Response::redirect('/admin/marketing/campanas/' . (int)($params['campaign_id'] ?? 0) . '/bloques');
    }
}
