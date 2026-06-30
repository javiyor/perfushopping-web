<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\CorreoRepo;
use Perfushopping\Web\Repo\MpRepo;
use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Service\AffiliateService;
use Perfushopping\Web\Service\CorreoArgentinoService;
use Perfushopping\Web\Service\MercadoPagoService;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class MercadoPagoController
{
    public function start(array $params): void
    {
        // Allow GET start after checkout.
        $this->doCreatePreference();
    }

    public function createPreference(array $params): void
    {
        $this->doCreatePreference();
    }

    private function doCreatePreference(): void
    {
        // This endpoint is called after checkout submit created an order.
        $mp = $_SESSION['mp_checkout'] ?? null;
        if (!is_array($mp) || !isset($mp['order_id'], $mp['order_code'], $mp['total_cents'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No hay checkout iniciado. Completa el checkout.'];
            Response::redirect('/checkout');
        }

        $order = (new OrderRepo())->find((int)$mp['order_id']);
        if (!$order) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Pedido no encontrado.'];
            Response::redirect('/');
        }

        $appUrl = rtrim(Env::get('APP_URL', 'https://perfushopping.ar'), '/');
        $notificationUrl = $appUrl . '/mp/webhook';

        $preference = [
            'external_reference' => (string)$order['order_code'],
            'items' => [
                [
                    'title' => 'Compra Perfushopping ' . (string)$order['order_code'],
                    'quantity' => 1,
                    'unit_price' => ((int)$mp['total_cents']) / 100,
                    'currency_id' => 'ARS',
                ],
            ],
            'payer' => [
                'email' => (string)$order['email'],
            ],
            'back_urls' => [
                'success' => $appUrl . '/pay/mp/success?order=' . urlencode((string)$order['order_code']),
                'pending' => $appUrl . '/pay/mp/pending?order=' . urlencode((string)$order['order_code']),
                'failure' => $appUrl . '/pay/mp/failure?order=' . urlencode((string)$order['order_code']),
            ],
            'auto_return' => 'approved',
            'notification_url' => $notificationUrl,
        ];

        $res = (new MercadoPagoService())->createPreference($preference);
        $prefId = (string)($res['id'] ?? '');
        $initPoint = (string)($res['init_point'] ?? '');
        if ($prefId !== '') {
            (new MpRepo())->upsertPreference((int)$order['id'], $prefId);
        }
        unset($_SESSION['mp_checkout']);

        if ($initPoint === '') {
            throw new \RuntimeException('Mercado Pago no devolvio init_point.');
        }
        Response::redirect($initPoint);
    }

    public function success(array $params): void
    {
        echo View::page('pay_result.php', ['title' => 'Pago exitoso', 'mode' => 'success']);
    }

    public function pending(array $params): void
    {
        echo View::page('pay_result.php', ['title' => 'Pago pendiente', 'mode' => 'pending']);
    }

    public function failure(array $params): void
    {
        echo View::page('pay_result.php', ['title' => 'Pago rechazado', 'mode' => 'failure']);
    }

    public function webhook(array $params): void
    {
        $raw = file_get_contents('php://input');
        if ($raw === false) {
            Response::json(['ok' => false], 400);
            return;
        }
        $topic = $_GET['topic'] ?? ($_GET['type'] ?? null);
        $eventKey = hash('sha256', ($topic ?? '') . '|' . ($raw));
        $stored = (new MpRepo())->storeWebhook($eventKey, is_string($topic) ? $topic : null, $raw);
        if (!$stored) {
            Response::json(['ok' => true, 'dup' => true], 200);
            return;
        }

        $json = json_decode($raw, true);
        $paymentId = null;
        if (is_array($json)) {
            if (isset($json['data']['id'])) {
                $paymentId = (int)$json['data']['id'];
            } elseif (isset($json['id'])) {
                $paymentId = (int)$json['id'];
            }
        }
        if (!$paymentId) {
            Response::json(['ok' => true], 200);
            return;
        }

        // Fetch payment details
        $payment = (new MercadoPagoService())->fetchPayment($paymentId);
        $ext = (string)($payment['external_reference'] ?? '');
        if ($ext === '') {
            Response::json(['ok' => true], 200);
            return;
        }
        $order = (new OrderRepo())->findByCode($ext);
        if (!$order) {
            Response::json(['ok' => true], 200);
            return;
        }

        $status = (string)($payment['status'] ?? '');
        $detail = (string)($payment['status_detail'] ?? '');
        (new MpRepo())->upsertPayment((int)$order['id'], $paymentId, $status, $detail, json_encode($payment, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($status === 'approved') {
            (new OrderRepo())->updateStatus((int)$order['id'], 'paid');
            // Create affiliate commission (pending 7 days)
            $orderPaid = (new OrderRepo())->find((int)$order['id']);
            if ($orderPaid) {
                (new AffiliateService())->maybeCreateCommissionForPaidOrder($orderPaid);

                // Create Correo Argentino order if shipping method is correo_argentino
                if (($orderPaid['shipping_method'] ?? '') === 'correo_argentino') {
                    try {
                        $correoPayload = self::buildCorreoPayload($orderPaid);
                        $correoResp = (new CorreoArgentinoService())->createOrder($correoPayload);
                        $operation = (string)($correoResp['operation'] ?? $correoResp['id'] ?? '');
                        $tracking = (string)($correoResp['tracking'] ?? $correoResp['tracking_number'] ?? '');
                        if ($operation !== '') {
                            (new OrderRepo())->updateCorreoData((int)$orderPaid['id'], $operation, $tracking);
                        }
                    } catch (\Throwable $e) {
                        error_log('CorreoArgentino createOrder error: ' . $e->getMessage());
                    }
                }
            }
        } elseif (in_array($status, ['rejected', 'cancelled'], true)) {
            (new OrderRepo())->updateStatus((int)$order['id'], 'cancelled');
        }

        Response::json(['ok' => true], 200);
    }

    /** @param array<string,mixed> $order
     *  @return array<string,mixed>
     */
    private static function buildCorreoPayload(array $order): array
    {
        $recipient = [
            'name' => (string)($order['ship_name'] ?? ''),
            'phone' => (string)($order['phone'] ?? ''),
            'email' => (string)($order['email'] ?? ''),
        ];

        $destination = [
            'street' => (string)($order['ship_address'] ?? ''),
            'city' => (string)($order['ship_city'] ?? ''),
            'state' => (string)($order['ship_province_name'] ?? ''),
            'zip' => (string)($order['ship_postal_code'] ?? ''),
        ];

        $packages = [
            [
                'weight' => 1.0,
                'content' => 'Productos de perfumeria',
            ],
        ];

        $provinceCodprov = (int)($order['ship_province_codprov'] ?? 0);
        $district = null;
        if ($provinceCodprov > 0) {
            $district = self::provinceToCorreoStateId($provinceCodprov);
        }

        if ($district !== null) {
            $destination['state_id'] = $district;
        }

        return [
            'reference' => (string)($order['order_code'] ?? ''),
            'recipient' => $recipient,
            'destination' => $destination,
            'packages' => $packages,
            'service_type' => 'PAQAR',
        ];
    }

    private static function provinceToCorreoStateId(int $codprov): ?string
    {
        return match ($codprov) {
            1 => 'B',
            2 => 'C',
            3 => 'S',
            4 => 'X',
            5 => 'W',
            6 => 'H',
            7 => 'P',
            8 => 'E',
            9 => 'G',
            10 => 'N',
            11 => 'L',
            12 => 'D',
            13 => 'A',
            14 => 'Y',
            15 => 'Z',
            16 => 'F',
            17 => 'K',
            18 => 'Q',
            19 => 'R',
            20 => 'V',
            21 => 'U',
            22 => 'M',
            23 => 'T',
            24 => 'J',
            default => null,
        };
    }
}
