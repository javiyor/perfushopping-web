<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\CorreoRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Service\CorreoArgentinoService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CorreoController
{
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $savedAgencies = [];
        try {
            $savedAgencies = (new CorreoRepo())->listAgencies(null, null, null, null, 300);
        } catch (\Throwable $e) {
            $savedAgencies = [];
        }
        echo View::adminPage('admin/correo.php', [
            'adminUser' => $adminUser,
            'csrf' => Csrf::token(),
            'authOk' => $_SESSION['correo_auth_ok'] ?? null,
            'agencies' => $_SESSION['correo_agencies'] ?? [],
            'agenciesSaved' => $_SESSION['correo_agencies_saved'] ?? null,
            'savedAgencies' => $savedAgencies,
            'savedFilters' => $_SESSION['correo_saved_filters'] ?? ['stateId' => '', 'cityName' => ''],
            'agencyFilters' => $_SESSION['correo_agency_filters'] ?? ['stateId' => '', 'pickup_availability' => '', 'package_reception' => ''],
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash'], $_SESSION['correo_auth_ok'], $_SESSION['correo_agencies'], $_SESSION['correo_agencies_saved'], $_SESSION['correo_saved_filters']);
    }

    public function auth(array $params): void
    {
        $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        try {
            (new CorreoArgentinoService())->auth();
            $_SESSION['correo_auth_ok'] = true;
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Credenciales de Correo Argentino validadas correctamente.'];
        } catch (\Throwable $e) {
            $_SESSION['correo_auth_ok'] = false;
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/correo');
    }

    public function agencies(array $params): void
    {
        $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $stateId = strtoupper(trim((string)($_POST['stateId'] ?? '')));
        $pickupRaw = trim((string)($_POST['pickup_availability'] ?? ''));
        $receptionRaw = trim((string)($_POST['package_reception'] ?? ''));

        $pickup = $pickupRaw === '' ? null : $pickupRaw === '1';
        $reception = $receptionRaw === '' ? null : $receptionRaw === '1';

        $_SESSION['correo_agency_filters'] = [
            'stateId' => $stateId,
            'pickup_availability' => $pickupRaw,
            'package_reception' => $receptionRaw,
        ];

        try {
            $agencies = (new CorreoArgentinoService())->agencies($stateId !== '' ? $stateId : null, $pickup, $reception);
            $repo = new CorreoRepo();
            $saved = 0;
            foreach ($agencies as $agency) {
                if (!is_array($agency)) continue;
                $repo->upsertAgency($agency);
                $saved++;
            }
            $_SESSION['correo_agencies'] = $agencies;
            $_SESSION['correo_agencies_saved'] = $saved;
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Sucursales consultadas: ' . count($agencies) . '. Guardadas/actualizadas: ' . $saved . '.'];
        } catch (\Throwable $e) {
            $_SESSION['correo_agencies'] = [];
            $_SESSION['correo_agencies_saved'] = 0;
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/correo');
    }

    public function savedAgencies(array $params): void
    {
        $this->auth->requireLogin();
        $stateId = strtoupper(trim((string)($_GET['stateId'] ?? '')));
        $cityName = trim((string)($_GET['cityName'] ?? ''));

        $_SESSION['correo_saved_filters'] = [
            'stateId' => $stateId,
            'cityName' => $cityName,
        ];

        try {
            $list = (new CorreoRepo())->listAgencies(
                $stateId !== '' ? $stateId : null,
                $cityName !== '' ? $cityName : null,
                null, null, 500
            );
            $_SESSION['correo_agencies'] = $list;
            $_SESSION['correo_agencies_saved'] = count($list);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Sucursales en base: ' . count($list) . '.'];
        } catch (\Throwable $e) {
            $_SESSION['correo_agencies'] = [];
            $_SESSION['correo_agencies_saved'] = 0;
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/correo');
    }
}
