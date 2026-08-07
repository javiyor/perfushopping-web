<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Support\Env;

final class NaveService
{
    private const AUDIENCE = 'https://naranja.com/ranty/merchants/api';
    private string $token;

    public function configured(): bool
    {
        return Env::get('NAVE_CLIENT_ID', '') !== ''
            && Env::get('NAVE_CLIENT_SECRET', '') !== ''
            && Env::get('NAVE_POS_ID', '') !== '';
    }

    private function baseUrl(): string
    {
        return Env::get('NAVE_ENV', 'sandbox') === 'production'
            ? 'https://api.ranty.io/api'
            : 'https://api-sandbox.ranty.io/api';
    }

    private function paymentsUrl(): string
    {
        return Env::get('NAVE_ENV', 'sandbox') === 'production'
            ? 'https://punku.ranty.io/payments-ms/payments'
            : 'https://punku-sandbox.ranty.io/payments-ms/payments';
    }

    public function getToken(): string
    {
        if (isset($this->token)) {
            return $this->token;
        }
        $clientId = Env::get('NAVE_CLIENT_ID', '');
        $clientSecret = Env::get('NAVE_CLIENT_SECRET', '');
        if ($clientId === '' || $clientSecret === '') {
            throw new \RuntimeException('Nave: client_id/client_secret no configurados.');
        }
        $url = Env::get('NAVE_ENV', 'sandbox') === 'production'
            ? 'https://services.apinaranja.com/security-ms/api/security/auth0/b2b/m2msPrivate'
            : 'https://homoservices.apinaranja.com/security-ms/api/security/auth0/b2b/m2ms';

        $payload = json_encode([
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'audience' => Env::get('NAVE_AUDIENCE', self::AUDIENCE),
        ], JSON_UNESCAPED_SLASHES);

        $res = $this->request($url, 'POST', $payload, []);
        $token = (string)($res['access_token'] ?? '');
        if ($token === '') {
            throw new \RuntimeException('Nave: no se obtuvo access_token.');
        }
        return $this->token = $token;
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed> { id, checkout_url }
     */
    public function createPaymentRequest(array $payload): array
    {
        $url = $this->baseUrl() . '/payment_request/ecommerce';
        $res = $this->request($url, 'POST', json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (empty($res['id']) || empty($res['checkout_url'])) {
            throw new \RuntimeException('Nave: respuesta incompleta (faltan id o checkout_url).');
        }
        return $res;
    }

    /**
     * Estado de una intencion de pago.
     * @return array<string,mixed>
     */
    public function getPaymentRequest(string $paymentRequestId): array
    {
        $url = $this->baseUrl() . '/payment_requests/' . rawurlencode($paymentRequestId);
        return $this->request($url, 'GET');
    }

    /**
     * Detalle interno de un pago (webhook/conciliacion).
     * @return array<string,mixed>
     */
    public function getPayment(string $paymentId): array
    {
        $url = $this->paymentsUrl() . '/' . rawurlencode($paymentId) . '/internal';
        return $this->request($url, 'GET');
    }

    /**
     * @return array<string,mixed>
     */
    private function request(string $url, string $method, string $body = '', array $headers = []): array
    {
        $headers = array_merge([
            'Authorization' => 'Bearer ' . $this->getToken(),
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
        ], $headers);

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('Nave: no se pudo iniciar cURL.');
        }
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => self::buildHeaders($headers),
            CURLOPT_TIMEOUT => 30,
        ];
        if ($method === 'POST') {
            $opts[CURLOPT_POST] = true;
            if ($body !== '') {
                $opts[CURLOPT_POSTFIELDS] = $body;
            }
        }
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Nave: error de red: ' . $err);
        }
        curl_close($ch);
        $json = json_decode($raw, true);
        if ($code < 200 || $code >= 300 || !is_array($json)) {
            throw new \RuntimeException('Nave: HTTP ' . $code . ' ' . substr($raw, 0, 400));
        }
        return $json;
    }

    /**
     * @param array<string,string> $headers
     * @return array<int,string>
     */
    private static function buildHeaders(array $headers): array
    {
        $out = [];
        foreach ($headers as $k => $v) {
            $out[] = $k . ': ' . $v;
        }
        return $out;
    }
}