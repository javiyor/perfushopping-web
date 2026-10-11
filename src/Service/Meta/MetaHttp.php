<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service\Meta;

final class MetaHttp
{
    /** POST JSON por cURL. Nunca loguea headers (pueden llevar tokens).
     * @param array<int,string> $headers
     * @param array<string,mixed> $payload
     * @return array{code: int, body: array<string,mixed>}
     */
    public static function postJson(string $url, array $headers, array $payload, int $timeout = 15): array
    {
        $ch = curl_init($url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => max(5, min(60, $timeout)),
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ];
        curl_setopt_array($ch, $opts);
        $raw = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $errno = curl_errno($ch);
        $err = $errno ? (string)curl_error($ch) : '';
        curl_close($ch);

        if ($errno || !is_string($raw) || $raw === '') {
            return ['code' => 0, 'body' => ['error' => ['message' => $err !== '' ? ('Error de red: ' . $err) : 'Sin respuesta del servidor', 'code' => 0]]];
        }
        $decoded = json_decode($raw, true);
        return ['code' => $code, 'body' => is_array($decoded) ? $decoded : []];
    }
}
