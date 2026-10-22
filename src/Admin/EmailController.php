<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\EmailFolderRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
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

    private function decodeHeader(string $value): string
    {
        if (!function_exists('imap_mime_header_decode') || trim($value) === '') {
            return $value;
        }
        $out = '';
        foreach (@imap_mime_header_decode($value) ?: [] as $p) {
            $text = (string)($p->text ?? '');
            $charset = strtoupper(trim((string)($p->charset ?? '')));
            if ($charset === '' || $charset === 'DEFAULT' || $charset === 'UTF-8' || $charset === 'UTF8' || $charset === 'US-ASCII') {
                $out .= $text;
            } else {
                $out .= $this->toUtf8($text, $charset);
            }
        }
        return $out !== '' ? $out : $value;
    }

    private function toUtf8(string $text, string $charset): string
    {
        $charset = strtoupper(trim($charset));
        if ($charset === '' || $charset === 'UTF-8' || $charset === 'UTF8' || $charset === 'US-ASCII' || $charset === 'ASCII') {
            return $text;
        }
        if (function_exists('mb_convert_encoding')) {
            try {
                $c = @mb_convert_encoding($text, 'UTF-8', $charset);
                if (is_string($c) && $c !== '') {
                    return $c;
                }
            } catch (\Throwable $e) {
            }
        }
        try {
            $c = @iconv($charset, 'UTF-8//TRANSLIT//IGNORE', $text);
            if (is_string($c) && $c !== '') {
                return $c;
            }
        } catch (\Throwable $e) {
        }
        return $text;
    }

    /**
     * Recorre el árbol MIME y devuelve las partes de texto.
     * @return array<int, array{num: string, subtype: string, encoding: int, charset: string, disposition: string, filename: string, mime: string, bytes: int, is_attachment: bool}>
     */
    private function walkParts(object $struct, string $prefix = ''): array
    {
        $out = [];
        if (!empty($struct->parts) && is_array($struct->parts)) {
            foreach ($struct->parts as $i => $part) {
                if (!is_object($part)) {
                    continue;
                }
                $num = $prefix === '' ? (string)($i + 1) : $prefix . '.' . ($i + 1);
                if (!empty($part->parts) && is_array($part->parts)) {
                    foreach ($this->walkParts($part, $num) as $sub) {
                        $out[] = $sub;
                    }
                    continue;
                }
                $params = [];
                foreach (['parameters', 'dparameters'] as $pk) {
                    if (!empty($part->$pk)) {
                        foreach ((array)$part->$pk as $pp) {
                            if (is_object($pp)) {
                                $params[strtolower((string)($pp->attribute ?? ''))] = (string)($pp->value ?? '');
                            }
                        }
                    }
                }
                $filename = (string)($params['filename*'] ?? $params['filename'] ?? $params['name'] ?? '');
                if (preg_match("/^([^']*)''(.+)$/", $filename, $fm)) {
                    $filename = rawurldecode($fm[2]);
                }
                $filename = $this->decodeHeader($filename);
                $types = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
                $primary = strtolower($types[(int)($part->type ?? 7)] ?? 'application');
                $subtype = strtoupper((string)($part->subtype ?? ''));
                $mime = $primary . '/' . strtolower($subtype !== '' ? $subtype : 'octet-stream');
                $out[] = [
                    'num' => $num,
                    'subtype' => $subtype,
                    'encoding' => (int)($part->encoding ?? 0),
                    'charset' => (string)($params['charset'] ?? ''),
                    'disposition' => strtoupper((string)($part->disposition ?? '')),
                    'filename' => $filename,
                    'mime' => $mime,
                    'bytes' => (int)($part->bytes ?? 0),
                    'is_attachment' => strtoupper((string)($part->disposition ?? '')) === 'ATTACHMENT' || $filename !== '',
                ];
            }
            return $out;
        }
        return [[
            'num' => '1',
            'subtype' => strtoupper((string)($struct->subtype ?? '')),
            'encoding' => (int)($struct->encoding ?? 0),
            'charset' => '',
            'disposition' => '',
            'filename' => '',
            'mime' => 'text/' . strtolower((string)($struct->subtype ?? 'plain')),
            'bytes' => (int)($struct->bytes ?? 0),
            'is_attachment' => false,
        ]];
    }

    private function decodeTransfer(string $body, int $encoding): string
    {
        if ($encoding === 3) {
            $d = @imap_base64($body);
            return is_string($d) ? $d : $body;
        }
        if ($encoding === 4) {
            $d = @imap_qprint($body);
            return is_string($d) ? $d : $body;
        }
        return $body;
    }

    /** @return array{html: string, plain: string} */
    private function fetchContent(\IMAP\Connection $mbox, int $msgno): array
    {
        $html = '';
        $plain = '';
        try {
            $struct = @imap_fetchstructure($mbox, $msgno);
            if (!is_object($struct)) {
                return ['html' => '', 'plain' => ''];
            }
            foreach ($this->walkParts($struct) as $p) {
                if (!empty($p['is_attachment'])) {
                    continue;
                }
                $subtype = (string)($p['subtype'] ?? '');
                if ($subtype !== 'HTML' && $subtype !== 'PLAIN') {
                    continue;
                }
                if ($subtype === 'HTML' && $html !== '') {
                    continue;
                }
                if ($subtype === 'PLAIN' && $plain !== '') {
                    continue;
                }
                $raw = @imap_fetchbody($mbox, $msgno, (string)$p['num']);
                if (!is_string($raw) || $raw === '') {
                    continue;
                }
                $text = $this->toUtf8($this->decodeTransfer($raw, (int)$p['encoding']), (string)$p['charset']);
                if ($subtype === 'HTML') {
                    $html = $text;
                } else {
                    $plain = $text;
                }
                if ($html !== '' && $plain !== '') {
                    break;
                }
            }
        } catch (\Throwable $e) {
        }
        return ['html' => $html, 'plain' => $plain];
    }

    /** @return array<int,array{part:string,name:string,mime:string,bytes:int}> */
    private function fetchAttachments(\IMAP\Connection $mbox, int $msgno): array
    {
        $struct = @imap_fetchstructure($mbox, $msgno);
        if (!is_object($struct)) return [];
        $attachments = [];
        foreach ($this->walkParts($struct) as $part) {
            if (empty($part['is_attachment'])) continue;
            $name = trim((string)$part['filename']);
            if ($name === '') $name = 'adjunto-' . (count($attachments) + 1) . '.' . strtolower((string)$part['subtype']);
            $attachments[] = [
                'part' => (string)$part['num'],
                'name' => $name,
                'mime' => (string)$part['mime'],
                'bytes' => (int)$part['bytes'],
            ];
        }
        return $attachments;
    }

    private function previewText(string $plain, string $html): string
    {
        $t = trim($plain);
        if ($t === '' && trim($html) !== '') {
            $t = (string)preg_replace('#<(br|p|div|li|tr)[^>]*>#i', "\n", $html);
            $t = strip_tags($t);
        }
        $t = html_entity_decode($t, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $t = trim((string)preg_replace('/\s+/', ' ', $t));
        return mb_substr($t, 0, 200);
    }

    /** Limpia HTML de emails para mostrarlo aislado (sin scripts ni eventos). */
    public static function sanitizeHtml(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }
        try {
            $doc = new \DOMDocument('1.0', 'UTF-8');
            libxml_use_internal_errors(true);
            $doc->loadHTML('<?xml encoding="utf-8"?>' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
            libxml_clear_errors();
            foreach (['script', 'iframe', 'object', 'embed', 'meta', 'link', 'base', 'title'] as $tag) {
                $nodes = $doc->getElementsByTagName($tag);
                for ($i = $nodes->length - 1; $i >= 0; $i--) {
                    $n = $nodes->item($i);
                    if ($n && $n->parentNode) {
                        $n->parentNode->removeChild($n);
                    }
                }
            }
            $forms = $doc->getElementsByTagName('form');
            for ($i = $forms->length - 1; $i >= 0; $i--) {
                $n = $forms->item($i);
                if ($n && $n->parentNode) {
                    while ($n->firstChild) {
                        $n->parentNode->insertBefore($n->firstChild, $n);
                    }
                    $n->parentNode->removeChild($n);
                }
            }
            $xp = new \DOMXPath($doc);
            foreach ($xp->query('//@*[starts-with(name(), "on")]') as $attr) {
                if ($attr->ownerElement) {
                    $attr->ownerElement->removeAttribute($attr->nodeName);
                }
            }
            foreach ($xp->query('//@href | //@src | //@action | //@background') as $attr) {
                $v = trim((string)$attr->value);
                if (preg_match('#^\s*javascript:#i', $v) || preg_match('#^\s*data:text/html#i', $v)) {
                    if ($attr->ownerElement) {
                        $attr->ownerElement->removeAttribute($attr->nodeName);
                    }
                }
            }
            foreach ($xp->query('//a[@href]') as $a) {
                $a->setAttribute('target', '_blank');
                $a->setAttribute('rel', 'noopener noreferrer');
            }
            $out = $doc->saveHTML();
            return is_string($out) ? $out : '';
        } catch (\Throwable $e) {
            return '';
        }
    }

    private static function normalizeMessageId(string $raw): string
    {
        return mb_substr(trim($raw, " \t<>"), 0, 255);
    }

    public function inbox(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');

        $imapExtension = function_exists('imap_open');
        $emails = [];
        $error = null;
        $folderFilter = (int)($_GET['folder'] ?? 0);

        $folderRepo = new EmailFolderRepo();
        $folders = [];
        $counts = [];
        $labels = [];
        try {
            $folders = $folderRepo->folders();
            $counts = $folderRepo->counts();
        } catch (\Throwable $e) {
        }

        if ($imapExtension) {
            try {
                $mbox = $this->conectar();
                if (!$mbox) {
                    $error = 'No se pudo conectar al servidor IMAP. Verificá las credenciales en .env.';
                } else {
                    $num = imap_num_msg($mbox);
                    for ($i = $num; $i >= max(1, $num - 49); $i--) {
                        $header = @imap_headerinfo($mbox, $i);
                        if (!$header) {
                            continue;
                        }
                        $from = isset($header->from[0])
                            ? trim((string)($header->from[0]->mailbox ?? '') . '@' . trim((string)($header->from[0]->host ?? ''), '@'))
                            : '';
                        $from = trim($from, '@');
                        $content = $this->fetchContent($mbox, $i);
                        $messageId = self::normalizeMessageId((string)($header->message_id ?? ''));
                        $emails[] = [
                            'uid' => $i,
                            'from' => $from,
                            'from_name' => $this->decodeHeader((string)($header->from[0]->personal ?? '')),
                            'subject' => $this->decodeHeader((string)($header->subject ?? '(sin asunto)')),
                            'date' => (string)($header->date ?? ''),
                            'message_id' => $messageId,
                            'body_preview' => $this->previewText($content['plain'], $content['html']),
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

        try {
            $labels = $folderRepo->labelsFor(array_column($emails, 'message_id'));
        } catch (\Throwable $e) {
        }

        try {
            $folderRepo->markSeen(array_column($emails, 'message_id'));
            $folderRepo->touchSeen((int)$adminUser['id']);
        } catch (\Throwable $e) {
        }

        if ($folderFilter > 0) {
            $emails = array_values(array_filter($emails, static fn (array $e) => (int)($labels[$e['message_id'] ?? ''] ?? 0) === $folderFilter));
        }

        echo View::adminPage('admin/email/inbox.php', [
            'adminUser' => $adminUser,
            'emails' => $emails,
            'error' => $error,
            'imapExtension' => $imapExtension,
            'folders' => $folders,
            'counts' => $counts,
            'labels' => $labels,
            'folderFilter' => $folderFilter,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Bandeja de entrada',
        ]);
    }

    public function view(array $params): void
    {
        $auth = new AdminAuthService();
        $adminUser = $auth->requirePermiso('clientes');

        $uid = (int)($params['uid'] ?? 0);
        if ($uid <= 0) {
            Response::redirect('/admin/email');
        }

        $email = null;
        $error = null;
        $cliente = null;
        $sugerida = null;
        $folderId = 0;
        $attachments = [];

        try {
            $mbox = $this->conectar();
            if ($mbox) {
                $header = @imap_headerinfo($mbox, $uid);
                if ($header) {
                    $from = isset($header->from[0])
                        ? trim((string)($header->from[0]->mailbox ?? '') . '@' . trim((string)($header->from[0]->host ?? ''), '@'))
                        : '';
                    $content = $this->fetchContent($mbox, $uid);
                    $attachments = $this->fetchAttachments($mbox, $uid);
                    $messageId = self::normalizeMessageId((string)($header->message_id ?? ''));
                    $email = [
                        'from' => trim($from, '@'),
                        'from_name' => $this->decodeHeader((string)($header->from[0]->personal ?? '')),
                        'subject' => $this->decodeHeader((string)($header->subject ?? '(sin asunto)')),
                        'date' => (string)($header->date ?? ''),
                        'message_id' => $messageId,
                        'body_html' => self::sanitizeHtml($content['html']),
                        'body_plain' => $content['plain'],
                    ];
                }
                imap_close($mbox);
            } else {
                $error = 'No se pudo conectar al servidor IMAP.';
            }
        } catch (\Throwable $e) {
            $error = 'Error: ' . $e->getMessage();
        }

        $folderRepo = new EmailFolderRepo();
        $folders = [];
        try {
            $folders = $folderRepo->folders();
            if ($email && ($email['message_id'] ?? '') !== '') {
                $map = $folderRepo->labelsFor([$email['message_id']]);
                $folderId = (int)($map[$email['message_id']] ?? 0);
                $folderRepo->markSeen([$email['message_id']]);
            }
            if ($email && ($email['from'] ?? '') !== '') {
                $cliente = $folderRepo->matchCliente((string)$email['from']);
                $sugerida = $folderRepo->suggestFolder($cliente);
            }
        } catch (\Throwable $e) {
        }

        echo View::adminPage('admin/email/view.php', [
            'adminUser' => $adminUser,
            'uid' => $uid,
            'email' => $email,
            'attachments' => $attachments,
            'error' => $error,
            'folders' => $folders,
            'folderId' => $folderId,
            'cliente' => $cliente,
            'sugerida' => $sugerida,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Email: ' . ($email['subject'] ?? ''),
        ]);
    }

    private static function sanitizeReplyStyle(string $style): string
    {
        $allowed = [];
        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) continue;
            $property = strtolower(trim($parts[0]));
            $value = trim($parts[1]);
            if ($value === '' || preg_match('/url|expression|@import|[{}]/i', $value)) continue;
            $valid = match ($property) {
                'color', 'background-color' => (bool)preg_match('/^(#[0-9a-f]{3,8}|[a-z]{1,20}|rgba?\([0-9\s.,%]+\))$/i', $value),
                'font-size' => (bool)preg_match('/^(?:[1-9]|[1-6][0-9])(?:px|pt|em|rem|%)$/i', $value),
                'font-family' => (bool)preg_match('/^[a-z0-9 ,"\'-]{1,80}$/i', $value),
                'font-weight' => (bool)preg_match('/^(normal|bold|[1-9]00)$/i', $value),
                'font-style' => in_array(strtolower($value), ['normal', 'italic', 'oblique'], true),
                'text-decoration' => in_array(strtolower($value), ['none', 'underline', 'line-through'], true),
                'text-align' => in_array(strtolower($value), ['left', 'center', 'right', 'justify'], true),
                'line-height' => (bool)preg_match('/^(?:[0-9]+(?:\.[0-9]+)?|[0-9]+(?:px|em|rem|%))$/i', $value),
                default => false,
            };
            if ($valid) $allowed[] = $property . ':' . $value;
        }
        return implode(';', $allowed);
    }

    private static function sanitizeReplyHtml(string $html): string
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?><div id="reply-root">' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        $root = $doc->getElementById('reply-root');
        if (!$root) return '';
        $allowedTags = ['div', 'p', 'br', 'strong', 'b', 'em', 'i', 'u', 'ul', 'ol', 'li', 'a', 'span', 'font', 'h1', 'h2', 'h3', 'blockquote'];
        $removeTags = ['script', 'style', 'iframe', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button', 'textarea'];
        $nodes = [];
        foreach ($root->getElementsByTagName('*') as $node) $nodes[] = $node;
        for ($i = count($nodes) - 1; $i >= 0; $i--) {
            $node = $nodes[$i];
            if (!$node->parentNode) continue;
            $tag = strtolower($node->nodeName);
            if (in_array($tag, $removeTags, true)) {
                $node->parentNode->removeChild($node);
                continue;
            }
            if (!in_array($tag, $allowedTags, true)) {
                while ($node->firstChild) $node->parentNode->insertBefore($node->firstChild, $node);
                $node->parentNode->removeChild($node);
                continue;
            }
            $attributes = [];
            foreach ($node->attributes ?? [] as $attribute) $attributes[] = $attribute->nodeName;
            foreach ($attributes as $name) {
                if ($name === 'style') {
                    $safeStyle = self::sanitizeReplyStyle((string)$node->getAttribute('style'));
                    if ($safeStyle !== '') $node->setAttribute('style', $safeStyle);
                    else $node->removeAttribute('style');
                } elseif ($tag === 'font' && $name === 'color') {
                    $color = trim((string)$node->getAttribute('color'));
                    if (preg_match('/^(#[0-9a-f]{3,8}|[a-z]{1,20})$/i', $color)) {
                        $existingStyle = trim((string)$node->getAttribute('style'));
                        $node->setAttribute('style', self::sanitizeReplyStyle($existingStyle . ';color:' . $color));
                    }
                    $node->removeAttribute('color');
                } elseif ($tag === 'a' && $name === 'href') {
                    $href = trim((string)$node->getAttribute('href'));
                    if (!preg_match('#^(https?://|mailto:)#i', $href)) $node->removeAttribute('href');
                } else {
                    $node->removeAttribute($name);
                }
            }
            if ($tag === 'a' && $node->hasAttribute('href')) {
                $node->setAttribute('target', '_blank');
                $node->setAttribute('rel', 'noopener noreferrer');
            }
        }
        $out = '';
        foreach ($root->childNodes as $child) $out .= $doc->saveHTML($child);
        return $out;
    }

    public function responder(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $msgno = (int)($_POST['uid'] ?? 0);
        $bodyHtml = trim((string)($_POST['body_html'] ?? ''));
        if ($msgno <= 0 || mb_strlen($bodyHtml) > 100000 || trim(strip_tags($bodyHtml)) === '') {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'Escribí el contenido de la respuesta antes de enviarla.'];
            Response::redirect('/admin/email/' . max(1, $msgno));
        }

        $mbox = $this->conectar();
        if (!$mbox) {
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo conectar al servidor IMAP para responder.'];
            Response::redirect('/admin/email/' . $msgno);
        }
        try {
            $header = @imap_headerinfo($mbox, $msgno);
            if (!$header) throw new \RuntimeException('No se encontró el mensaje original.');
            $to = isset($header->from[0])
                ? trim((string)($header->from[0]->mailbox ?? '') . '@' . trim((string)($header->from[0]->host ?? ''), '@'), '@')
                : '';
            $to = filter_var($to, FILTER_VALIDATE_EMAIL) ? $to : '';
            $subject = $this->decodeHeader((string)($header->subject ?? ''));
            $subject = preg_match('/^re\s*:/i', $subject) ? $subject : 'Re: ' . $subject;
            $messageId = self::normalizeMessageId((string)($header->message_id ?? ''));
            $references = preg_replace('/[\r\n]+/', ' ', (string)($header->references ?? '')) ?? '';
            imap_close($mbox);
            if ($to === '') throw new \RuntimeException('El remitente del mensaje no tiene una dirección válida.');

            $safeHtml = self::sanitizeReplyHtml($bodyHtml);
            if (trim(strip_tags($safeHtml)) === '') throw new \RuntimeException('La respuesta quedó vacía luego de quitar contenido no permitido.');
            $plain = trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>', '</div>', '</li>'], "\n", $safeHtml)), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
            $threadHeaders = [];
            if ($messageId !== '' && !preg_match('/[\r\n<>]/', $messageId)) {
                $threadHeaders['In-Reply-To'] = '<' . $messageId . '>';
                $threadHeaders['References'] = trim($references . ' <' . $messageId . '>');
            }
            (new \Perfushopping\Web\Infra\SmtpMailer())->send($to, $subject, $safeHtml, $plain, [], $threadHeaders);
            $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Respuesta enviada a ' . $to . '.'];
        } catch (\Throwable $e) {
            if (isset($mbox) && $mbox instanceof \IMAP\Connection) @imap_close($mbox);
            $_SESSION['admin_flash'] = ['type' => 'danger', 'text' => 'No se pudo enviar la respuesta: ' . $e->getMessage()];
        }
        Response::redirect('/admin/email/' . $msgno);
    }

    public function adjunto(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        $msgno = (int)($params['uid'] ?? 0);
        $partNo = trim((string)($_GET['part'] ?? ''));
        if ($msgno <= 0 || !preg_match('/^\d+(?:\.\d+)*$/', $partNo) || !function_exists('imap_open')) {
            Response::notFound();
            return;
        }
        $mbox = $this->conectar();
        if (!$mbox) {
            Response::notFound();
            return;
        }
        $payload = null;
        try {
            $struct = @imap_fetchstructure($mbox, $msgno);
            if (is_object($struct)) {
                foreach ($this->walkParts($struct) as $part) {
                    if ((string)$part['num'] !== $partNo || empty($part['is_attachment'])) continue;
                    $raw = @imap_fetchbody($mbox, $msgno, $partNo);
                    if (is_string($raw)) {
                        $payload = [
                            'content' => $this->decodeTransfer($raw, (int)$part['encoding']),
                            'name' => (string)$part['filename'],
                            'mime' => (string)$part['mime'],
                        ];
                    }
                    break;
                }
            }
        } catch (\Throwable $e) {
        }
        imap_close($mbox);
        if (!$payload) {
            Response::notFound();
            return;
        }
        $name = basename(str_replace('\\', '/', (string)$payload['name']));
        $name = trim(str_replace(["\r", "\n", '"'], ['', '', '_'], $name));
        if ($name === '') $name = 'adjunto';
        $mime = (string)$payload['mime'];
        if (!preg_match('#^[a-z0-9.+-]+/[a-z0-9.+-]+$#i', $mime)) $mime = 'application/octet-stream';
        $inline = ($_GET['inline'] ?? '') === '1' && in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'application/pdf'], true);
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?: 'adjunto';
        header('Content-Type: ' . $mime);
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($name));
        header('Content-Length: ' . strlen((string)$payload['content']));
        echo $payload['content'];
    }

    /** Chequeo liviano para el badge (cabeceras de los últimos 30, sin cuerpos). */
    public function nuevos(array $params): void
    {
        $auth = new AdminAuthService();
        $u = $auth->user();
        if (!$u || !$auth->checkPermiso('clientes')) {
            Response::json(['nuevos' => 0]);
            return;
        }
        $adminId = (int)$u['id'];
        $repo = new EmailFolderRepo();
        try {
            if (function_exists('imap_open')) {
                $mbox = $this->conectar();
                if ($mbox) {
                    $num = imap_num_msg($mbox);
                    $ids = [];
                    for ($i = $num; $i >= max(1, $num - 29); $i--) {
                        $header = @imap_headerinfo($mbox, $i);
                        if (!$header) {
                            continue;
                        }
                        $mid = self::normalizeMessageId((string)($header->message_id ?? ''));
                        if ($mid !== '') {
                            $ids[] = $mid;
                        }
                    }
                    imap_close($mbox);
                    $repo->markSeen($ids);
                }
            }
            $since = $repo->lastSeen($adminId);
            if ($since === null) {
                $repo->touchSeen($adminId);
                Response::json(['nuevos' => 0]);
                return;
            }
            Response::json(['nuevos' => $repo->countNew($since)]);
        } catch (\Throwable $e) {
            Response::json(['nuevos' => 0]);
        }
    }

    public function crearCarpeta(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        if ($nombre !== '') {
            (new EmailFolderRepo())->createFolder($nombre);
        }
        Response::redirect('/admin/email');
    }

    public function mover(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $messageId = trim((string)($_POST['message_id'] ?? ''));
        $folderId = (int)($_POST['folder_id'] ?? 0);
        if ($messageId !== '' && $folderId > 0) {
            (new EmailFolderRepo())->assign($messageId, $folderId);
        }
        $back = '/admin/email';
        $folder = (int)($_POST['folder'] ?? 0);
        if ($folder > 0) {
            $back .= '?folder=' . $folder;
        }
        Response::redirect($back);
    }

    public function quitar(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $messageId = trim((string)($_POST['message_id'] ?? ''));
        if ($messageId !== '') {
            (new EmailFolderRepo())->unassign($messageId);
        }
        $back = '/admin/email';
        $folder = (int)($_POST['folder'] ?? 0);
        if ($folder > 0) {
            $back .= '?folder=' . $folder;
        }
        Response::redirect($back);
    }

    public function moverGrupo(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $ids = (array)($_POST['message_ids'] ?? []);
        $folderId = (int)($_POST['folder_id'] ?? 0);
        $n = 0;
        if ($ids && $folderId > 0) {
            $repo = new EmailFolderRepo();
            foreach ($ids as $mid) {
                $mid = trim((string)$mid);
                if ($mid === '') {
                    continue;
                }
                if ($repo->assign($mid, $folderId)) {
                    $n++;
                }
            }
        }
        $_SESSION['admin_flash'] = ['type' => 'ok', 'text' => 'Movidos a carpeta: ' . $n . '.'];
        $back = '/admin/email';
        $folder = (int)($_POST['folder'] ?? 0);
        if ($folder > 0) {
            $back .= '?folder=' . $folder;
        }
        Response::redirect($back);
    }

    public function eliminar(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $msgnos = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['msgnos'] ?? [])))));
        $n = 0;
        if ($msgnos && function_exists('imap_open')) {
            try {
                $mbox = $this->conectar();
                if ($mbox) {
                    $repo = new EmailFolderRepo();
                    foreach ($msgnos as $msgno) {
                        if ($msgno <= 0) {
                            continue;
                        }
                        $header = @imap_headerinfo($mbox, $msgno);
                        if ($header) {
                            $mid = self::normalizeMessageId((string)($header->message_id ?? ''));
                            if ($mid !== '') {
                                $repo->unassign($mid);
                            }
                        }
                        if (@imap_delete($mbox, $msgno)) {
                            $n++;
                        }
                    }
                    @imap_expunge($mbox);
                    imap_close($mbox);
                }
            } catch (\Throwable $e) {
            }
        }
        $_SESSION['admin_flash'] = ['type' => $n > 0 ? 'ok' : 'warning', 'text' => $n > 0 ? ('Eliminados: ' . $n . '.') : 'No se eliminó ningún mensaje.'];
        $back = '/admin/email';
        $folder = (int)($_POST['folder'] ?? 0);
        if ($folder > 0) {
            $back .= '?folder=' . $folder;
        }
        Response::redirect($back);
    }
}
