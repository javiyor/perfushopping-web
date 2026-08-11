<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\NaveRepo;
use Perfushopping\Web\Repo\OrderRepo;
use Perfushopping\Web\Service\AffiliateService;
use Perfushopping\Web\Service\CorreoArgentinoService;
use Perfushopping\Web\Service\NaveService;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class NaveController
{
    public function start(array $params): void
    {
        $checkout = $_SESSION['nave_checkout'] ?? null;
        if (!is_array($checkout) || !isset($checkout['order_id'], $checkout['order_code'], $checkout['total_cents'])) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'No hay checkout iniciado. Completa el checkout.'];
            Response::redirect('/checkout');
        }

        $order = (new OrderRepo())->find((int)$checkout['order_id']);
        if (!$order) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'Pedido no encontrado.'];
            Response::redirect('/');
        }

        $nave = new NaveService();
        if (!$nave->configured()) {
            $_SESSION['flash'] = ['type' => 'danger', 'text' => 'El pago con Nave no esta disponible por el momento. Elegi otro metodo.'];
            Response::redirect('/checkout');
        }

        $returnToken = bin2hex(random_bytes(16));
        $webhookSecret = bin2hex(random_bytes(16));
        $payload = $this->buildPayload($order, (int)$checkout['total_cents'], $returnToken, $webhookSecret);

        $res = $nave->createPaymentRequest($payload);
        $paymentRequestId = (string)($res['id'] ?? '');
        $checkoutUrl = (string)($res['checkout_url'] ?? '');

        (new NaveRepo())->store((int)$order['id'], $paymentRequestId, $returnToken, $webhookSecret);
        unset($_SESSION['nave_checkout']);

        if ($checkoutUrl === '') {
            throw new \RuntimeException('Nave no devolvio checkout_url.');
        }
        Response::redirect($checkoutUrl);
    }

    public function success(array $params): void
    {
        echo View::page('pay_result.php', ['title' => 'Pago exitoso', 'mode' => 'success', 'provider' => 'Nave']);
    }

    public function pending(array $params): void
    {
        echo View::page('pay_result.php', ['title' => 'Pago pendiente', 'mode' => 'pending', 'provider' => 'Nave']);
    }

    public function failure(array $params): void
    {
        echo View::page('pay_result.php', ['title' => 'Pago rechazado', 'mode' => 'failure', 'provider' => 'Nave']);
    }

    public function webhook(array $params): void
    {
        $raw = file_get_contents('php://input');
        if ($raw === false) {
            Response::json(['ok' => false], 400);
            return;
        }

        $json = json_decode($raw, true);
        $prId = null;
        if (is_array($json)) {
            $v = $json['payment_request_id'] ?? $json['data']['payment_request_id'] ?? $json['payment_request']['id'] ?? null;
            if (is_string($v) && $v !== '') {
                $prId = $v;
            }
        }
        $stored = (new NaveRepo())->storeWebhook(hash('sha256', $raw), $prId, $raw);
        if (!$stored) {
            Response::json(['ok' => true, 'dup' => true], 200);
            return;
        }

        if (!is_array($json)) {
            Response::json(['ok' => true], 200);
            return;
        }

        $payment = $this->resolvePayment($json);
        if ($payment === null) {
            $payment = $json;
        }

        $order = $this->resolveOrder($payment, $json);
        if (!$order) {
            Response::json(['ok' => true], 200);
            return;
        }

        $paymentId = $payment['id'] ?? $json['data']['id'] ?? $json['id'] ?? null;
        $statusRaw = (string)($payment['status'] ?? $payment['status_detail'] ?? $json['status'] ?? $json['event'] ?? '');
        $status = $this->mapStatus($statusRaw);
        $code = (string)($payment['payment_code'] ?? $payment['code'] ?? $payment['external_reference'] ?? '');

        (new NaveRepo())->updatePayment(
            (int)$order['id'],
            is_scalar($paymentId) ? (string)$paymentId : '',
            $status,
            $code,
            json_encode($json, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );

        if ($status === 'paid') {
            (new OrderRepo())->updateStatus((int)$order['id'], 'paid');
            $orderPaid = (new OrderRepo())->find((int)$order['id']);
            if ($orderPaid) {
                (new AffiliateService())->maybeCreateCommissionForPaidOrder($orderPaid);

                // Loyalty points: accrue on paid order
                try {
                    (new \Perfushopping\Web\Service\PuntosService())->acreditarOrder($orderPaid);
                } catch (\Throwable $e) {
                    error_log('Puntos acreditar error: ' . $e->getMessage());
                }

                if (($orderPaid['shipping_method'] ?? '') === 'correo_argentino') {
                    try {
                        $correoResp = (new CorreoArgentinoService())->createOrder($this->buildCorreoPayload($orderPaid));
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
        } elseif ($status === 'cancelled') {
            (new OrderRepo())->updateStatus((int)$order['id'], 'cancelled');
        }

        Response::json(['ok' => true], 200);
    }

    /** @param array<string,mixed> $order
     *  @return array<string,mixed>
     */
    private function buildPayload(array $order, int $totalCents, string $returnToken, string $webhookSecret): array
    {
        $appUrl = rtrim(Env::get('APP_URL', 'https://perfushopping.ar'), '/');
        $orderCode = (string)$order['order_code'];

        return [
            'external_reference' => $orderCode,
            'amount' => $totalCents,
            'currency' => 'ARS',
            'description' => 'Compra Perfushopping ' . $orderCode,
            'return_url' => $appUrl . '/pay/nave/success?order=' . urlencode($orderCode) . '&token=' . $returnToken,
            'webhook_url' => $appUrl . '/nave/webhook?secret=' . $webhookSecret,
            'pos_id' => Env::get('NAVE_POS_ID', ''),
            'buyer' => [
                'name' => (string)($order['ship_name'] ?? ''),
                'email' => (string)($order['email'] ?? ''),
                'phone' => (string)($order['phone'] ?? ''),
            ],
            'metadata' => [
                'order_id' => (int)$order['id'],
                'order_code' => $orderCode,
            ],
        ];
    }

    /** @param array<string,mixed> $json
     *  @return array<string,mixed>|null
     */
    private function resolvePayment(array $json): ?array
    {
        $nave = new NaveService();
        if (!$nave->configured()) {
            return null;
        }
        $paymentId = $json['data']['id'] ?? $json['payment']['id'] ?? $json['id'] ?? $json['payment_id'] ?? null;
        $paymentReqId = $json['payment_request_id'] ?? $json['data']['payment_request_id'] ?? $json['payment_request']['id'] ?? null;

        if (is_scalar($paymentId) && (string)$paymentId !== '') {
            try {
                return $nave->getPayment((string)$paymentId);
            } catch (\Throwable $e) {
                error_log('Nave getPayment error: ' . $e->getMessage());
            }
        }
        if (is_scalar($paymentReqId) && (string)$paymentReqId !== '') {
            try {
                $pr = $nave->getPaymentRequest((string)$paymentReqId);
                return is_array($pr) ? $pr : null;
            } catch (\Throwable $e) {
                error_log('Nave getPaymentRequest error: ' . $e->getMessage());
            }
        }
        return null;
    }

    /** @param array<string,mixed> $payment
     *  @param array<string,mixed> $json
     *  @return array<string,mixed>|null
     */
    private function resolveOrder(array $payment, array $json): ?array
    {
        $code = (string)($payment['external_reference'] ?? $payment['order_reference'] ?? $payment['reference'] ?? $json['external_reference'] ?? '');
        $code = trim($code);
        if ($code !== '') {
            $order = (new OrderRepo())->findByCode($code);
            if ($order) {
                return $order;
            }
        }
        $prId = $payment['payment_request_id'] ?? $payment['payment_request']['id'] ?? $json['payment_request_id'] ?? null;
        if (is_scalar($prId) && (string)$prId !== '') {
            $row = (new NaveRepo())->findByPaymentRequest((string)$prId);
            if ($row) {
                return (new OrderRepo())->find((int)$row['order_id']) ?: null;
            }
        }
        return null;
    }

    private function mapStatus(string $raw): string
    {
        $raw = strtolower(trim($raw));
        if (in_array($raw, ['approved', 'paid', 'succeeded', 'success', 'captured', 'confirmed', 'completed'], true)) {
            return 'paid';
        }
        if (in_array($raw, ['rejected', 'cancelled', 'canceled', 'failed', 'declined', 'error', 'reversed', 'refunded'], true)) {
            return 'cancelled';
        }
        return 'pending';
    }

    /** @param array<string,mixed> $order
     *  @return array<string,mixed>
     */
    private static function buildCorreoPayload(array $order): array
    {
        $destination = [
            'street' => (string)($order['ship_address'] ?? ''),
            'city' => (string)($order['ship_city'] ?? ''),
            'state' => (string)($order['ship_province_name'] ?? ''),
            'zip' => (string)($order['ship_postal_code'] ?? ''),
        ];

        $provinceCodprov = (int)($order['ship_province_codprov'] ?? 0);
        if ($provinceCodprov > 0) {
            $district = self::provinceToCorreoStateId($provinceCodprov);
            if ($district !== null) {
                $destination['state_id'] = $district;
            }
        }

        return [
            'reference' => (string)($order['order_code'] ?? ''),
            'recipient' => [
                'name' => (string)($order['ship_name'] ?? ''),
                'phone' => (string)($order['phone'] ?? ''),
                'email' => (string)($order['email'] ?? ''),
            ],
            'destination' => $destination,
            'packages' => [
                [
                    'weight' => 1.0,
                    'content' => 'Productos de perfumeria',
                ],
            ],
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
