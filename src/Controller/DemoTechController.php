<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\DemoTechRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class DemoTechController
{
    public function index(array $params): void
    {
        $this->renderPage(null);
    }

    public function professionalsForm(array $params): void
    {
        $this->renderPage('pro');
    }

    public function clientsForm(array $params): void
    {
        $this->renderPage('client');
    }

    public function professionalsSubmit(array $params): void
    {
        $this->submitRegistration('pro', '/eventos/capacitaciones/profesionales');
    }

    public function clientsSubmit(array $params): void
    {
        $this->submitRegistration('client', '/eventos/capacitaciones/clientes');
    }

    private function renderPage(?string $mode): void
    {
        $auth = new AuthService();
        $u = $auth->user();
        $isWholesale = $auth->isWholesaleApproved($u);
        $repo = new DemoTechRepo();
        $events = $repo->listUpcomingEvents();

        // add remaining seats per event
        foreach ($events as $k => $ev) {
            $id = (int)($ev['id'] ?? 0);
            $events[$k]['remaining'] = $id > 0 ? $repo->remainingSeats($id) : 0;
        }
        echo View::page('demo_tech.php', [
            'user' => $u,
            'isWholesale' => $isWholesale,
            'csrf' => Csrf::token(),
            'events' => $events,
            'mode' => $mode,
        ]);
    }

    private function submitRegistration(string $kind, string $redirectPath): void
    {
        Csrf::check($_POST['_csrf'] ?? null);

        // Basic honeypot
        $trap = trim((string)($_POST['website'] ?? ''));
        if ($trap !== '') {
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Registro recibido.'];
            Response::redirect($redirectPath);
        }

        $postedKind = (string)($_POST['kind'] ?? '');
        if ($postedKind !== $kind) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Tipo de registro invalido.'];
            Response::redirect($redirectPath);
        }

        $name = trim((string)($_POST['name'] ?? ''));
        $email = trim((string)($_POST['email'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $province = trim((string)($_POST['province'] ?? ''));
        $salonName = trim((string)($_POST['salon_name'] ?? ''));
        $salonAddress = trim((string)($_POST['salon_address'] ?? ''));
        $eventId = (int)($_POST['event_id'] ?? 0);
        $attendees = trim((string)($_POST['attendees'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($name === '') {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Completa tu nombre.'];
            Response::redirect($redirectPath);
        }
        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Email invalido.'];
            Response::redirect($redirectPath);
        }
        if ($eventId <= 0) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Elegí un horario disponible.'];
            Response::redirect($redirectPath);
        }

        $repo = new DemoTechRepo();

        $att = null;
        if ($attendees !== '') {
            $attN = (int)$attendees;
            if ($attN < 1 || $attN > 60) {
                $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Cantidad de asistentes invalida.'];
                Response::redirect($redirectPath);
            }
            $att = $attN;
        }

        // Do not allow past event dates (defense-in-depth)
        try {
            $ev = $repo->findEvent($eventId);
            if (!$ev) {
                $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Evento no disponible.'];
                Response::redirect($redirectPath);
            }
            $dtEv = \DateTimeImmutable::createFromFormat('Y-m-d', (string)($ev['monday_date'] ?? ''));
            $today = new \DateTimeImmutable('today');
            if (!$dtEv || $dtEv < $today) {
                $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No se permiten fechas pasadas.'];
                Response::redirect($redirectPath);
            }
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Evento no disponible.'];
            Response::redirect($redirectPath);
        }

        if ($kind === 'pro' && $salonName === '') {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Profesionales: completa el nombre del salon/peluqueria.'];
            Response::redirect($redirectPath);
        }

        try {
            $id = $repo->createRegistration([
            'kind' => $kind,
            'name' => mb_substr($name, 0, 120),
            'email' => $email !== '' ? mb_substr($email, 0, 190) : null,
            'phone' => $phone !== '' ? mb_substr($phone, 0, 40) : null,
            'city' => $city !== '' ? mb_substr($city, 0, 120) : null,
            'province' => $province !== '' ? mb_substr($province, 0, 80) : null,
            'salon_name' => $salonName !== '' ? mb_substr($salonName, 0, 160) : null,
            'salon_address' => $salonAddress !== '' ? mb_substr($salonAddress, 0, 190) : null,
            'event_id' => $eventId,
            'attendees' => $att,
            'notes' => $notes !== '' ? mb_substr($notes, 0, 600) : null,
            'created_at' => (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
            'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? mb_substr((string)$_SERVER['HTTP_USER_AGENT'], 0, 255) : null,
            ]);
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
            Response::redirect($redirectPath);
        }

        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Registro recibido. ID #' . $id . ' (te contactamos para coordinar).'];
        Response::redirect($redirectPath);
    }
}
