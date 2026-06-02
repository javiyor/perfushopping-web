<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Support\Env;

final class AiProductDescriptionService
{
    /** @param array<string,mixed> $product @param array<int, array<string,mixed>> $variants */
    public function generate(array $product, array $variants): string
    {
        $apiKey = trim((string)Env::get('OPENAI_API_KEY', ''));
        if ($apiKey === '') {
            throw new \RuntimeException('OPENAI_API_KEY no configurado.');
        }

        $baseUrl = rtrim((string)Env::get('OPENAI_BASE_URL', 'https://api.openai.com/v1'), '/');
        $url = str_ends_with($baseUrl, '/chat/completions') ? $baseUrl : $baseUrl . '/chat/completions';
        $model = trim((string)Env::get('OPENAI_MODEL', 'gpt-4.1-mini'));
        $timeout = (int)(Env::get('OPENAI_TIMEOUT', '30') ?? '30');
        $timeout = max(5, min(120, $timeout));

        $variantNames = [];
        foreach ($variants as $variant) {
            $name = trim((string)($variant['nomgusto'] ?? ''));
            if ($name !== '') {
                $variantNames[] = $name;
            }
        }

        $promptData = [
            'idprodu' => (int)($product['idprodu'] ?? 0),
            'codigo' => trim((string)($product['codprodu'] ?? '')),
            'nombre' => trim((string)($product['produ'] ?? '')),
            'marca' => trim((string)($product['nomsub'] ?? '')),
            'categoria' => trim((string)($product['nomrub'] ?? '')),
            'descripcion_actual' => trim((string)($product['observ'] ?? '')),
            'precio_minorista_con_iva' => $this->grossPrice((float)($product['precio'] ?? 0), (float)($product['tiva'] ?? 0)),
            'precio_mayorista_con_iva' => $this->grossPrice((float)($product['precio1'] ?? 0), (float)($product['tiva'] ?? 0)),
            'iva' => (float)($product['tiva'] ?? 0),
            'fecha_compra' => trim((string)($product['fecompra'] ?? '')),
            'variantes' => $variantNames,
        ];

        $payload = [
            'model' => $model,
            'temperature' => 0.7,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sos redactor ecommerce para Perfushopping. Escribi en espanol argentino, tono comercial claro y confiable. No inventes ingredientes, beneficios clinicos, duracion, origen ni datos tecnicos que no esten presentes. Entrega solo texto final, sin titulos ni comillas, en 1 o 2 parrafos breves.',
                ],
                [
                    'role' => 'user',
                    'content' => "Genera una descripcion breve de producto para ecommerce usando estos datos JSON:\n" . json_encode($promptData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
        ];

        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('No se pudo iniciar cURL para IA.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $apiKey,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            CURLOPT_TIMEOUT => $timeout,
        ]);

        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($body === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('IA error: ' . $err);
        }
        curl_close($ch);

        $json = json_decode($body, true);
        if ($code < 200 || $code >= 300 || !is_array($json)) {
            throw new \RuntimeException('IA HTTP ' . $code . ': ' . $body);
        }

        $content = $json['choices'][0]['message']['content'] ?? '';
        if (is_array($content)) {
            $parts = [];
            foreach ($content as $item) {
                if (is_array($item) && isset($item['text']) && is_string($item['text'])) {
                    $parts[] = $item['text'];
                }
            }
            $content = implode("\n", $parts);
        }

        $content = trim((string)$content);
        if ($content === '') {
            throw new \RuntimeException('La IA devolvio una respuesta vacia.');
        }
        return $content;
    }

    private function grossPrice(float $net, float $ivaRate): float
    {
        return round($net * (1.0 + ($ivaRate / 100.0)), 2);
    }
}
