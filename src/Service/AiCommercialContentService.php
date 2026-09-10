<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service;

use Perfushopping\Web\Support\Env;

final class AiCommercialContentService
{
    /**
     * @param array<string,mixed> $product
     * @param array<int,array<string,mixed>> $variants
     * @return array{content:array<string,string>,tags:array<int,array{taxonomy_key:string,term_value:string}>}
     */
    public function suggestForProduct(array $product, array $variants): array
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
            if ($name !== '') $variantNames[] = $name;
        }

        $promptData = [
            'idprodu' => (int)($product['idprodu'] ?? 0),
            'codigo' => trim((string)($product['codprodu'] ?? '')),
            'nombre' => trim((string)($product['produ'] ?? '')),
            'marca' => trim((string)($product['nomsub'] ?? '')),
            'categoria' => trim((string)($product['nomrub'] ?? '')),
            'descripcion_actual' => trim((string)($product['observ'] ?? '')),
            'variantes' => $variantNames,
        ];

        $system = 'Sos asistente de contenido comercial para Perfushopping, una perfumería y distribuidora de productos de peluquería. ' .
            'Recibís datos de un producto y devolvés un JSON estructurado. ' .
            'No inventes ingredientes, beneficios clínicos, duración, origen ni datos técnicos que no estén en los datos. ' .
            'Si no hay información suficiente para un campo, devolvé una cadena vacía. ' .
            'Para tags, usá solo taxonomías y valores de este listado cerrado: ' .
            'need: cabello_danado, sequedad, frizz, color, caida, brillo; ' .
            'hair_type: liso, ondulado, rizado, afro, fino, grueso; ' .
            'goal: reparar, hidratar, proteger, definir_rizos, volumen; ' .
            'routine_step: limpiar, acondicionar, tratar, mascarilla, proteger, finalizar; ' .
            'usage: profesional, hogar, frecuente, ocasional. ' .
            'Incluí tags solo cuando tengas certeza razonable. ' .
            'Formato de respuesta: JSON válido con keys "content" (objeto) y "tags" (array). ' .
            'Dentro de "content" incluí: benefit, ideal_for, problem, results, usage, advice, cta.';

        $payload = [
            'model' => $model,
            'temperature' => 0.5,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => "Sugerí contenido comercial para este producto:\n" . json_encode($promptData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)],
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
                if (is_array($item) && isset($item['text']) && is_string($item['text'])) $parts[] = $item['text'];
            }
            $content = implode("\n", $parts);
        }
        $content = trim((string)$content);
        if ($content === '') {
            throw new \RuntimeException('La IA devolvio una respuesta vacia.');
        }

        $parsed = json_decode($content, true);
        if (!is_array($parsed)) {
            throw new \RuntimeException('La IA no devolvio JSON valido.');
        }

        return [
            'content' => array_map(static fn($v) => is_string($v) ? trim($v) : '', (array)($parsed['content'] ?? [])),
            'tags' => array_values(array_filter((array)($parsed['tags'] ?? []), static fn($t) => is_array($t) && !empty($t['taxonomy_key']) && !empty($t['term_value']))),
        ];
    }
}
