<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin\Marketing;

use Perfushopping\Web\Repo\Marketing\NeedRepo;
use Perfushopping\Web\Repo\Marketing\RoutineRepo;
use Perfushopping\Web\Repo\Marketing\SeoRepo;
use Perfushopping\Web\Repo\Marketing\TopicRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class RoutineController
{
    private AdminAuthService $auth;
    private RoutineRepo $repo;
    private SeoRepo $seo;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
        $this->repo = new RoutineRepo();
        $this->seo = new SeoRepo();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_routines');
        $routines = $this->repo->findAll();
        echo View::adminPage('admin/marketing/routines/list.php', [
            'adminUser' => $adminUser,
            'routines' => $routines,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Rutinas',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function create(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_routines');
        echo View::adminPage('admin/marketing/routines/form.php', [
            'adminUser' => $adminUser,
            'routine' => null,
            'seo' => null,
            'items' => [],
            'needs' => [],
            'topics' => [],
            'videos' => [],
            'articles' => [],
            'allNeeds' => (new NeedRepo())->findAll(true),
            'allTopics' => (new TopicRepo())->findAll(true),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Nueva rutina',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function edit(array $params): void
    {
        $adminUser = $this->auth->requirePermiso('marketing_routines');
        $id = (int)($params['id'] ?? 0);
        $routine = $this->repo->findById($id);
        if (!$routine) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Rutina no encontrada.'];
            Response::redirect('/admin/marketing/rutinas');
        }
        $seo = $this->seo->find('routine', $id);
        echo View::adminPage('admin/marketing/routines/form.php', [
            'adminUser' => $adminUser,
            'routine' => $routine,
            'seo' => $seo,
            'items' => $this->repo->findItems($id),
            'needs' => $this->repo->findNeeds($id),
            'topics' => $this->repo->findTopics($id),
            'videos' => $this->repo->findVideos($id),
            'articles' => $this->repo->findArticles($id),
            'allNeeds' => (new NeedRepo())->findAll(true),
            'allTopics' => (new TopicRepo())->findAll(true),
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Editar rutina',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $this->auth->requirePermiso('marketing_routines');
        Csrf::check($_POST['_csrf'] ?? null);

        $name = trim((string)($_POST['name'] ?? ''));
        if ($name === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El nombre es obligatorio.'];
            Response::redirect('/admin/marketing/rutinas');
        }

        $id = (int)($_POST['id'] ?? 0);
        $slug = trim((string)($_POST['slug'] ?? ''));

        $routineId = $this->repo->save([
            'id' => $id,
            'name' => $name,
            'slug' => $slug,
            'image' => $_POST['image'] ?? '',
            'description' => $_POST['description'] ?? '',
            'problem' => $_POST['problem'] ?? '',
            'expected_result' => $_POST['expected_result'] ?? '',
            'active' => isset($_POST['active']) ? 1 : 0,
            'sort_order' => (int)($_POST['sort_order'] ?? 0),
            'seo_title' => $_POST['seo_title'] ?? '',
            'seo_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
        ]);

        $saved = $this->repo->findById($routineId);
        $this->seo->save('routine', $routineId, [
            'slug' => $saved['slug'] ?? '',
            'title' => $_POST['seo_title'] ?? $name,
            'meta_description' => $_POST['seo_description'] ?? '',
            'og_image' => $_POST['og_image'] ?? '',
            'indexable' => (isset($_POST['active']) ? 1 : 0),
        ]);

        $items = [];
        foreach ((array)($_POST['items']['product_id'] ?? []) as $i => $pid) {
            $pid = (int)$pid;
            if ($pid <= 0) continue;
            $items[] = [
                'product_id' => $pid,
                'step_order' => (int)($_POST['items']['step_order'][$i] ?? 0),
                'instructions' => trim((string)($_POST['items']['instructions'][$i] ?? '')),
                'optional' => isset($_POST['items']['optional'][$i]) ? 1 : 0,
            ];
        }
        usort($items, static fn($a, $b) => $a['step_order'] <=> $b['step_order']);
        $this->repo->setItems($routineId, $items);

        $this->repo->setNeeds($routineId, (array)($_POST['needs'] ?? []));
        $this->repo->setTopics($routineId, (array)($_POST['topics'] ?? []));
        $this->repo->setVideos($routineId, (array)($_POST['videos'] ?? []));
        $this->repo->setArticles($routineId, (array)($_POST['articles'] ?? []));

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Rutina guardada correctamente.'];
        Response::redirect('/admin/marketing/rutinas');
    }

    public function delete(array $params): void
    {
        $this->auth->requirePermiso('marketing_routines');
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $this->repo->delete($id);
        $this->seo->delete('routine', $id);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Rutina eliminada.'];
        Response::redirect('/admin/marketing/rutinas');
    }
}
