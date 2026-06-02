<?php
declare(strict_types=1);

// Multipart upload endpoint compatible with VFP curl usage.
// POST fields:
// - token: shared secret (UPLOAD_TOKEN or SYNC_TOKEN)
// - imagen: file
// - nombre (optional): desired filename (e.g. armani-code-men.jpg)
// Optional DB registration for gusto images:
// - idprodu (int)
// - idcodgusto (int)
// - idimagen (int, optional) to replace by id
// - old_rutaimg (string, optional) to replace when idimagen is unknown

require __DIR__ . '/../../src/bootstrap.php';

use Perfushopping\Web\Infra\Db;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

function norm_filename(string $v): string
{
    $v = trim($v);
    if ($v === '' || $v === '*') {
        return '';
    }
    $v = str_replace('\\', '/', $v);
    $v = basename($v);
    $v = trim($v, "\"' ");
    $v = preg_replace('/\s+/', '-', $v) ?? $v;
    $v = mb_strtolower($v);
    $v = str_replace(['á','é','í','ó','ú','ñ','ü'], ['a','e','i','o','u','n','u'], $v);
    return $v;
}

function allowed_ext(string $name): bool
{
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

// Optional: register as gusto image in DB
$idprodu = isset($_POST['idprodu']) ? (int)$_POST['idprodu'] : 0;
$idcodgusto = isset($_POST['idcodgusto']) ? (int)$_POST['idcodgusto'] : 0;
$idimagen = isset($_POST['idimagen']) ? (int)$_POST['idimagen'] : 0;
$oldRuta = isset($_POST['old_rutaimg']) && is_string($_POST['old_rutaimg']) ? norm_filename($_POST['old_rutaimg']) : '';

$dbRow = null;
if ($idprodu > 0 && $idcodgusto > 0) {
    $pdo = Db::pdo();

    // Replace by idimagen
    if ($idimagen > 0) {
        $stu = $pdo->prepare('UPDATE imagen SET rutaimg=:r, idprodu=:p, idcodgusto=:g WHERE idimagen=:i');
        $stu->execute([':r' => $filename, ':p' => $idprodu, ':g' => $idcodgusto, ':i' => $idimagen]);
        if ($stu->rowCount() > 0) {
            $dbRow = ['idimagen' => $idimagen, 'idprodu' => $idprodu, 'idcodgusto' => $idcodgusto, 'rutaimg' => $filename];
        }
    }

    // Replace by old rutaimg
    if ($dbRow === null && $oldRuta !== '') {
        $stu = $pdo->prepare('UPDATE imagen SET rutaimg=:new WHERE idprodu=:p AND idcodgusto=:g AND rutaimg=:old');
        $stu->execute([':new' => $filename, ':p' => $idprodu, ':g' => $idcodgusto, ':old' => $oldRuta]);
        if ($stu->rowCount() > 0) {
            $dbRow = ['idimagen' => null, 'idprodu' => $idprodu, 'idcodgusto' => $idcodgusto, 'rutaimg' => $filename, 'replaced' => $oldRuta];
        }
    }

    // Insert new (max 6 per gusto)
    if ($dbRow === null) {
        $stc = $pdo->prepare('SELECT COUNT(*) FROM imagen WHERE idcodgusto=:g');
        $stc->execute([':g' => $idcodgusto]);
        $cnt = (int)$stc->fetchColumn();
        if ($cnt < 6) {
            // Avoid duplicates
            $st = $pdo->prepare('SELECT idimagen FROM imagen WHERE idprodu=:p AND idcodgusto=:g AND rutaimg=:r LIMIT 1');
            $st->execute([':p' => $idprodu, ':g' => $idcodgusto, ':r' => $filename]);
            $exists = (int)($st->fetchColumn() ?: 0);
            if ($exists <= 0) {
                $sti = $pdo->prepare('INSERT INTO imagen (rutaimg, idprodu, idcodgusto) VALUES (:r,:p,:g)');
                $sti->execute([':r' => $filename, ':p' => $idprodu, ':g' => $idcodgusto]);
                $newId = (int)$pdo->lastInsertId();
                $dbRow = ['idimagen' => $newId, 'idprodu' => $idprodu, 'idcodgusto' => $idcodgusto, 'rutaimg' => $filename];
            } else {
                $dbRow = ['idimagen' => $exists, 'idprodu' => $idprodu, 'idcodgusto' => $idcodgusto, 'rutaimg' => $filename, 'dedup' => true];
            }
        } else {
            $dbRow = ['skipped' => 'max_6', 'idprodu' => $idprodu, 'idcodgusto' => $idcodgusto, 'rutaimg' => $filename];
        }
    }
}

Response::json([
    'ok' => true,
    'filename' => $filename,
    'url' => '/upload/' . rawurlencode($filename),
    'db' => $dbRow,
], 200);
