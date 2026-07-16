<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\AdminProductRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class PriceUpdateController
{
    private AdminProductRepo $repo;
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->repo = new AdminProductRepo();
        $this->auth = new AdminAuthService();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireSesion();

        $q = trim((string)($_GET['q'] ?? ''));
        $codsub = (int)($_GET['codsub'] ?? 0);
        $codprove = trim((string)($_GET['codprove'] ?? ''));
        $fecompraDesde = trim((string)($_GET['fecompra_desde'] ?? ''));
        $fecompraHasta = trim((string)($_GET['fecompra_hasta'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(10, min(200, (int)($_GET['per_page'] ?? 50)));

        $brands = $this->repo->brandOptions();
        $categories = $this->repo->categoryOptions();
        $proveedores = (new \Perfushopping\Web\Repo\ProveedorRepo())->findAll();

        $result = $this->repo->searchForPriceUpdate($q, $codsub, $codprove, $fecompraDesde, $fecompraHasta, $page, $perPage);

        echo View::adminPage('admin/productos/price_update.php', [
            'adminUser' => $adminUser,
            'q' => $q,
            'codsub' => $codsub,
            'codprove' => $codprove,
            'fecompraDesde' => $fecompraDesde,
            'fecompraHasta' => $fecompraHasta,
            'page' => $page,
            'perPage' => $perPage,
            'total' => $result['total'],
            'brands' => $brands,
            'categories' => $categories,
            'proveedores' => $proveedores,
            'products' => $result['items'],
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
            'pageTitle' => 'Actualizar precios',
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function apply(array $params): void
    {
        $this->auth->requireSesion();
        Csrf::check($_POST['_csrf'] ?? null);

        $percentage = (float)str_replace(',', '.', trim((string)($_POST['porcentaje'] ?? '0')));
        $selection = $_POST['productos'] ?? [];

        if ($percentage === 0.0) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'El porcentaje debe ser distinto de cero.'];
            Response::redirect('/admin/productos/actualizar-precios');
        }

        if (!is_array($selection) || !$selection) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'Selecciona al menos un producto.'];
            Response::redirect('/admin/productos/actualizar-precios');
        }

        $ids = array_map('intval', $selection);
        $ids = array_values(array_filter($ids, static fn(int $id): bool => $id > 0));

        if (!$ids) {
            $_SESSION['admin_flash'] = ['type' => 'warning', 'text' => 'IDs de producto inválidos.'];
            Response::redirect('/admin/productos/actualizar-precios');
        }

        $updated = $this->repo->bulkPriceUpdate($ids, $percentage);

        $signo = $percentage > 0 ? 'subió' : 'bajó';
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => "Precios actualizados: {$updated} productos {$signo} un {$percentage}%."];
        Response::redirect('/admin/productos/actualizar-precios');
    }
}
