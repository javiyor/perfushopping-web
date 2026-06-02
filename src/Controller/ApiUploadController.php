<?php
declare(strict_types=1);

namespace Perfushopping\Web\Controller;

use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

final class ApiUploadController
{
    /**
     * Upload an image via JSON base64.
     * Body: {"filename":"armani-code-men.jpg","content_base64":"..."}
     */
    public function uploadBase64(array $params): void
    {
        $token = Env::get('SYNC_TOKEN', '');
        if ($token === '') {
            Response::json(['ok' => false, 'error' => 'SYNC_TOKEN not configured'], 500);
            return;
        }

        $provided = $this->getToken();
        if ($provided === '' || !hash_equals($token, $provided)) {
            Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
            return;
        }

        $raw = file_get_contents('php://input');
        if ($raw === false || trim($raw) === '') {
            Response::json(['ok' => false, 'error' => 'Empty body'], 400);
            return;
        }
        $json = json_decode($raw, true);
        if (!is_array($json)) {
            Response::json(['ok' => false, 'error' => 'Invalid JSON'], 400);
            return;
        }

        $filename = isset($json['filename']) && is_string($json['filename']) ? trim($json['filename']) : '';
        $b64 = isset($json['content_base64']) && is_string($json['content_base64']) ? trim($json['content_base64']) : '';
        if ($filename === '' || $b64 === '') {
            Response::json(['ok' => false, 'error' => 'Missing filename/content_base64'], 400);
            return;
        }

        $filename = $this->normalizeFilename($filename);
        if (!$this->isAllowedFilename($filename)) {
            Response::json(['ok' => false, 'error' => 'Invalid filename or extension'], 400);
            return;
        }

        $bin = base64_decode($b64, true);
        if ($bin === false) {
            Response::json(['ok' => false, 'error' => 'Invalid base64'], 400);
            return;
        }
        if (strlen($bin) > 8 * 1024 * 1024) {
            Response::json(['ok' => false, 'error' => 'File too large (max 8MB)'], 400);
            return;
        }

        $dir = $this->resolveUploadDir();
        if ($dir === '') {
            Response::json(['ok' => false, 'error' => 'Upload directory not found'], 500);
            return;
        }
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        if (!is_dir($dir) || !is_writable($dir)) {
            Response::json(['ok' => false, 'error' => 'Upload directory not writable'], 500);
            return;
        }

        $path = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
        $tmp = $path . '.tmp_' . bin2hex(random_bytes(6));
        if (file_put_contents($tmp, $bin) === false) {
            Response::json(['ok' => false, 'error' => 'Write failed'], 500);
            return;
        }
        @chmod($tmp, 0644);
        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            Response::json(['ok' => false, 'error' => 'Move failed'], 500);
            return;
        }

        Response::json([
            'ok' => true,
            'filename' => $filename,
            'url' => '/upload/' . rawurlencode($filename),
        ], 200);
    }

    private function resolveUploadDir(): string
    {
        $base = defined('APP_BASE_DIR') ? (string)APP_BASE_DIR : (string)realpath(__DIR__ . '/../..');
        $candidates = [
            rtrim($base, '/\\') . '/public_html/upload',
            rtrim($base, '/\\') . '/public/upload',
            rtrim($base, '/\\') . '/upload',
        ];
        foreach ($candidates as $c) {
            if (is_dir($c) || @mkdir($c, 0775, true)) {
                return $c;
            }
        }
        return '';
    }

    private function isAllowedFilename(string $name): bool
    {
        if ($name === '' || str_contains($name, '..') || str_contains($name, '/')) {
            return false;
        }
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
    }

    private function normalizeFilename(string $v): string
    {
        $v = trim($v);
        $v = str_replace('\\', '/', $v);
        $v = basename($v);
        $v = trim($v, "\"' ");
        $v = preg_replace('/\s+/', '-', $v) ?? $v;
        $v = mb_strtolower($v);
        $v = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $v);
        return $v;
    }

    private function getToken(): string
    {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (is_string($auth) && stripos($auth, 'bearer ') === 0) {
            return trim(substr($auth, 7));
        }
        $t = $_SERVER['HTTP_X_SYNC_TOKEN'] ?? '';
        if (is_string($t)) {
            return trim($t);
        }
        return '';
    }
}
