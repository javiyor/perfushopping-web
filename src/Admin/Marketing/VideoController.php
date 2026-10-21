<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\VideoRepo;
use Perfushopping\Web\Repo\AdminProductRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class VideoController
{
    private AdminAuthService $auth;
    private VideoRepo $repo;
    private SeoRepo $seo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new VideoRepo();
        $this->seo = new SeoRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_videos');
        $videos = $this->repo->findAll();
        echo View::adminPage('admin/marketing/videos/list.php', [
            'adminUser' => $adminUser,
            'videos' => $videos,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Videos',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_videos');
        echo View::adminPage('admin/marketing/videos/form.php', [
            'adminUser' => $adminUser,
            'video' => null,
            'seo' => null,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nuevo video',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_videos');
        $id = (int)($params['id'] ?? 0);
        $video = $this->repo->findById($id);
        if (!$video) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Video no encontrado.'];
            Response::redirect('/admin/marketing/videos');
        }
        $seo = $this->seo->find('video', $id);
        echo View::adminPage('admin/marketing/videos/form.php', [
            'adminUser' => $adminUser,
            'video' => $video,
            'selectedProducts' => $this->selectedProducts($this->repo->findProductIdsByVideoId($id)),
            'seo' => $seo,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar video',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_videos');
        Csrf::check($_POST['_csrf'] ?? null);

        $title = trim((string)($_POST['title'] ?? ''));
        $url = trim((string)($_POST['url'] ?? ''));
        if ($title === '' || $url === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Título y URL son obligatorios.'];
            Response::redirect('/admin/marketing/videos');
        }

        $id = (int)($_POST['id'] ?? 0);
        $slug = trim((string)($_POST['slug'] ?? ''));
        if ($slug === '') $slug = $title;
        $thumbnail = trim((string)($_POST['thumbnail'] ?? ''));
        if ($thumbnail === '') $thumbnail = VideoRepo::youtubeThumb($url);

        $videoId = $this->repo->save([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'description_short' => $_POST['description_short'] ?? '',
            'description_long' => $_POST['description_long'] ?? '',
            'thumbnail' => $thumbnail,
            'source' => 'youtube',
            'url' => $url,
            'duration' => (int)($_POST['duration'] ?? 0),
            'instructor' => $_POST['instructor'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'featured' => isset($_POST['featured']) ? 1 : 0,
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
        ]);

        $this->seo->save('video', $videoId, [
            'slug' => $this->repo->findById($videoId)['slug'] ?? '',
            'title' => $_POST['seo_title'] ?? $title,
            'meta_description' => $_POST['seo_description'] ?? '',
            'indexable' => isset($_POST['active']) ? 1 : 0,
        ]);

        $productIds = $_POST['product_ids'] ?? [];
        $this->repo->saveProductIds($videoId, is_array($productIds) ? $productIds : []);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Video guardado correctamente.'];
        Response::redirect('/admin/marketing/videos');
    }

    public function searchProducts(array $params): void
    {
        $this->auth->requirePermiso('marketing_videos');
        $q = trim((string)($_GET['q'] ?? ''));
        if (mb_strlen($q) < 2) {
            Response::json(['items' => []]);
            return;
        }
        $result = (new AdminProductRepo())->search($q, 0, 0, 10, 'produ', 'asc', 1, 10);
        Response::json(['items' => array_map(static fn(array $p): array => [
            'idprodu' => (int)$p['idprodu'],
            'produ' => (string)$p['produ'],
        ], $result['items'] ?? [])]);
    }

    /** @param array<int,int> $ids @return array<int,array{idprodu:int,produ:string}> */
    private function selectedProducts(array $ids): array
    {
        $products = [];
        foreach ($ids as $id) {
            $result = (new AdminProductRepo())->search((string)(int)$id, 0, 0, 1, 'id', 'asc', 1, 1);
            foreach ($result['items'] ?? [] as $product) {
                if ((int)$product['idprodu'] === (int)$id) {
                    $products[] = ['idprodu' => (int)$id, 'produ' => (string)$product['produ']];
                    break;
                }
            }
        }
        return $products;
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_videos');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $this->seo->delete('video', $id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Video eliminado.'];
        Response::redirect('/admin/marketing/videos');
    }
}
