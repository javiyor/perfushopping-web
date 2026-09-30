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

    /**
     * Receptor para apps de tracking (OwnTracks / GPSLogger).
     * Funciona con la app cerrada: se autentica por token, sin sesión.
     * OwnTracks (HTTP): POST JSON {"lat":..,"lon":..,"tst":..} a /api/ubicacion?token=XXX
     * GPSLogger (custom URL): GET /api/ubicacion?token=XXX&lat=%LAT&lon=%LON&tst=%TIMESTAMP
     */
    public function apiGuardar(array $params): void
    {
        $token = trim((string)($_GET['token'] ?? ''));
        $repo = new UbicacionRepo();
        $row = $repo->usuarioPorToken($token);
        if (!$row) {
            Response::json(['ok' => false, 'error' => 'Token inválido.'], 401);
            return;
        }

        $body = [];
        $raw = file_get_contents('php://input');
        if (is_string($raw) && $raw !== '') {
            $decoded = json_decode($raw, true);
            if (is_array($decoded)) {
                $body = $decoded;
            }
        }

        $lat = $body['lat'] ?? $body['latitude'] ?? $_GET['lat'] ?? null;
        $lng = $body['lon'] ?? $body['lng'] ?? $body['longitude'] ?? $_GET['lon'] ?? $_GET['lng'] ?? null;
        $tst = $body['tst'] ?? $_GET['tst'] ?? null;
        $acc = $body['acc'] ?? $body['accuracy'] ?? $_GET['acc'] ?? null;
        $lat = is_numeric($lat) ? (float)$lat : 999;
        $lng = is_numeric($lng) ? (float)$lng : 999;
        if ($lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            Response::json(['ok' => false, 'error' => 'Coordenadas inválidas.']);
            return;
        }

        $fecha = null;
        if (is_numeric($tst) && (float)$tst > 0) {
            $ts = (float)$tst;
            if ($ts > 100000000000) {
                $ts = $ts / 1000;
            }
            if (abs(time() - $ts) < 86400) {
                $fecha = date('Y-m-d H:i:s', (int)$ts);
            }
        }

        $repo->guardarConFecha(
            (int)$row['admin_user_id'],
            $lat,
            $lng,
            $acc !== null && is_numeric($acc) ? max(0, (int)$acc) : null,
            $fecha
        );
        Response::json(['ok' => true]);
    }

    /** Visor de ubicaciones: solo superadmin. */
    public function index(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireRol('superadmin');

        $repo = new UbicacionRepo();
        echo View::adminPage('admin/ubicaciones/index.php', [
            'adminUser' => $adminUser,
            'ultimas' => $repo->ultimas(),
            'tokens' => $repo->listarTokens(),
            'usuarios' => (new \Perfushopping\Web\Repo\AdminUserRepo())->findAll(200),
            'csrf' => Csrf::token(),
            'pageTitle' => 'Ubicaciones del personal',
        ]);
    }

    public function crearToken(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $userId = (int)($_POST['admin_user_id'] ?? 0);
        $dispositivo = trim((string)($_POST['dispositivo'] ?? ''));
        if ($userId <= 0) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Elegí un usuario.'];
            Response::redirect('/admin/ubicaciones');
        }
        (new UbicacionRepo())->crearToken($userId, $dispositivo !== '' ? $dispositivo : 'Celular');
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Token creado. Configuralo en la app de tracking del celular.'];
        Response::redirect('/admin/ubicaciones');
    }

    public function revocarToken(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireRol('superadmin');
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            (new UbicacionRepo())->revocarToken($id);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Token revocado.'];
        }
        Response::redirect('/admin/ubicaciones');
    }

    public function data(array $params): void
    {
        $auth = new AdminAuthService();
        $auth->requireRol('superadmin');

        Response::json(['ok' => true, 'ultimas' => (new UbicacionRepo())->ultimas()]);
    }
}
