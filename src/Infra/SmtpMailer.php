<?php
declare(strict_types=1);

namespace Perfushopping\Web\Infra;

use Perfushopping\Web\Support\Env;

final class SmtpMailer
{
    /**
     * @param array $attachments [['name'=>'file.pdf','content'=>'raw bytes','mime'=>'application/pdf'], ...]
     */
    public function send(string $to, string $subject, string $htmlBody, string $textBody = '', array $attachments = [], array $extraHeaders = []): void
    {
        $host = Env::get('SMTP_HOST', 'smtp.hostinger.com');
        $port = (int)Env::get('SMTP_PORT', '465');
        $enc = Env::get('SMTP_ENCRYPTION', 'ssl');
        $user = Env::get('SMTP_USER', '');
        $pass = Env::get('SMTP_PASS', '');
        $from = Env::get('MAIL_FROM', $user);
        $fromName = Env::get('MAIL_FROM_NAME', 'perfushopping');

        if ($user === '' || $pass === '' || $from === '') {
            throw new \RuntimeException('SMTP is not configured (missing SMTP_USER/SMTP_PASS/MAIL_FROM).');
        }

        $transport = ($enc === 'ssl') ? 'ssl://' : '';
        $fp = stream_socket_client($transport . $host . ':' . $port, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!$fp) {
            throw new \RuntimeException('SMTP connect failed: ' . $errstr);
        }
        stream_set_timeout($fp, 20);

        $this->expect($fp, 220);
        $this->cmd($fp, 'EHLO perfushopping');
        $this->expect($fp, 250);

        $this->cmd($fp, 'AUTH LOGIN');
        $this->expect($fp, 334);
        $this->cmd($fp, base64_encode($user));
        $this->expect($fp, 334);
        $this->cmd($fp, base64_encode($pass));
        $this->expect($fp, 235);

        $this->cmd($fp, 'MAIL FROM:<' . $from . '>');
        $this->expect($fp, 250);
        $this->cmd($fp, 'RCPT TO:<' . $to . '>');
        $this->expect($fp, 250);
        $this->cmd($fp, 'DATA');
        $this->expect($fp, 354);

        $inlineAttachments = [];
        $regularAttachments = [];
        foreach ($attachments as $attachment) {
            if (!empty($attachment['inline'])) {
                $contentId = trim((string)($attachment['content_id'] ?? ''));
                if (!preg_match('/^[A-Za-z0-9._-]{1,120}$/', $contentId)) {
                    throw new \InvalidArgumentException('Inline attachment requires a valid content_id.');
                }
                $inlineAttachments[] = $attachment;
            } else {
                $regularAttachments[] = $attachment;
            }
        }
        $hasAttachments = count($regularAttachments) > 0;
        $hasInlineAttachments = count($inlineAttachments) > 0;
        $boundaryMixed = 'mixed_' . bin2hex(random_bytes(12));
        $boundaryAlt = 'alt_' . bin2hex(random_bytes(12));
        $boundaryRelated = 'related_' . bin2hex(random_bytes(12));

        $headers = [];
        $headers[] = 'From: ' . $this->encodeHeaderName($fromName) . ' <' . $from . '>';
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . $this->encodeHeaderName($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Date: ' . date('r');
        foreach (['In-Reply-To', 'References', 'List-Unsubscribe', 'List-Unsubscribe-Post'] as $headerName) {
            $headerValue = trim((string)($extraHeaders[$headerName] ?? ''));
            if ($headerValue !== '' && !preg_match('/[\r\n]/', $headerValue)) {
                $headers[] = $headerName . ': ' . $headerValue;
            }
        }

        if ($hasAttachments) {
            $headers[] = 'Content-Type: multipart/mixed; boundary=' . $boundaryMixed;
            $msg = implode("\r\n", $headers) . "\r\n\r\n";
            $msg .= '--' . $boundaryMixed . "\r\n";
            $msg .= "Content-Type: multipart/alternative; boundary=" . $boundaryAlt . "\r\n\r\n";
        } else {
            $headers[] = 'Content-Type: multipart/alternative; boundary=' . $boundaryAlt;
            $msg = implode("\r\n", $headers) . "\r\n\r\n";
        }

        $text = $textBody !== '' ? $textBody : strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $htmlBody));

        $msg .= '--' . $boundaryAlt . "\r\n";
        $msg .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $msg .= $this->dotStuff($text) . "\r\n";
        $msg .= '--' . $boundaryAlt . "\r\n";
        if ($hasInlineAttachments) {
            $msg .= 'Content-Type: multipart/related; boundary=' . $boundaryRelated . "\r\n\r\n";
            $msg .= '--' . $boundaryRelated . "\r\n";
        }
        $msg .= "Content-Type: text/html; charset=UTF-8\r\n";
        $msg .= "Content-Transfer-Encoding: 8bit\r\n\r\n";
        $msg .= $this->dotStuff($htmlBody) . "\r\n";
        if ($hasInlineAttachments) {
            foreach ($inlineAttachments as $att) {
                $msg .= '--' . $boundaryRelated . "\r\n";
                $mime = $this->safeMime((string)($att['mime'] ?? 'application/octet-stream'));
                $name = $this->safeFilename((string)($att['name'] ?? 'image'));
                $msg .= 'Content-Type: ' . $mime . '; name="' . $name . "\"\r\n";
                $msg .= 'Content-ID: <' . trim((string)$att['content_id']) . ">\r\n";
                $msg .= 'Content-Disposition: inline; filename="' . $name . "\"\r\n";
                $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $msg .= chunk_split(base64_encode((string)($att['content'] ?? '')), 76, "\r\n") . "\r\n";
            }
            $msg .= '--' . $boundaryRelated . "--\r\n";
        }
        $msg .= '--' . $boundaryAlt . "--\r\n";

        if ($hasAttachments) {
            foreach ($regularAttachments as $att) {
                $msg .= '--' . $boundaryMixed . "\r\n";
                $mime = $this->safeMime((string)($att['mime'] ?? 'application/octet-stream'));
                $name = $this->safeFilename((string)($att['name'] ?? 'attachment'));
                $msg .= 'Content-Type: ' . $mime . '; name="' . $name . "\"\r\n";
                $msg .= 'Content-Disposition: attachment; filename="' . $name . "\"\r\n";
                $msg .= "Content-Transfer-Encoding: base64\r\n\r\n";
                $msg .= chunk_split(base64_encode((string)($att['content'] ?? '')), 76, "\r\n") . "\r\n";
            }
            $msg .= '--' . $boundaryMixed . "--\r\n";
        }

        fwrite($fp, $msg . "\r\n.\r\n");
        $this->expect($fp, 250);
        $this->cmd($fp, 'QUIT');
        fclose($fp);
    }

    private function safeMime(string $mime): string
    {
        return preg_match('#^[A-Za-z0-9.+-]+/[A-Za-z0-9.+-]+$#', $mime) ? $mime : 'application/octet-stream';
    }

    private function safeFilename(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? '';
        return $name !== '' ? $name : 'attachment';
    }

    private function cmd($fp, string $line): void
    {
        fwrite($fp, $line . "\r\n");
    }

    private function expect($fp, int $code): void
    {
        $buf = '';
        while (!feof($fp)) {
            $line = fgets($fp, 2048);
            if ($line === false) {
                break;
            }
            $buf .= $line;
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }
        if (!preg_match('/^(\d{3})/', $buf, $m)) {
            throw new \RuntimeException('SMTP invalid response: ' . trim($buf));
        }
        $got = (int)$m[1];
        if ($got !== $code) {
            throw new \RuntimeException('SMTP expected ' . $code . ' got ' . $got . ': ' . trim($buf));
        }
    }

    private function dotStuff(string $s): string
    {
        $s = str_replace("\r\n", "\n", $s);
        $s = str_replace("\r", "\n", $s);
        $lines = explode("\n", $s);
        foreach ($lines as &$line) {
            if (isset($line[0]) && $line[0] === '.') {
                $line = '.' . $line;
            }
        }
        unset($line);
        return implode("\r\n", $lines);
    }

    private function encodeHeaderName(string $s): string
    {
        $s = trim($s);
        if ($s === '') {
            return '';
        }
        return '=?UTF-8?B?' . base64_encode($s) . '?=';
    }
}
