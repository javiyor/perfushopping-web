<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Support\Env;

final class MercadoPagoService
{
    /** @param array<string,mixed> $preference */
    public function createPreference(array $preference): array
    {
        $token = Env::get('MP_ACCESS_TOKEN', '');
        if ($token === '') {
            throw new \RuntimeException('MP_ACCESS_TOKEN no configurado.');
        }
        $ch = curl_init('https://api.mercadopago.com/checkout/preferences');
        if ($ch === false) {
            throw new \RuntimeException('No se pudo iniciar cURL.');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($preference, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Mercado Pago error: ' . $err);
        }
        curl_close($ch);
        $json = json_decode($body, true);
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('Mercado Pago HTTP ' . $code . ': ' . $body);
        }
        if (!is_array($json)) {
            throw new \RuntimeException('Mercado Pago respuesta invalida.');
        }
        return $json;
    }

    public function fetchPayment(int $paymentId): array
    {
        $token = Env::get('MP_ACCESS_TOKEN', '');
        if ($token === '') {
            throw new \RuntimeException('MP_ACCESS_TOKEN no configurado.');
        }
        $ch = curl_init('https://api.mercadopago.com/v1/payments/' . $paymentId);
        if ($ch === false) {
            throw new \RuntimeException('No se pudo iniciar cURL.');
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
            ],
            CURLOPT_TIMEOUT => 20,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Mercado Pago error: ' . $err);
        }
        curl_close($ch);
        $json = json_decode($body, true);
        if ($code < 200 || $code >= 300 || !is_array($json)) {
            throw new \RuntimeException('Mercado Pago payment HTTP ' . $code . ': ' . $body);
        }
        return $json;
    }
}
