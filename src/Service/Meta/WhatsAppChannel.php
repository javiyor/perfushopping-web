<?php
declare(strict_types=1);

namespace Perfushopping\Web\Service\Meta;

use Perfushopping\Web\Support\Env;

final class WhatsAppChannel implements MetaChannelInterface
{
    public function name(): string
    {
        return 'whatsapp';
    }

    public static function graphVersion(): string
    {
        $v = trim((string)(Env::get('META_GRAPH_API_VERSION', 'v25.0') ?? 'v25.0'));
        return $v !== '' ? $v : 'v25.0';
    }

    public static function accessToken(): string
    {
        return trim((string)(Env::get('WA_ACCESS_TOKEN', '') ?? ''));
    }

    public static function phoneNumberId(): string
    {
        return trim((string)(Env::get('WA_PHONE_NUMBER_ID', '') ?? ''));
    }

    public static function isConfigured(): bool
    {
        return self::accessToken() !== '' && self::phoneNumberId() !== '';
    }

    public static function buildSendUrl(): ?string
    {
        $phoneId = self::phoneNumberId();
        if ($phoneId === '') {
            return null;
        }
        return 'https://graph.facebook.com/' . self::graphVersion() . '/' . $phoneId . '/messages';
    }

    /** @return array<string,mixed> */
    public static function buildTextPayload(string $to, string $text): array
    {
        return [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $to,
            'type' => 'text',
            'text' => [
                'preview_url' => false,
                'body' => $text,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $body
     * @return array{ok: bool, wamid: ?string, error: ?string}
     */
    public static function parseSendResponse(int $httpCode, array $body): array
    {
        $messages = $body['messages'] ?? null;
        if ($httpCode >= 200 && $httpCode < 300 && is_array($messages) && isset($messages[0]['id'])) {
            $wamid = trim((string)$messages[0]['id']);
            if ($wamid !== '') {
                return ['ok' => true, 'wamid' => $wamid, 'error' => null];
            }
        }
        $err = $body['error'] ?? null;
        $msg = 'Error ' . $httpCode;
        $code = 0;
        if (is_array($err)) {
            $code = (int)($err['code'] ?? 0);
            $detail = trim((string)($err['message'] ?? ''));
            $msg = $code > 0 ? ('Error ' . $code . ': ' . $detail) : $detail;
            if ($msg === '') {
                $msg = 'Error ' . $httpCode;
            }
        }
        return ['ok' => false, 'wamid' => null, 'error' => $msg !== '' ? $msg : 'Error desconocido'];
    }

    /** @return array{ok: bool, wamid: ?string, error: ?string} */
    public function sendText(string $to, string $text): array
    {
        if (!self::isConfigured()) {
            return ['ok' => false, 'wamid' => null, 'error' => 'WhatsApp no configurado (WA_ACCESS_TOKEN / WA_PHONE_NUMBER_ID).'];
        }
        $to = preg_replace('/\D/', '', $to) ?? '';
        if ($to === '' || strlen($to) < 8) {
            return ['ok' => false, 'wamid' => null, 'error' => 'Teléfono destino inválido.'];
        }
        $text = trim($text);
        if ($text === '') {
            return ['ok' => false, 'wamid' => null, 'error' => 'Mensaje vacío.'];
        }
        $text = mb_substr($text, 0, 4096);

        $url = self::buildSendUrl();
        if ($url === null) {
            return ['ok' => false, 'wamid' => null, 'error' => 'Falta WA_PHONE_NUMBER_ID.'];
        }
        $res = MetaHttp::postJson($url, [
            'Authorization: Bearer ' . self::accessToken(),
            'Content-Type: application/json',
        ], self::buildTextPayload($to, $text));
        return self::parseSendResponse($res['code'], $res['body']);
    }
}
