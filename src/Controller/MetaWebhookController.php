<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Repo\SocialInboxRepo;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

final class MetaWebhookController
{
    public function verify(array $params): void
    {
        $mode = (string)($_GET['hub_mode'] ?? $_GET['hub.mode'] ?? '');
        $token = (string)($_GET['hub_verify_token'] ?? $_GET['hub.verify_token'] ?? '');
        $challenge = (string)($_GET['hub_challenge'] ?? $_GET['hub.challenge'] ?? '');
        $expected = (string)Env::get('META_VERIFY_TOKEN', '');

        if ($mode === 'subscribe' && $expected !== '' && hash_equals($expected, $token)) {
            Response::html($challenge, 200);
            return;
        }

        Response::html('forbidden', 403);
    }

    public function receive(array $params): void
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || $raw === '') {
            Response::json(['ok' => false], 400);
            return;
        }

        if (!$this->isSignatureValid($raw)) {
            Response::json(['ok' => false, 'error' => 'invalid_signature'], 401);
            return;
        }

        $repo = new SocialInboxRepo();
        $eventKey = hash('sha256', $raw);
        $stored = $repo->storeWebhookEvent($eventKey, $raw);
        if (!$stored) {
            Response::json(['ok' => true, 'dup' => true], 200);
            return;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            Response::json(['ok' => true], 200);
            return;
        }

        foreach ($this->extractMessages($payload) as $m) {
            $repo->ingestInbound($m);
        }

        foreach (self::normalizeStatuses($payload) as $s) {
            $repo->applyStatus($s['message_id'], $s['status'], $s['at'], $s['error_code']);
        }

        Response::json(['ok' => true], 200);
    }

    private function isSignatureValid(string $raw): bool
    {
        $secret = (string)Env::get('META_APP_SECRET', '');
        if ($secret === '') {
            return true;
        }

        $header = (string)($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');
        if (!str_starts_with($header, 'sha256=')) {
            return false;
        }

        $given = substr($header, 7);
        $calc = hash_hmac('sha256', $raw, $secret);
        return hash_equals($calc, $given);
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<int,array<string,mixed>>
     */
    private function extractMessages(array $payload): array
    {
        $messages = [];
        $object = (string)($payload['object'] ?? '');
        $entries = $payload['entry'] ?? [];
        if (!is_array($entries)) {
            return $messages;
        }

        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }

            $changes = $entry['changes'] ?? null;
            if (is_array($changes)) {
                $messages = array_merge($messages, $this->extractWhatsAppChanges($changes));
            }

            $messaging = $entry['messaging'] ?? null;
            if (is_array($messaging)) {
                $channel = $object === 'instagram' ? 'instagram' : 'facebook';
                $messages = array_merge($messages, $this->extractMessengerEvents($messaging, $channel));
            }
        }

        return $messages;
    }

    /**
     * Normaliza los eventos de estado de WhatsApp (value.statuses).
     * @param array<string,mixed> $payload
     * @return array<int,array{message_id:string,status:string,at:?string,error_code:?string,recipient:string}>
     */
    public static function normalizeStatuses(array $payload): array
    {
        $out = [];
        $entries = $payload['entry'] ?? [];
        if (!is_array($entries)) {
            return $out;
        }
        foreach ($entries as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $changes = $entry['changes'] ?? null;
            if (!is_array($changes)) {
                continue;
            }
            foreach ($changes as $change) {
                if (!is_array($change)) {
                    continue;
                }
                $value = $change['value'] ?? null;
                if (!is_array($value)) {
                    continue;
                }
                $statuses = $value['statuses'] ?? null;
                if (!is_array($statuses)) {
                    continue;
                }
                foreach ($statuses as $st) {
                    if (!is_array($st)) {
                        continue;
                    }
                    $messageId = trim((string)($st['id'] ?? ''));
                    $status = trim((string)($st['status'] ?? ''));
                    if ($messageId === '' || !in_array($status, ['sent', 'delivered', 'read', 'failed'], true)) {
                        continue;
                    }
                    $at = null;
                    $ts = (string)($st['timestamp'] ?? '');
                    if ($ts !== '' && ctype_digit($ts)) {
                        $at = gmdate('Y-m-d H:i:s', (int)$ts);
                    }
                    $errorCode = null;
                    $errors = $st['errors'] ?? null;
                    if (is_array($errors) && isset($errors[0]) && is_array($errors[0])) {
                        $errorCode = trim((string)($errors[0]['code'] ?? ''));
                        if ($errorCode === '') {
                            $errorCode = null;
                        }
                    }
                    $out[] = [
                        'message_id' => $messageId,
                        'status' => $status,
                        'at' => $at,
                        'error_code' => $errorCode,
                        'recipient' => trim((string)($st['recipient_id'] ?? '')),
                    ];
                }
            }
        }
        return $out;
    }

    /** @param array<int,mixed> $changes */
    private function extractWhatsAppChanges(array $changes): array
    {
        $out = [];
        foreach ($changes as $change) {
            if (!is_array($change)) {
                continue;
            }
            $value = $change['value'] ?? null;
            if (!is_array($value)) {
                continue;
            }

            $contacts = [];
            if (isset($value['contacts']) && is_array($value['contacts'])) {
                foreach ($value['contacts'] as $contact) {
                    if (!is_array($contact)) {
                        continue;
                    }
                    $waId = (string)($contact['wa_id'] ?? '');
                    if ($waId === '') {
                        continue;
                    }
                    $contacts[$waId] = [
                        'name' => trim((string)($contact['profile']['name'] ?? '')),
                        'phone' => preg_replace('/\D/', '', $waId) ?: '',
                    ];
                }
            }

            $messages = $value['messages'] ?? null;
            if (!is_array($messages)) {
                continue;
            }

            foreach ($messages as $msg) {
                if (!is_array($msg)) {
                    continue;
                }
                $from = trim((string)($msg['from'] ?? ''));
                if ($from === '') {
                    continue;
                }

                $type = trim((string)($msg['type'] ?? 'text'));
                $body = '';
                if ($type === 'text') {
                    $body = trim((string)($msg['text']['body'] ?? ''));
                } elseif ($type === 'button') {
                    $body = trim((string)($msg['button']['text'] ?? ''));
                } elseif ($type === 'interactive') {
                    $body = trim((string)($msg['interactive']['button_reply']['title'] ?? $msg['interactive']['list_reply']['title'] ?? ''));
                }

                $providerAt = null;
                $ts = (string)($msg['timestamp'] ?? '');
                if ($ts !== '' && ctype_digit($ts)) {
                    $providerAt = gmdate('Y-m-d H:i:s', (int)$ts);
                }

                $attachments = null;
                if ($type !== 'text') {
                    $attachments = json_encode([$type => $msg[$type] ?? null], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                }

                $out[] = [
                    'channel' => 'whatsapp',
                    'external_id' => $from,
                    'display_name' => (string)($contacts[$from]['name'] ?? ''),
                    'phone' => (string)($contacts[$from]['phone'] ?? preg_replace('/\D/', '', $from)),
                    'message_id' => (string)($msg['id'] ?? ''),
                    'message_type' => $type,
                    'body' => $body,
                    'attachments_json' => $attachments,
                    'provider_created_at' => $providerAt,
                    'raw_json' => json_encode($msg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ];
            }
        }
        return $out;
    }

    /** @param array<int,mixed> $events */
    private function extractMessengerEvents(array $events, string $channel): array
    {
        $out = [];
        foreach ($events as $ev) {
            if (!is_array($ev) || !isset($ev['message']) || !is_array($ev['message'])) {
                continue;
            }

            $msg = $ev['message'];
            if (!empty($msg['is_echo'])) {
                continue;
            }

            $senderId = trim((string)($ev['sender']['id'] ?? ''));
            if ($senderId === '') {
                continue;
            }

            $body = trim((string)($msg['text'] ?? ''));
            $attachments = null;
            if (isset($msg['attachments']) && is_array($msg['attachments']) && $msg['attachments']) {
                $attachments = json_encode($msg['attachments'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }

            $providerAt = null;
            if (isset($ev['timestamp']) && is_numeric((string)$ev['timestamp'])) {
                $providerAt = gmdate('Y-m-d H:i:s', (int)(((int)$ev['timestamp']) / 1000));
            }

            $out[] = [
                'channel' => $channel,
                'external_id' => $senderId,
                'display_name' => '',
                'phone' => '',
                'message_id' => (string)($msg['mid'] ?? ''),
                'message_type' => $attachments ? 'attachment' : 'text',
                'body' => $body,
                'attachments_json' => $attachments,
                'provider_created_at' => $providerAt,
                'raw_json' => json_encode($ev, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ];
        }
        return $out;
    }
}
