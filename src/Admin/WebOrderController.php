<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Infra\SmtpMailer;
use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class WebOrderController
{
    private AdminAuthService $auth;

    public function __construct()
    {
        $this->auth = new AdminAuthService();
    }

    public function index(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $q = trim((string)($_GET['q'] ?? ''));
        $status = trim((string)($_GET['status'] ?? ''));
        $repo = new OrderRepo();
        $orders = $repo->adminList($q, $status, ($q === '' && $status === '') ? 120 : 200);
        $items = $repo->itemsByOrderIds(array_map(static fn (array $row): int => (int)($row['id'] ?? 0), $orders));
        $itemsByOrder = [];
        foreach ($items as $item) {
            $orderId = (int)($item['order_id'] ?? 0);
            if ($orderId <= 0) continue;
            if (!isset($itemsByOrder[$orderId])) $itemsByOrder[$orderId] = [];
            $itemsByOrder[$orderId][] = $item;
        }

        echo View::adminPage('admin/orders.php', [
            'adminUser' => $adminUser,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'q' => $q,
            'status' => $status,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function prepare(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        $repo = new OrderRepo();
        $orders = $repo->adminList('', 'paid', 120);
        $transferOrders = $repo->adminList('', 'pending_transfer', 120);
        $orders = array_merge($orders, $transferOrders);
        usort($orders, static fn (array $a, array $b): int => strtotime((string)($b['created_at'] ?? '')) - strtotime((string)($a['created_at'] ?? '')));

        $items = $repo->itemsByOrderIds(array_map(static fn (array $row): int => (int)($row['id'] ?? 0), $orders));
        $itemsByOrder = [];
        foreach ($items as $item) {
            $orderId = (int)($item['order_id'] ?? 0);
            if ($orderId <= 0) continue;
            if (!isset($itemsByOrder[$orderId])) $itemsByOrder[$orderId] = [];
            $itemsByOrder[$orderId][] = $item;
        }

        echo View::adminPage('admin/prepare.php', [
            'adminUser' => $adminUser,
            'orders' => $orders,
            'itemsByOrder' => $itemsByOrder,
            'csrf' => Csrf::token(),
            'flash' => $_SESSION['admin_flash'] ?? null,
        ]);
        unset($_SESSION['admin_flash']);
    }

    public function status(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $orderId = (int)($_POST['order_id'] ?? 0);
        $newStatus = trim((string)($_POST['status'] ?? ''));

        $order = $newStatus !== '' ? (new OrderRepo())->find($orderId) : null;
        if (!$order) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Pedido no encontrado.'];
            Response::redirect('/admin/orders');
        }

        $transitions = [
            'pending_payment' => ['paid', 'cancelled'],
            'paid' => ['preparing', 'cancelled'],
            'pending_transfer' => ['paid', 'preparing', 'cancelled'],
            'transfer_reported' => ['paid', 'cancelled'],
            'preparing' => ['prepared', 'cancelled'],
            'prepared' => ['shipped', 'cancelled'],
            'shipped' => ['archived'],
            'cancelled' => ['archived'],
            'archived' => [],
        ];

        $currentStatus = (string)($order['status'] ?? '');
        $allowed = $transitions[$currentStatus] ?? [];
        if ($orderId <= 0 || !in_array($newStatus, $allowed, true)) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Transicion invalida.'];
            Response::redirect('/admin/orders');
        }
        (new OrderRepo())->updateStatus($orderId, $newStatus);
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Pedido #' . $orderId . ' actualizado a ' . $newStatus . '.'];
        Response::redirect($_SERVER['HTTP_REFERER'] ?? '/admin/orders');
    }

    public function recoverAbandoned(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);

        $carts = (new OrderRepo())->findAbandonedCarts();
        if (!$carts) {
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'No hay carritos abandonados para recuperar.'];
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
<li><strong>$100.000</strong> &rarr; 50% de descuento en el envio</li>
<li><strong>$180.000</strong> &rarr; 75% de descuento en el envio</li>
<li><strong>$250.000</strong> &rarr; envio <strong>GRATIS</strong> a todo el pais</li>
</ul>
<p style="line-height:1.6">En Reconquista y Avellaneda (Santa Fe) el envio es gratis en horarios de reparto (12:00 y 19:30 hs).</p>
<div style="text-align:center;margin:24px 0">
<a href="' . $appUrl . '/checkout" style="display:inline-block;padding:14px 28px;border-radius:14px;background:linear-gradient(135deg,#d8b25a,#f3d48a);color:#17140f;font-weight:800;text-decoration:none">Finalizar mi compra</a>
</div>
<p style="color:rgba(246,244,239,0.5);font-size:12px;text-align:center">Pedido #' . htmlspecialchars($code) . ' &mdash; Si ya pagaste, ignora este email.</p>
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

        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Emails de recuperacion enviados: ' . $sent . ' (errores: ' . $errors . ').'];
        Response::redirect('/admin/orders');
    }

    public function archiveAbandoned(array $params): void
    {
        $adminUser = $this->auth->requireLogin();
        Csrf::check($_POST['_csrf'] ?? null);
        $count = (new OrderRepo())->archiveAbandonedCarts();
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Carritos abandonados archivados: ' . $count . '.'];
        Response::redirect('/admin/orders');
    }
}
