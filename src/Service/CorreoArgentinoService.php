<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Support\Env;

final class CorreoArgentinoService
{
    private string $baseUrl;
    private string $apiKey;
    private string $agreement;

    public function __construct()
    {
        $this->baseUrl = rtrim(Env::get('CORREO_API_BASE', 'https://api.correoargentino.com.ar/paqar/v1'), '/');
        $this->apiKey = trim(Env::get('CORREO_API_KEY', ''));
        $this->agreement = trim(Env::get('CORREO_AGREEMENT', ''));
    }

    public function auth(): bool
    {
        $this->request('GET', '/auth');
        return true;
    }

    /** @return array<int, array<string,mixed>> */
    public function agencies(?string $stateId = null, ?bool $pickupAvailability = null, ?bool $packageReception = null): array
    {
        $query = [];
        if ($stateId !== null && $stateId !== '') {
            $query['stateId'] = $stateId;
        }
        if ($pickupAvailability !== null) {
            $query['pickup_availability'] = $pickupAvailability ? 'true' : 'false';
        }
        if ($packageReception !== null) {
            $query['package_reception'] = $packageReception ? 'true' : 'false';
        }

        $response = $this->request('GET', '/agencies', null, $query);
        return is_array($response) ? $response : [];
    }

    /** @param array<string,mixed> $payload
     *  @return array<string,mixed>
     */
    public function createOrder(array $payload): array
    {
        $response = $this->request('POST', '/orders', $payload);
        return is_array($response) ? $response : [];
    }

    /** @return array<string,mixed> */
    public function cancelOrder(string $trackingNumber): array
    {
        $trackingNumber = trim($trackingNumber);
        if ($trackingNumber === '') {
            throw new \RuntimeException('Tracking number vacio.');
        }
        $response = $this->request('PATCH', '/orders/' . rawurlencode($trackingNumber) . '/cancel');
        return is_array($response) ? $response : [];
    }

    /** @param array<int, array<string,string>> $items
     *  @return array<int, array<string,mixed>>
     */
    public function tracking(array $items, string $extClient = ''): array
    {
        $query = [];
        if (trim($extClient) !== '') {
            $query['extClient'] = $extClient;
        }
        $response = $this->request('GET', '/tracking', $items, $query);
        return is_array($response) ? $response : [];
    }

    /** @param array<int, array<string,string>> $items
     *  @return array<int, array<string,mixed>>
     */
    public function labels(array $items, string $labelFormat = '10x15'): array
    {
        $query = [];
        if ($labelFormat !== '') {
            $query['labelFormat'] = $labelFormat;
        }
        $response = $this->request('POST', '/labels', $items, $query);
        return is_array($response) ? $response : [];
    }

    /** @param array<string,mixed>|array<int, array<string,mixed>>|null $body
     *  @param array<string,string> $query
     *  @return mixed
     */
    private function request(string $method, string $path, array|null $body = null, array $query = []): mixed
    {
        $this->assertConfigured();

        $url = $this->baseUrl . $path;
        if ($query) {
            $url .= '?' . http_build_query($query);
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('No se pudo inicializar cURL para Correo Argentino.');
        }

        $headers = [
            'Authorization: Apikey ' . $this->apiKey,
            'agreement: ' . $this->agreement,
            'Accept: application/json',
        ];

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        if ($body !== null) {
            $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($json === false) {
                curl_close($ch);
                throw new \RuntimeException('No se pudo serializar payload de Correo Argentino.');
            }
            $headers[] = 'Content-Type: application/json';
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        }

        $raw = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);

        if ($raw === false) {
            throw new \RuntimeException('Error de conexion con Correo Argentino: ' . $err);
        }

        if ($httpCode >= 400) {
            $message = $this->extractErrorMessage($raw);
            throw new \RuntimeException('Correo Argentino HTTP ' . $httpCode . ($message !== '' ? ' - ' . $message : ''));
        }

        if ($httpCode === 204 || trim($raw) === '') {
            return true;
        }

        $decoded = json_decode($raw, true);
        return $decoded ?? $raw;
    }

    private function assertConfigured(): void
    {
        if ($this->baseUrl === '') {
            throw new \RuntimeException('CORREO_API_BASE no configurado.');
        }
        if ($this->apiKey === '') {
            throw new \RuntimeException('CORREO_API_KEY no configurado.');
        }
        if ($this->agreement === '') {
            throw new \RuntimeException('CORREO_AGREEMENT no configurado.');
        }
    }

    private function extractErrorMessage(string $raw): string
    {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $message = trim((string)($decoded['message'] ?? ''));
            if ($message !== '') {
                return $message;
            }
            $error = trim((string)($decoded['error'] ?? ''));
            if ($error !== '') {
                return $error;
            }
        }
        return trim($raw);
    }
}
