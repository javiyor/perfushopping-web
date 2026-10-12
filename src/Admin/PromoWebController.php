<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\AdminProductRepo;
use Perfushopping\Web\Repo\PromoWebRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class PromoWebController
{
    public function index(array $params): void
    {
        $adminUser = (new AdminAuthService())->requirePermiso('productos');

        $q = trim((string)($_GET['q'] ?? ''));
        $searchResults = [];
        if ($q !== '') {
            $searchResults = (new AdminProductRepo())->search($q, 0, 0, 20, 'id', 'desc', 1, 20)['items'] ?? [];
        }

        echo View::adminPage('admin/promos/list.php', [
            'adminUser' => $adminUser,
            'promos' => (new PromoWebRepo())->listAdmin(),
            'banners' => (new PromoWebRepo())->listBannersAdmin(),
            'brands' => (new AdminProductRepo())->brandOptions(),
            'q' => $q,
            'searchResults' => $searchResults,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Promociones web',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function add(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        $modo = trim((string)($_POST['modo'] ?? 'precio'));
        $valor = (float)str_replace(',', '.', trim((string)($_POST['valor'] ?? '')));
        $back = '/admin/promos';

        $prod = $idprodu > 0 ? (new AdminProductRepo())->find($idprodu) : null;
        if (!$prod) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Producto no encontrado.'];
            Response::redirect($back);
        }
        $precioNet = (float)($prod['precio'] ?? 0);
        $tiva = (float)($prod['tiva'] ?? 0);
        if ($precioNet <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El producto no tiene precio minorista cargado.'];
            Response::redirect($back);
        }

        if ($modo === 'descuento') {
            if ($valor <= 0 || $valor >= 100) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'El descuento debe estar entre 0 y 100%.'];
                Response::redirect($back);
            }
            $promoNet = $precioNet * (1 - $valor / 100);
        } else {
            if ($valor <= 0) {
                $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Ingresá el precio final con IVA.'];
                Response::redirect($back);
            }
            $promoNet = $valor / (1 + $tiva / 100);
        }

        $ok = (new PromoWebRepo())->addOrUpdate($idprodu, $promoNet);
        if ($ok) {
            $final = number_format($promoNet * (1 + $tiva / 100), 0, ',', '.');
            $off = (int)round((1 - $promoNet / $precioNet) * 100);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Promo guardada: $' . $final . ' final (' . $off . '% OFF).'];
        } else {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo guardar la promo.'];
        }
        Response::redirect($back);
    }

    public function remove(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        if ($idprodu > 0) {
            (new PromoWebRepo())->remove($idprodu);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Promo quitada.'];
        }
        Response::redirect('/admin/promos');
    }

    public function toggle(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $idprodu = (int)($_POST['idprodu'] ?? 0);
        if ($idprodu > 0) {
            (new PromoWebRepo())->toggle($idprodu);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado.'];
        }
        Response::redirect('/admin/promos');
    }

    public function reorder(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $orden = (array)($_POST['orden'] ?? []);
        $map = [];
        foreach ($orden as $idprodu => $pos) {
            $map[(int)$idprodu] = (int)$pos;
        }
        if ($map) {
            (new PromoWebRepo())->saveOrder($map);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden actualizado.'];
        }
        Response::redirect('/admin/promos');
    }

    public function saveBanner(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $ok = (new PromoWebRepo())->saveBanner([
            'id' => (int)($_POST['id'] ?? 0),
            'titulo' => trim((string)($_POST['titulo'] ?? '')),
            'texto' => trim((string)($_POST['texto'] ?? '')),
            'codsub' => (int)($_POST['codsub'] ?? 0),
            'link' => trim((string)($_POST['link'] ?? '')),
            'color' => trim((string)($_POST['color'] ?? '')),
            'bg' => trim((string)($_POST['bg'] ?? '')),
        ]);
        $_SESSION['admin_flash'] = $ok
            ? ['type' => 'ok', 'text' => 'Banner guardado.']
            : ['type' => 'danger', 'text' => 'No se pudo guardar (revisá el título).'];
        Response::redirect('/admin/promos');
    }

    public function removeBanner(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new PromoWebRepo())->removeBanner($id);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Banner quitado.'];
        }
        Response::redirect('/admin/promos');
    }

    public function toggleBanner(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new PromoWebRepo())->toggleBanner($id);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Estado actualizado.'];
        }
        Response::redirect('/admin/promos');
    }

    public function reorderBanners(array $params): void
    {
        (new AdminAuthService())->requirePermiso('productos');
        Csrf::check($_POST['_csrf'] ?? null);

        $orden = (array)($_POST['orden'] ?? []);
        $map = [];
        foreach ($orden as $id => $pos) {
            $map[(int)$id] = (int)$pos;
        }
        if ($map) {
            (new PromoWebRepo())->saveBannerOrder($map);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Orden actualizado.'];
        }
        Response::redirect('/admin/promos');
    }
}
