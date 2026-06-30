<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Infra\SmtpMailer;
use Perfushopping\Web\Repo\UserRepo;
use Perfushopping\Web\Repo\WholesaleRepo;
use Perfushopping\Web\Service\AuthService;
use Perfushopping\Web\Repo\AffiliateLedgerRepo;
use Perfushopping\Web\Repo\AffiliateWithdrawalRepo;
use Perfushopping\Web\Repo\DemoTechRepo;
use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Repo\CorreoRepo;
use Perfushopping\Web\Service\CorreoArgentinoService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class AdminController
{
    public function dashboard(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        echo View::page('admin/dashboard.php', ['user' => $u]);
    }

    public function correo(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        $savedAgencies = [];
        try {
            $savedAgencies = (new CorreoRepo())->listAgencies(null, null, null, null, 300);
        } catch (\Throwable $e) {
            $savedAgencies = [];
        }
        echo View::page('admin/correo.php', [
            'user' => $u,
            'csrf' => Csrf::token(),
            'authOk' => $_SESSION['correo_auth_ok'] ?? null,
            'agencies' => $_SESSION['correo_agencies'] ?? [],
            'agenciesSaved' => $_SESSION['correo_agencies_saved'] ?? null,
            'savedAgencies' => $savedAgencies,
            'savedFilters' => $_SESSION['correo_saved_filters'] ?? ['stateId' => '', 'cityName' => ''],
            'agencyFilters' => $_SESSION['correo_agency_filters'] ?? ['stateId' => '', 'pickup_availability' => '', 'package_reception' => ''],
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash'], $_SESSION['correo_auth_ok'], $_SESSION['correo_agencies'], $_SESSION['correo_agencies_saved'], $_SESSION['correo_saved_filters']);
    }

    public function correoAuth(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        try {
            (new CorreoArgentinoService())->auth();
            $_SESSION['correo_auth_ok'] = true;
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Credenciales de Correo Argentino validadas correctamente.'];
        } catch (\Throwable $e) {
            $_SESSION['correo_auth_ok'] = false;
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/correo');
    }

    public function correoAgencies(array $params): void
    {
        (new AuthService())->requireAdmin();
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
                if (!is_array($agency)) {
                    continue;
                }
                $repo->upsertAgency($agency);
                $saved++;
            }
            $_SESSION['correo_agencies'] = $agencies;
            $_SESSION['correo_agencies_saved'] = $saved;
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Sucursales consultadas: ' . count($agencies) . '. Guardadas/actualizadas: ' . $saved . '.'];
        } catch (\Throwable $e) {
            $_SESSION['correo_agencies'] = [];
            $_SESSION['correo_agencies_saved'] = 0;
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/correo');
    }

    public function correoSavedAgencies(array $params): void
    {
        (new AuthService())->requireAdmin();
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
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Sucursales en base: ' . count($list) . '.'];
        } catch (\Throwable $e) {
            $_SESSION['correo_agencies'] = [];
            $_SESSION['correo_agencies_saved'] = 0;
            $_SESSION['flash'] = ['type' => 'danger', 'text' => $e->getMessage()];
        }
        Response::redirect('/admin/correo');
    }

    public function orders(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        $q = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $repo = new OrderRepo();
        $orders = $repo->adminList($q, $status, ($q === '' && $status === '') ? 120 : 200);
        $items = $repo->itemsByOrderIds(array_map(static fn (array $row): int => (int)($row['id'] ?? 0), $orders));
        $itemsByOrder = [];
        foreach ($items as $item) {
            $orderId = (int)($item['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }
            if (!isset($itemsByOrder[$orderId])) {
                $itemsByOrder[$orderId] = [];
            }
            $itemsByOrder[$orderId][] = $item;
        }

        echo View::page('admin/orders.php', [
            'user' => $u,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'q' => $q,
            'status' => $status,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function prepare(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        $repo = new OrderRepo();
        $orders = $repo->adminList('', 'paid', 120);
        $transferOrders = $repo->adminList('', 'pending_transfer', 120);
        $orders = array_merge($orders, $transferOrders);
        usort($orders, static fn (array $a, array $b): int => strtotime((string)($b['created_at'] ?? '')) - strtotime((string)($a['created_at'] ?? '')));

        $items = $repo->itemsByOrderIds(array_map(static fn (array $row): int => (int)($row['id'] ?? 0), $orders));
        $itemsByOrder = [];
        foreach ($items as $item) {
            $orderId = (int)($item['order_id'] ?? 0);
            if ($orderId <= 0) {
                continue;
            }
            if (!isset($itemsByOrder[$orderId])) {
                $itemsByOrder[$orderId] = [];
            }
            $itemsByOrder[$orderId][] = $item;
        }

        echo View::page('admin/prepare.php', [
            'user' => $u,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['flash'] ?? null,
        ]);
        unset($_SESSION['flash']);
    }

    public function orderStatus(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim((string)($_POST['status'] ?? ''));
        $allowed = ['preparing', 'prepared', 'shipped', 'cancelled', 'archived'];
        if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Datos invalidos.'];
            Response::redirect('/admin/orders');
        }
        (new OrderRepo())->updateStatus($orderId, $newStatus);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Pedido #' . $orderId . ' actualizado a ' . $newStatus . '.'];
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/admin/orders');
    }

    public function recoverAbandoned(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);

        $carts = (new OrderRepo())->findAbandonedCarts();
        if (!$carts) {
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'No hay carritos abandonados para recuperar.'];
            Response::redirect('/admin/orders');
        }

        $sent = 0;
        $errors = 0;
        $appUrl = rtrim(Env::get('APP_URL', 'https://perfushopping.ar'), '/');

        foreach ($carts as $cart) {
            $email = (string)($cart['email'] ?? '');
            if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors++;
                continue;
            }

            $name = (string)($cart['ship_name'] ?? $cart['user_name'] ?? '');
            $code = (string)($cart['order_code'] ?? '');
            $total = (int)($cart['total_cents'] ?? 0);
            $totalFormatted = number_format($total / 100, 0, ',', '.');

            $html = '
<html><body style="margin:0;padding:0;background:#0c0b0a;font-family:sans-serif">
<div style="max-width:560px;margin:0 auto;padding:24px">
<div style="text-align:center;padding:20px 0">
<img src="' . $appUrl . '/assets/brand/logo-header.png" alt="Perfushopping" style="height:44px" />
</div>
<div style="background:rgba(255,255,255,0.06);border-radius:18px;padding:24px;color:#f6f4ef">
<h2 style="margin:0 0 12px;color:#d8b25a">' . htmlspecialchars($name ?: 'Hola') . ', tu carrito te espera!</h2>
<p style="line-height:1.6">Dejaste un pedido por <strong style="color:#f3d48a">$' . $totalFormatted . '</strong> sin confirmar. Todavia podes completarlo.</p>
<p style="line-height:1.6">Paga con tu tarjeta en cuotas sin interes a traves de Mercado Pago, o transferinos por alias <strong>perfushopping.mp</strong>.</p>
<p style="line-height:1.6">Ademas, si tu compra supera:</p>
<ul style="line-height:1.8">
<li><strong>$100.000</strong> → 50% de descuento en el envio</li>
<li><strong>$180.000</strong> → 75% de descuento en el envio</li>
<li><strong>$250.000</strong> → envio <strong>GRATIS</strong> a todo el pais</li>
</ul>
<p style="line-height:1.6">En Reconquista y Avellaneda (Santa Fe) el envio es gratis en horarios de reparto (12:00 y 19:30 hs).</p>
<div style="text-align:center;margin:24px 0">
<a href="' . $appUrl . '/checkout" style="display:inline-block;padding:14px 28px;border-radius:14px;background:linear-gradient(135deg,#d8b25a,#f3d48a);color:#17140f;font-weight:800;text-decoration:none">Finalizar mi compra</a>
</div>
<p style="color:rgba(246,244,239,0.5);font-size:12px;text-align:center">Pedido #' . htmlspecialchars($code) . ' — Si ya pagaste, ignora este email.</p>
</div>
</div>
</body></html>';

            $text = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</li>', '</ul>'], "\n", $html));

            try {
                (new SmtpMailer())->send($email, $name ? $name . ', recupera tu carrito en Perfushopping' : 'Recupera tu carrito en Perfushopping', $html, $text);
                $sent++;
            } catch (\Throwable $e) {
                $errors++;
                error_log('Recovery email failed for ' . $email . ': ' . $e->getMessage());
            }
        }

        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Emails de recuperacion enviados: ' . $sent . ' (errores: ' . $errors . ').'];
        Response::redirect('/admin/orders');
    }

    public function archiveAbandoned(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $count = (new OrderRepo())->archiveAbandonedCarts();
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Carritos abandonados archivados: ' . $count . '.'];
        Response::redirect('/admin/orders');
    }

    public function users(array $params): void
    {
        $u = (new AuthService())->requireAdmin();
        $q = trim((string)($_GET['q'] ?? ''));
        $list = (new UserRepo())->adminList($q, $q === '' ? 120 : 200);
        echo View::page('admin/users.php', ['user' => $u, 'list' => $list, 'q' => $q, 'customerCategories' => UserRepo::customerCategoryOptions(), 'csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
        unset($_SESSION['flash']);
    }

    public function userRoleSave(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $role = trim((string)($_POST['role'] ?? ''));
        $q = trim((string)($_POST['q'] ?? ''));
        $allowed = ['customer', 'admin'];
        if ($userId <= 0 || !in_array($role, $allowed, true)) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Datos invalidos para cambiar el rol.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        if ((int)($admin['id'] ?? 0) === $userId && $role !== 'admin') {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No podes quitarte el rol admin a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo->setRole($userId, $role);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Rol actualizado para ' . (string)($target['email'] ?? ('#' . $userId)) . '.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function userSave(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $email = trim((string)($_POST['email'] ?? ''));
        $name = trim((string)($_POST['name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $role = trim((string)($_POST['role'] ?? ''));
        $wholesaleStatus = trim((string)($_POST['wholesale_status'] ?? 'none'));
        $customerCategory = trim((string)($_POST['customer_category'] ?? 'none'));
        $q = trim((string)($_POST['q'] ?? ''));
        $allowed = ['customer', 'admin'];
        $allowedWholesale = ['none', 'pending', 'approved', 'rejected'];
        if ($userId <= 0 || $email === '' || $name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, $allowed, true) || !in_array($wholesaleStatus, $allowedWholesale, true) || !array_key_exists($customerCategory, UserRepo::customerCategoryOptions())) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Datos invalidos para guardar el usuario.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        if ((int)($admin['id'] ?? 0) === $userId && $role !== 'admin') {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No podes quitarte el rol admin a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        $existing = $repo->findByEmail($email);
        if ($existing && (int)($existing['id'] ?? 0) !== $userId) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Ese email ya esta registrado en otra cuenta.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo->adminUpdate($userId, $email, $name, $phone, $role, $wholesaleStatus, $customerCategory);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Usuario actualizado.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function userToggleBlock(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $q = trim((string)($_POST['q'] ?? ''));
        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        if ((int)($admin['id'] ?? 0) === $userId) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No podes bloquearte a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $disabled = empty($target['disabled_at']);
        $repo->setDisabled($userId, $disabled);
        $_SESSION['flash'] = ['type' => 'ok', 'text' => $disabled ? 'Usuario bloqueado.' : 'Usuario desbloqueado.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function userDelete(array $params): void
    {
        $admin = (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $q = trim((string)($_POST['q'] ?? ''));
        if ((int)($admin['id'] ?? 0) === $userId) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No podes eliminarte a vos mismo.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }
        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        try {
            $repo->deleteUser($userId);
            $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Usuario eliminado.'];
        } catch (\PDOException $e) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No se pudo eliminar el usuario porque tiene registros relacionados. Podes bloquearlo en su lugar.'];
        }
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
    }

    public function userPasswordReset(array $params): void
    {
        (new AuthService())->requireAdmin();
        Csrf::check($_POST['_csrf'] ?? null);
        $userId = (int)($_POST['user_id'] ?? 0);
        $password = (string)($_POST['new_password'] ?? '');
        $q = trim((string)($_POST['q'] ?? ''));
        if ($userId <= 0 || strlen($password) < 8) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'La nueva clave debe tener al menos 8 caracteres.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo = new UserRepo();
        $target = $repo->findById($userId);
        if (!$target) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Usuario no encontrado.'];
            Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
        }

        $repo->adminResetPassword($userId, password_hash($password, PASSWORD_DEFAULT));
        $_SESSION['flash'] = ['type' => 'ok', 'text' => 'Clave actualizada para ' . (string)($target['email'] ?? ('#' . $userId)) . '.'];
        Response::redirect('/admin/users' . ($q !== '' ? '?q=' . urlencode($q) : ''));
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
        echo View::page('admin/wholesale_list.php', ['user' => $u, 'list' => $list, 'customerCategories' => UserRepo::customerCategoryOptions(), 'csrf' => Csrf::token(), 'flash' => $_SESSION['flash'] ?? null]);
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
        (new UserRepo())->setCustomerCategory((int)$wr['user_id'], (string)($wr['customer_category'] ?? $wr['user_customer_category'] ?? 'none'));
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
