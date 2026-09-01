<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\AdminProductRepo;
use Perfushopping\Web\Repo\MetaRepo;
use Perfushopping\Web\Repo\PortadaRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class PortadaController
{
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('productos');

        $repo = new PortadaRepo();
        $config = $repo->getConfig();
        $manual = $repo->getManualProducts();
        $meta = new MetaRepo();
        $rubros = $meta->rubros();
        $marcas = $meta->marcas();

        $q = trim((string)($_GET['q'] ?? ''));
        $searchResults = [];
        if ($q !== '') {
            $searchResults = (new AdminProductRepo())->search($q, 0, 0, 20, 'id', 'desc', 1, 20)['items'] ?? [];
        }

        echo View::adminPage('admin/portada.php', [
            'adminUser' => $adminUser,
            'config' => $config,
            'manual' => $manual,
            'rubros' => $rubros,
            'marcas' => $marcas,
            'q' => $q,
            'searchResults' => $searchResults,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Portada',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function save(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $modo = trim((string)($_POST['modo'] ?? 'auto'));
        $codrub = (int)($_POST['codrub'] ?? 0);
        $codsub = (int)($_POST['codsub'] ?? 0);

        (new PortadaRepo())->saveConfig($modo, $codrub ?: null, $codsub ?: null);

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Portada actualizada.'];
        Response::redirect('/admin/portada');
    }

    public function addManual(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        if ($idprodu > 0) {
            $ok = (new PortadaRepo())->addManual($idprodu);
            $_SESSION['admin_flash'] = $ok
                ? ['type' => 'ok', 'text' => 'Producto agregado a portada.']
                : ['type' => 'danger', 'text' => 'No se pudo agregar (ya existe o no encontrado).'];
        }
        Response::redirect('/admin/portada?modo=manual');
    }

    public function addManyManual(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $ids = $_POST['idprodu'] ?? [];
        if (!is_array($ids)) $ids = [$ids];
        $repo = new PortadaRepo();
        $added = 0;
        foreach ($ids as $id) {
            if ($repo->addManual((int)$id)) $added++;
        }
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Agregados $added productos a portada."];
        Response::redirect('/admin/portada?modo=manual');
    }

    public function removeManual(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        if ($idprodu > 0) {
            (new PortadaRepo())->removeManual($idprodu);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Producto quitado de portada.'];
        }
        Response::redirect('/admin/portada?modo=manual');
    }

    public function clearManual(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        (new PortadaRepo())->clearManual();
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Portada manual vaciada.'];
        Response::redirect('/admin/portada?modo=manual');
    }

    public function reorderManual(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $order = $_POST['orden'] ?? '';
        if (is_string($order)) {
            $ids = array_map('intval', explode(',', $order));
        } elseif (is_array($order)) {
            $ids = array_map('intval', $order);
        } else {
            $ids = [];
        }
        $ids = array_values(array_filter($ids, fn(int $v): bool => $v > 0));
        if ($ids) {
            (new PortadaRepo())->reorderManual($ids);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden actualizado.'];
        }
        Response::redirect('/admin/portada?modo=manual');
    }

    public function searchJson(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requirePermiso('productos');
        $q = trim((string)($_GET['q'] ?? ''));
        if ($q === '') {
            Response::json(['items' => []], 200);
            return;
        }
        $items = (new AdminProductRepo())->search($q, 0, 0, 20, 'id', 'desc', 1, 20)['items'] ?? [];
        $out = [];
        foreach ($items as $it) {
            $out[] = [
                'idprodu' => (int)($it['idprodu'] ?? 0),
                'produ' => (string)($it['produ'] ?? ''),
                'codrub' => (int)($it['codrub'] ?? 0),
                'codsub' => (int)($it['codsub'] ?? 0),
            ];
        }
        Response::json(['items' => $out], 200);
    }
}
