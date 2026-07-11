<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\DemoTechRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class CapacitacionController
{
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $list = [];
        try {
            $list = (new DemoTechRepo())->listRecentRegistrations(250);
        } catch (\Throwable $e) {
            $list = [];
        }
        echo View::adminPage('admin/capacitaciones/registros.php', [
            'adminUser' => $adminUser,
            'list' => $list,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function horarios(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $repo = new DemoTechRepo();
        $events = [];
        try {
            $events = $repo->listEvents(250);
            foreach ($events as $k => $ev) {
                $id = (int)($ev['id'] ?? 0);
                $events[$k]['remaining'] = $id > 0 ? $repo->remainingSeats($id) : 0;
            }
        } catch (\Throwable $e) {
            $events = [];
        }
        echo View::adminPage('admin/capacitaciones/horarios.php', [
            'adminUser' => $adminUser,
            'events' => $events,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function horariosSave(array $params): void
    {
        $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);

        $id = (int)($_POST['id'] ?? 0);
        $mondayDate = trim((string)($_POST['monday_date'] ?? ''));
        $start = trim((string)($_POST['start_time'] ?? ''));
        $end = trim((string)($_POST['end_time'] ?? ''));
        $venue = trim((string)($_POST['venue_name'] ?? ''));
        $addr = trim((string)($_POST['venue_address'] ?? ''));
        $cap = (int)($_POST['capacity'] ?? 0);
        $notes = trim((string)($_POST['notes'] ?? ''));
        $active = isset($_POST['active']) ? 1 : 0;

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $mondayDate);
        if (!$dt) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Fecha invalida.'];
            Response::redirect('/admin/capacitaciones/horarios');
        }
        if ((int)$dt->format('N') !== 1) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'La fecha debe ser lunes.'];
            Response::redirect('/admin/capacitaciones/horarios');
        }
        $today = new \DateTimeImmutable('today');
        if ($dt < $today) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se permiten fechas pasadas.'];
            Response::redirect('/admin/capacitaciones/horarios');
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Hora invalida (HH:MM).'];
            Response::redirect('/admin/capacitaciones/horarios');
        }
        if ($venue === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Completa la sede.'];
            Response::redirect('/admin/capacitaciones/horarios');
        }
        if ($cap < 1 || $cap > 200) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Cupo invalido.'];
            Response::redirect('/admin/capacitaciones/horarios');
        }

        try {
            (new DemoTechRepo())->saveEvent([
                'id' => $id > 0 ? $id : 0,
                'monday_date' => $dt->format('Y-m-d'),
                'start_time' => $start . ':00',
                'end_time' => $end . ':00',
                'venue_name' => mb_substr($venue, 0, 160),
                'venue_address' => $addr !== '' ? mb_substr($addr, 0, 190) : null,
                'capacity' => $cap,
                'notes' => $notes !== '' ? mb_substr($notes, 0, 400) : null,
                'active' => $active,
            ]);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Horario guardado.'];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/capacitaciones/horarios');
    }

    public function status(array $params): void
    {
        $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        try {
            (new DemoTechRepo())->setStatus($id, $status);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Actualizado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/capacitaciones');
    }
}
