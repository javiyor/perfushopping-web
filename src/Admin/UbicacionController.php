<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\UbicacionRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class UbicacionController
{
    /** Recibe la posición del celular cada 5 minutos (cualquier usuario logueado). */
    public function guardar(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireLogin();

        $input = json_decode(file_get_contents('php://input'), true);
        Csrf::check($input['_csrf'] ?? null);
        if (!is_array($input)) {
            Response::json(['ok' => false, 'error' => 'Datos inválidos.']);
            return;
        }

        $lat = (float)($input['lat'] ?? 999);
        $lng = (float)($input['lng'] ?? 999);
        $acc = isset($input['accuracy']) ? max(0, (int)$input['accuracy']) : null;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            Response::json(['ok' => false, 'error' => 'Coordenadas inválidas.']);
            return;
        }

        (new UbicacionRepo())->guardar((int)$adminUser['id'], $lat, $lng, $acc);
        Response::json(['ok' => true]);
    }

    /** Visor de ubicaciones: solo superadmin. */
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');

        echo View::adminPage('admin/ubicaciones/index.php', [
            'adminUser' => $adminUser,
            'ultimas' => (new UbicacionRepo())->ultimas(),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Ubicaciones del personal',
        ]);
    }

    public function data(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireRol('superadmin');

        Response::json(['ok' => true, 'ultimas' => (new UbicacionRepo())->ultimas()]);
    }
}
