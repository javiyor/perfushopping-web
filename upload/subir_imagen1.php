<?php
declare(strict_types=1);

// Multipart upload endpoint compatible with VFP curl usage.
// POST fields:
// - token: shared secret (UPLOAD_TOKEN or SYNC_TOKEN)
// - imagen: file
// - nombre (optional): desired filename (e.g. armani-code-men.jpg)

require __DIR__ . '/../../src/bootstrap.php';

use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

function norm_filename(string $v): string {
    $v = trim($v);
    $v = str_replace('\\', '/', $v);
    $v = basename($v);
    $v = trim($v, "\"' ");
    $v = preg_replace('/\s+/', '-', $v) ?? $v;
    $v = mb_strtolower($v);
    $v = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $v);
    return $v;
}

function allowed_ext(string $name): bool {
    if ($name === '' || str_contains($name, '..') || str_contains($name, '/')) {
        return false;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
}

$uploadToken = Env::get('UPLOAD_TOKEN', '');
$syncToken = Env::get('SYNC_TOKEN', '');
$token = $uploadToken !== '' ? $uploadToken : $syncToken;
if ($token === '') {
    Response::json(['ok' => false, 'error' => 'UPLOAD_TOKEN/SYNC_TOKEN not configured'], 500);
    exit;
}

$provided = isset($_POST['token']) && is_string($_POST['token']) ? trim($_POST['token']) : '';
if ($provided === '' || !hash_equals($token, $provided)) {
    Response::json(['ok' => false, 'error' => 'Unauthorized'], 401);
    exit;
}

if (!isset($_FILES['imagen']) || !is_array($_FILES['imagen'])) {
    Response::json(['ok' => false, 'error' => 'Missing file field imagen'], 400);
    exit;
}

$f = $_FILES['imagen'];
if (($f['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    Response::json(['ok' => false, 'error' => 'Upload error'], 400);
    exit;
}

$origName = isset($f['name']) && is_string($f['name']) ? $f['name'] : '';
$desired = isset($_POST['nombre']) && is_string($_POST['nombre']) ? $_POST['nombre'] : '';
$filename = norm_filename($desired !== '' ? $desired : $origName);

if (!allowed_ext($filename)) {
    Response::json(['ok' => false, 'error' => 'Invalid filename or extension'], 400);
    exit;
}

$tmp = isset($f['tmp_name']) && is_string($f['tmp_name']) ? $f['tmp_name'] : '';
if ($tmp === '' || !is_uploaded_file($tmp)) {
    Response::json(['ok' => false, 'error' => 'Invalid upload'], 400);
    exit;
}

if ((int)($f['size'] ?? 0) > 8 * 1024 * 1024) {
    Response::json(['ok' => false, 'error' => 'File too large (max 8MB)'], 400);
    exit;
}

$dir = __DIR__;
if (!is_dir($dir) || !is_writable($dir)) {
    Response::json(['ok' => false, 'error' => 'Upload directory not writable'], 500);
    exit;
}

$dest = rtrim($dir, '/\\') . DIRECTORY_SEPARATOR . $filename;
$tmpDest = $dest . '.tmp_' . bin2hex(random_bytes(6));

if (!move_uploaded_file($tmp, $tmpDest)) {
    Response::json(['ok' => false, 'error' => 'Move failed'], 500);
    exit;
}
@chmod($tmpDest, 0644);
if (!@rename($tmpDest, $dest)) {
    @unlink($tmpDest);
    Response::json(['ok' => false, 'error' => 'Finalize failed'], 500);
    exit;
}

Response::json([
    'ok' => true,
    'filename' => $filename,
    'url' => '/upload/' . rawurlencode($filename),
], 200);
