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
            'tema' => PortadaRepo::tema($config),
            'plantillas' => PortadaRepo::plantillas(),
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

        $banner = [
            'text' => trim((string)($_POST['banner_text'] ?? '')),
            'bold' => isset($_POST['banner_bold']) ? 1 : 0,
            'italic' => isset($_POST['banner_italic']) ? 1 : 0,
            'size' => trim((string)($_POST['banner_size'] ?? 'grande')),
            'color' => trim((string)($_POST['banner_color'] ?? '')),
            'bg' => isset($_POST['banner_bg_transp']) ? null : trim((string)($_POST['banner_bg'] ?? '')),
            'sub' => trim((string)($_POST['banner_sub'] ?? '')),
            'sub_bold' => isset($_POST['banner_sub_bold']) ? 1 : 0,
            'sub_italic' => isset($_POST['banner_sub_italic']) ? 1 : 0,
            'sub_size' => trim((string)($_POST['banner_sub_size'] ?? 'normal')),
            'sub_color' => trim((string)($_POST['banner_sub_color'] ?? '')),
        ];

        $theme = [
            'plantilla' => trim((string)($_POST['plantilla'] ?? 'elegante')),
            'color_fondo' => trim((string)($_POST['color_fondo'] ?? '')),
            'color_fondo_secundario' => trim((string)($_POST['color_fondo_secundario'] ?? '')),
            'color_tarjeta' => trim((string)($_POST['color_tarjeta'] ?? '')),
            'color_acento' => trim((string)($_POST['color_acento'] ?? '')),
            'color_texto' => trim((string)($_POST['color_texto'] ?? '')),
            'fuente_tarjeta' => trim((string)($_POST['fuente_tarjeta'] ?? 'system')),
            'color_nombre_producto' => trim((string)($_POST['color_nombre_producto'] ?? '')),
            'tamano_nombre_producto' => (int)($_POST['tamano_nombre_producto'] ?? 14),
            'color_precio_producto' => trim((string)($_POST['color_precio_producto'] ?? '')),
            'tamano_precio_producto' => (int)($_POST['tamano_precio_producto'] ?? 18),
        ];

        (new PortadaRepo())->saveConfig($modo, $codrub ?: null, $codsub ?: null, $banner, $theme);

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
