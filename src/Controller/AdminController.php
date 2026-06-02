<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Repo\WholesaleRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Repo\AffiliateLedgerRepo;
use Perfushopping\Web\Repo\AffiliateWithdrawalRepo;
use Perfushopping\Web\Repo\DemoTechRepo;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class AdminController
{
    public function dashboard(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        echo View::page('admin/dashboard.php', ['user' => $u]);
    }

    public function demoTech(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        $list = [];
        try {
            $list = (new DemoTechRepo())->listRecentRegistrations(250);
        } catch (\Throwable $e) {
            $list = [];
        }
        echo View::page('admin/demo_tech.php', ['user' => $u, 'list' => $list, 'csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function demoTechEvents(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
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
        echo View::page('admin/demo_tech_events.php', ['user' => $u, 'events' => $events, 'csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function demoTechEventSave(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
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
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Fecha invalida.'];
            Response::redirect('/admin/demo-tecnica/horarios');
        }
        if ((int)$dt->format('N') !== 1) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'La fecha debe ser lunes.'];
            Response::redirect('/admin/demo-tecnica/horarios');
        }
        $today = new \DateTimeImmutable('today');
        if ($dt < $today) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No se permiten fechas pasadas.'];
            Response::redirect('/admin/demo-tecnica/horarios');
        }
        if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end)) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Hora invalida (HH:MM).'];
            Response::redirect('/admin/demo-tecnica/horarios');
        }
        if ($venue === '') {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Completa la sede.'];
            Response::redirect('/admin/demo-tecnica/horarios');
        }
        if ($cap < 1 || $cap > 200) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Cupo invalido.'];
            Response::redirect('/admin/demo-tecnica/horarios');
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
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Horario guardado.'];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/demo-tecnica/horarios');
    }

    public function demoTechStatus(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $status = trim((string)($_POST['status'] ?? ''));
        try {
            (new DemoTechRepo())->setStatus($id, $status);
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Actualizado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/demo-tecnica');
    }

    public function affiliateRelease(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $n = 0;
        try {
            $n = (new AffiliateLedgerRepo())->releaseDueCommissions();
        } catch (\Throwable $e) {
            $n = 0;
        }
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Comisiones liberadas: ' . $n];
        Response::redirect('/admin');
    }

    public function withdrawals(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        $list = [];
        try {
            $list = (new AffiliateWithdrawalRepo())->listRequested();
        } catch (\Throwable $e) {
            $list = [];
        }
        echo View::page('admin/withdrawals.php', ['user' => $u, 'list' => $list, 'csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function withdrawalsApprove(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        try {
            (new AffiliateWithdrawalRepo())->setStatus($id, 'approved');
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Retiro aprobado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/withdrawals');
    }

    public function withdrawalsPaid(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        try {
            (new AffiliateWithdrawalRepo())->setStatus($id, 'paid');
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Retiro marcado como pagado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/withdrawals');
    }

    public function withdrawalsReject(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $reason = trim((string)($_POST['reason'] ?? ''));
        try {
            (new AffiliateWithdrawalRepo())->rejectAndRefund($id, (int)$admin['id'], $reason);
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Retiro rechazado y reintegrado #' . $id];
        } catch (\Throwable $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/withdrawals');
    }

    public function wholesaleList(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        $list = (new WholesaleRepo())->pendingList();
        echo View::page('admin/wholesale_list.php', ['user' => $u, 'list' => $list, 'csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function wholesaleApprove(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $wr = (new WholesaleRepo())->find($id);
        if (!$wr) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Solicitud no encontrada.'];
            Response::redirect('/admin/wholesale');
        }

        // Upsert into clientes by CUIT/mail (best-effort)
        $clienteId = $this->upsertCliente((string)$wr['cuit'], (string)$wr['email'], (string)$wr['razon_social'], (string)$wr['address'], (string)$wr['phone'], (string)$wr['postal_code']);

        (new WholesaleRepo())->decide($id, (int)$admin['id'], 'approved', trim((string)($_POST['notes'] ?? '')) ?: null);
        (new UserRepo())->setWholesaleStatus((int)$wr['user_id'], 'approved', $clienteId);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Mayorista aprobado.'];
        Response::redirect('/admin/wholesale');
    }

    public function wholesaleReject(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $id = (int)($_POST['id'] ?? 0);
        $wr = (new WholesaleRepo())->find($id);
        if (!$wr) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Solicitud no encontrada.'];
            Response::redirect('/admin/wholesale');
        }
        (new WholesaleRepo())->decide($id, (int)$admin['id'], 'rejected', trim((string)($_POST['notes'] ?? '')) ?: null);
        (new UserRepo())->setWholesaleStatus((int)$wr['user_id'], 'rejected', (int)($wr['cliente_id'] ?? 0) ?: null);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Solicitud rechazada.'];
        Response::redirect('/admin/wholesale');
    }

    private function upsertCliente(string $cuit, string $mail, string $razon, string $direc, string $tele, string $codpost): ?int
    {
        $pdo = Db::pdo();
        $cuitNum = preg_replace('/[^0-9]/', '', $cuit) ?? '';
        $mail = trim($mail);

        // Try by CUIT
        if ($cuitNum !== '') {
            $st = $pdo->prepare('SELECT idclien FROM clientes WHERE cuit=:c LIMIT 1');
            $st->execute([':c' => $cuitNum]);
            $id = $st->fetchColumn();
            if ($id !== false) {
                $upd = $pdo->prepare('UPDATE clientes SET razon=:r, direc=:d, tele=:t, codpost=:cp, mail=:m, activo=1 WHERE idclien=:i');
                $upd->execute([':r' => $razon, ':d' => $direc, ':t' => $tele, ':cp' => $codpost, ':m' => $mail, ':i' => (int)$id]);
                return (int)$id;
            }
        }

        // Try by mail
        if ($mail !== '') {
            $st = $pdo->prepare('SELECT idclien FROM clientes WHERE mail=:m LIMIT 1');
            $st->execute([':m' => $mail]);
            $id = $st->fetchColumn();
            if ($id !== false) {
                $upd = $pdo->prepare('UPDATE clientes SET razon=:r, direc=:d, tele=:t, codpost=:cp, activo=1 WHERE idclien=:i');
                $upd->execute([':r' => $razon, ':d' => $direc, ':t' => $tele, ':cp' => $codpost, ':i' => (int)$id]);
                return (int)$id;
            }
        }

        // Insert minimal
        $ins = $pdo->prepare('INSERT INTO clientes (razon,cuit,direc,tele,codpost,mail,activo,fealta) VALUES (:r,:c,:d,:t,:cp,:m,1,CURDATE())');
        $ins->execute([
            ':r' => $razon,
            ':c' => $cuitNum !== '' ? $cuitNum : 0,
            ':d' => $direc,
            ':t' => $tele,
            ':cp' => $codpost,
            ':m' => $mail,
        ]);
        return (int)$pdo->lastInsertId();
    }
}
