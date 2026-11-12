<?php
declare(strict_types=1);

namespace Perfushopping\Web\Admin;

use Perfushopping\Web\Repo\EmailFolderRepo;
use Perfushopping\Web\Repo\EmailMarketingRepo;
use Perfushopping\Web\Repo\CustomerRepo;
use Perfushopping\Web\Service\AdminAuthService;
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;
use Perfushopping\Web\Support\UploadValidator;
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

    private function publicBaseUrl(): string
    {
        $url = rtrim(trim((string)Env::get('APP_URL', 'https://perfushopping.ar')), '/');
        return preg_match('#^https?://[A-Za-z0-9.-]+(?::\d+)?$#', $url) ? $url : 'https://perfushopping.ar';
    }

    public function publicidad(array $params): void
    {
        $adminUser = (new AdminAuthService())->requirePermiso('clientes');
        $repo = new CustomerRepo();
        $q = trim((string)($_GET['q'] ?? ''));
        $city = trim((string)($_GET['city'] ?? ''));
        $category = trim((string)($_GET['category'] ?? ''));
        $page = max(1, (int)($_GET['page'] ?? 1));
        $perPage = (int)($_GET['per_page'] ?? 200);
        if (!in_array($perPage, [50, 100, 200], true)) $perPage = 200;
        $total = $repo->countMarketingContacts($q, $city, $category);
        $pages = max(1, (int)ceil($total / $perPage));
        $page = min($page, $pages);
        $contacts = $repo->marketingContacts($q, $city, $category, $perPage, ($page - 1) * $perPage);
        $unsubscribed = (new EmailMarketingRepo())->unsubscribeMap(array_column($contacts, 'email'));
        foreach ($contacts as &$contact) {
            $contact['unsubscribed'] = isset($unsubscribed[mb_strtolower(trim((string)($contact['email'] ?? '')))]);
        }
        unset($contact);
        echo View::adminPage('admin/email/publicidad.php', [
            'adminUser' => $adminUser,
            'contacts' => $contacts,
            'total' => $total,
            'q' => $q,
            'city' => $city,
            'category' => $category,
            'cities' => $repo->ciudadesFiltro(),
            'categories' => $repo->categoriasFiltro(),
            'page' => $page,
            'pages' => $pages,
            'perPage' => $perPage,
            'csrf' => Csrf::token(),
            'pageTitle' => 'Envío publicitario',
        ]);
    }

    public function enviarPublicidad(array $params): void
    {
        (new AdminAuthService())->requirePermiso('clientes');
        Csrf::check($_POST['_csrf'] ?? null);
        $keys = array_slice(array_values(array_unique(array_map('strval', (array)($_POST['contact_keys'] ?? [])))), 0, 25);
        $subject = trim((string)($_POST['subject'] ?? ''));
        $body = trim((string)($_POST['body'] ?? ''));
        $includeUnsubscribe = in_array((string)($_POST['include_unsubscribe'] ?? ''), ['1', 'on', 'true'], true);
        if (!$keys || $subject === '' || $body === '' || mb_strlen($subject) > 200 || mb_strlen($body) > 30000) {
            Response::json(['ok' => false, 'error' => 'Seleccioná contactos e ingresá asunto y mensaje. Cada tanda admite hasta 25 contactos.'], 422);
            return;
        }

        $attachments = [];
        $inlineImageHtml = '';
        $inlineImagePlain = '';
        $attachedPdfHtml = '';
        $attachedPdfPlain = '';
        try {
            $imageUpload = $_FILES['inline_images'] ?? ($_FILES['inline_image'] ?? null);
            $imageFiles = [];
            if (is_array($imageUpload) && isset($imageUpload['name']) && is_array($imageUpload['name'])) {
                foreach (array_keys($imageUpload['name']) as $index) {
                    $imageFiles[] = [
                        'name' => $imageUpload['name'][$index] ?? '',
                        'type' => $imageUpload['type'][$index] ?? '',
                        'tmp_name' => $imageUpload['tmp_name'][$index] ?? '',
                        'error' => $imageUpload['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                        'size' => $imageUpload['size'][$index] ?? 0,
                    ];
                }
            } elseif (is_array($imageUpload)) {
                $imageFiles[] = $imageUpload;
            }
            $imageFiles = array_values(array_filter($imageFiles, static fn(array $file): bool => (int)($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE));
            if (count($imageFiles) > 4) throw new \RuntimeException('Podés insertar hasta 4 imágenes en cada envío.');
            $totalImageBytes = 0;
            foreach ($imageFiles as $index => $image) {
                UploadValidator::validate($image);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, (string)$image['tmp_name']) : '';
                if ($finfo) finfo_close($finfo);
                if (!in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true)) {
                    throw new \RuntimeException('Las imágenes deben ser JPG, PNG o WEBP.');
                }
                $content = file_get_contents((string)$image['tmp_name']);
                if (!is_string($content) || $content === '') throw new \RuntimeException('No se pudo leer una de las imágenes.');
                $totalImageBytes += strlen($content);
                if ($totalImageBytes > 10 * 1024 * 1024) throw new \RuntimeException('El peso total de las imágenes no puede superar 10 MB.');
                $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime];
                $contentId = 'ad-image-' . bin2hex(random_bytes(12));
                $attachments[] = [
                    'name' => 'imagen-publicitaria-' . ($index + 1) . '.' . $extension,
                    'content' => $content,
                    'mime' => $mime,
                    'inline' => true,
                    'content_id' => $contentId,
                ];
                $inlineImageHtml .= '<div style="margin:20px 0;text-align:center"><img src="cid:' . $contentId . '" alt="Imagen de Perfushopping" style="display:inline-block;max-width:100%;height:auto;border:0"></div>';
            }
            if ($imageFiles) {
                $inlineImagePlain = "\n\n[" . count($imageFiles) . " imagen(es) promocional(es) incluidas en el correo]";
            }

            $pdf = $_FILES['attached_pdf'] ?? null;
            if (is_array($pdf) && (int)($pdf['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                UploadValidator::validate($pdf);
                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime = $finfo ? finfo_file($finfo, (string)$pdf['tmp_name']) : '';
                if ($finfo) finfo_close($finfo);
                if ($mime !== 'application/pdf') throw new \RuntimeException('El archivo adjunto debe ser un PDF.');
                $content = file_get_contents((string)$pdf['tmp_name']);
                if (!is_string($content) || $content === '') throw new \RuntimeException('No se pudo leer el PDF seleccionado.');
                $attachments[] = ['name' => 'archivo-publicitario.pdf', 'content' => $content, 'mime' => 'application/pdf'];
                $attachedPdfHtml = '<p style="font:13px Arial,sans-serif;color:#555">Adjuntamos un archivo PDF con más información.</p>';
                $attachedPdfPlain = "\n\nSe adjunta un archivo PDF.";
            }
        } catch (\Throwable $e) {
            Response::json(['ok' => false, 'error' => $e->getMessage()], 422);
            return;
        }

        $contacts = (new CustomerRepo())->marketingContactsByKeys($keys);
        $marketingRepo = new EmailMarketingRepo();
        $unsubscribed = $marketingRepo->unsubscribeMap(array_column($contacts, 'email'));
        $mailer = new \Perfushopping\Web\Infra\SmtpMailer();
        $baseUrl = $this->publicBaseUrl();
        $siteUrl = 'https://www.perfushopping.com.ar';
        $whatsAppUrl = 'https://wa.me/543482765798';
        $replyAddress = trim((string)Env::get('MAIL_FROM', Env::get('SMTP_USER', 'clientes@perfushopping.com.ar')));
        if (!filter_var($replyAddress, FILTER_VALIDATE_EMAIL)) $replyAddress = 'clientes@perfushopping.com.ar';
        $replyUrl = 'mailto:' . rawurlencode($replyAddress) . '?subject=' . rawurlencode('Re: ' . $subject);
        $sent = 0;
        $skipped = 0;
        $errors = [];
        $seen = [];
        foreach ($contacts as $contact) {
            $to = mb_strtolower(trim((string)($contact['email'] ?? '')));
            if (!filter_var($to, FILTER_VALIDATE_EMAIL) || isset($seen[$to]) || isset($unsubscribed[$to])) {
                $skipped++;
                continue;
            }
            $seen[$to] = true;
            try {
                $html = '<div style="font-family:Arial,sans-serif;line-height:1.6">' . nl2br(htmlspecialchars($body, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</div>'
                    . $inlineImageHtml . $attachedPdfHtml;
                $html .= '<hr style="border:0;border-top:1px solid #ddd;margin:24px 0 14px">'
                    . '<p style="font:14px Arial,sans-serif;line-height:1.7">Visitá nuestra web: <a href="' . $siteUrl . '">www.perfushopping.com.ar</a><br>'
                    . 'WhatsApp: <a href="' . $whatsAppUrl . '">3482 765798</a></p>'
                    . '<p style="font:14px Arial,sans-serif;line-height:1.6">Si te gusta, te lo podemos enviar donde estés… estamos para servirte.</p>'
                    . '<p style="margin:18px 0"><a href="' . htmlspecialchars($replyUrl, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '" style="display:inline-block;padding:11px 22px;border-radius:6px;background:#b13d68;color:#fff;text-decoration:none;font-weight:bold">Responder</a></p>';
                $plain = $body . $inlineImagePlain . $attachedPdfPlain
                    . "\n\nVisitá nuestra web: www.perfushopping.com.ar"
                    . "\nWhatsApp: 3482 765798 (" . $whatsAppUrl . ')'
                    . "\nSi te gusta, te lo podemos enviar donde estés… estamos para servirte."
                    . "\nResponder: " . $replyUrl;
                $headers = [];
                if ($includeUnsubscribe) {
                    $token = $marketingRepo->tokenForEmail($to);
                    if ($token === null) {
                        $skipped++;
                        continue;
                    }
                    $url = $baseUrl . '/email/desuscribir?token=' . rawurlencode($token);
                    $html .= '<hr><p style="font-size:12px;color:#666">Si no querés recibir más mensajes publicitarios, <a href="'
                        . htmlspecialchars($url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '">podés darte de baja de la lista aquí</a>.</p>';
                    $plain .= "\n\nSi no querés recibir más mensajes publicitarios, podés darte de baja aquí: " . $url;
                    $headers = [
                        'List-Unsubscribe' => '<' . $url . '>',
                        'List-Unsubscribe-Post' => 'List-Unsubscribe=One-Click',
                    ];
                }
                $mailer->send($to, $subject, $html, $plain, $attachments, $headers);
                $sent++;
            } catch (\Throwable $e) {
                $errors[] = ['email' => $to, 'error' => mb_substr($e->getMessage(), 0, 180)];
            }
        }
        Response::json(['ok' => true, 'sent' => $sent, 'skipped' => $skipped, 'errors' => $errors]);
    }

    public function desuscribir(array $params): void
    {
        $token = trim((string)($_GET['token'] ?? $_POST['token'] ?? ''));
        $repo = new EmailMarketingRepo();
        $email = $repo->emailByToken($token);
        if ($email === null) {
            Response::html('<!doctype html><html lang="es"><meta charset="utf-8"><title>Enlace inválido</title><body><h1>Enlace inválido</h1><p>No se encontró este enlace de baja.</p></body></html>', 404);
            return;
        }
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            $repo->unsubscribe($token);
            $message = '<h1>Listo, te dimos de baja.</h1><p>No enviaremos más mensajes publicitarios a ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.</p>';
        } else {
            $already = isset($repo->unsubscribeMap([$email])[mb_strtolower($email)]);
            $message = $already
                ? '<h1>Ya estabas fuera de la lista.</h1><p>No enviaremos más mensajes publicitarios a ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.</p>'
                : '<h1>Dejar de recibir publicidad</h1><p>Confirmá la baja de ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '.</p><form method="post"><input type="hidden" name="token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '"><button type="submit">Darme de baja</button></form>';
        }
        Response::html('<!doctype html><html lang="es"><meta charset="utf-8"><meta name="viewport" content="width=device-width"><title>Baja de publicidad</title><body style="font:16px Arial,sans-serif;max-width:640px;margin:12vh auto;padding:24px;color:#222">' . $message . '</body></html>');
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
