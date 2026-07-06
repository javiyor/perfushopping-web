<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\View;

final class EmailController
{
    private function conectar(): ?\IMAP\Connection
    {
        $user = Env::get('SMTP_USER', '');
        $pass = Env::get('SMTP_PASS', '');
        if ($user === '' || $pass === '') return null;
        $mbox = @imap_open('{imap.hostinger.com:993/imap/ssl}INBOX', $user, $pass, 0, 1);
        return $mbox ?: null;
    }

    private function fetchBody(\IMAP\Connection $mbox, int $uid, bool $preferHtml = true): string
    {
        $struct = imap_fetchstructure($mbox, $uid);

        $partNum = '1';
        $encoding = $struct->encoding ?? 0;

        if (isset($struct->parts) && count($struct->parts) > 0) {
            $preferred = $preferHtml ? 'HTML' : 'PLAIN';
            $fallback = $preferHtml ? 'PLAIN' : 'HTML';
            $found = false;

            foreach ([$preferred, $fallback] as $subtype) {
                foreach ($struct->parts as $i => $part) {
                    if (strtoupper($part->subtype ?? '') === $subtype) {
                        $partNum = (string)($i + 1);
                        $encoding = (int)($part->encoding ?? 0);
                        $found = true;
                        break 2;
                    }
                }
            }

            if (!$found && isset($struct->parts[0]->encoding)) {
                $encoding = (int)$struct->parts[0]->encoding;
            }
        }

        $body = imap_fetchbody($mbox, $uid, $partNum);
        if ($body === '' || $body === null) return '';

        if ($encoding === 3) {
            $decoded = @imap_base64($body);
            return is_string($decoded) ? $decoded : $body;
        }
        if ($encoding === 4) {
            $decoded = @imap_qprint($body);
            return is_string($decoded) ? $decoded : $body;
        }

        return $body;
    }

    public function inbox(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $imapExtension = function_exists('imap_open');
        $emails = [];
        $error = null;

        if ($imapExtension) {
            try {
                $mbox = $this->conectar();
                if (!$mbox) {
                    $error = 'No se pudo conectar al servidor IMAP. Verificá las credenciales en .env.';
                } else {
                    $num = imap_num_msg($mbox);
                    $limit = min($num, 50);
                    for ($i = $limit; $i >= max(1, $num - 49); $i--) {
                        $header = imap_headerinfo($mbox, $i);
                        $body = $this->fetchBody($mbox, $i, false);
                        $emails[] = [
                            'uid' => $i,
                            'from' => $header->from[0]->mailbox . '@' . ($header->from[0]->host ?? ''),
                            'from_name' => $header->from[0]->personal ?? '',
                            'subject' => $header->subject ?? '(sin asunto)',
                            'date' => $header->date ?? '',
                            'body_preview' => mb_substr(strip_tags($body), 0, 200),
                        ];
                    }
                    imap_close($mbox);
                }
            } catch (\Throwable $e) {
                $error = 'Error IMAP: ' . $e->getMessage();
            }
        } else {
            $error = 'La extensión PHP IMAP no está instalada en este servidor.';
        }

        echo View::adminPage('admin/email/inbox.php', [
            'adminUser' => $adminUser,
            'emails' => $emails,
            'error' => $error,
            'imapExtension' => $imapExtension,
            'pageTitle' => 'Bandeja de entrada',
        ]);
    }

    public function view(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requireSesion();

        $uid = (int)($params['uid'] ?? 0);
        if ($uid <= 0) {
            Response::redirect('/admin/email');
        }

        $email = null;
        $error = null;

        try {
            $mbox = $this->conectar();
            if ($mbox) {
                $header = imap_headerinfo($mbox, $uid);
                $body = $this->fetchBody($mbox, $uid, true);
                $email = [
                    'from' => $header->from[0]->mailbox . '@' . ($header->from[0]->host ?? ''),
                    'from_name' => $header->from[0]->personal ?? '',
                    'subject' => $header->subject ?? '(sin asunto)',
                    'date' => $header->date ?? '',
                    'body' => $body,
                ];
                imap_close($mbox);
            } else {
                $error = 'No se pudo conectar al servidor IMAP.';
            }
        } catch (\Throwable $e) {
            $error = 'Error: ' . $e->getMessage();
        }

        echo View::adminPage('admin/email/view.php', [
            'adminUser' => $adminUser,
            'email' => $email,
            'error' => $error,
            'pageTitle' => 'Email: ' . ($email['subject'] ?? ''),
        ]);
    }
}
