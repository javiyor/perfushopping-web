<?php
declare(strict_types=1);

// Simple webservice for VFP to upsert gustos and images.
// Auth: POST token=<UPLOAD_TOKEN or SYNC_TOKEN>
// Modes:
// - JSON body (recommended): {"gustos":[...],"images":[...]}
// - Form lote (legacy): POST lote_gustos="idcodgusto;idprodu;codgusto;nomgusto;codscan;stockact;discont;rutaimg\n..."
//                     POST lote_imagenes="idprodu;idcodgusto;rutaimg;old_rutaimg\n..."

require __DIR__ . '/../../src/bootstrap.php';

use Perfushopping\Web\Repo\SyncRepo;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Response;

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

$raw = file_get_contents('php://input');
$json = null;
if (is_string($raw) && trim($raw) !== '' && str_starts_with(ltrim($raw), '{')) {
    $json = json_decode($raw, true);
    if (!is_array($json)) {
        Response::json(['ok' => false, 'error' => 'Invalid JSON'], 400);
        exit;
    }
}

$gustos = [];
$images = [];

if (is_array($json)) {
    $gustos = $json['gustos'] ?? [];
    $images = $json['images'] ?? [];
    if (!is_array($gustos) || !is_array($images)) {
        Response::json(['ok' => false, 'error' => 'gustos/images must be arrays'], 400);
        exit;
    }
} else {
    $lG = isset($_POST['lote_gustos']) && is_string($_POST['lote_gustos']) ? trim($_POST['lote_gustos']) : '';
    $lI = isset($_POST['lote_imagenes']) && is_string($_POST['lote_imagenes']) ? trim($_POST['lote_imagenes']) : '';

    if ($lG !== '') {
        $lG = str_replace(["\r\n", "\r"], "\n", $lG);
        foreach (explode("\n", $lG) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $p = array_map('trim', explode(';', $line));
            $gustos[] = [
                'idcodgusto' => (int)($p[0] ?? 0),
                'idprodu' => (int)($p[1] ?? 0),
                'codgusto' => ($p[2] ?? '') !== '' ? (string)$p[2] : null,
                'nomgusto' => (string)($p[3] ?? ''),
                'codscan' => ($p[4] ?? '') !== '' ? (string)$p[4] : null,
                'stockact' => (float)str_replace(',', '.', (string)($p[5] ?? '0')),
                'discont' => (int)($p[6] ?? 0),
                'rutaimg' => (string)($p[7] ?? ''),
            ];
        }
    }
    if ($lI !== '') {
        $lI = str_replace(["\r\n", "\r"], "\n", $lI);
        foreach (explode("\n", $lI) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') {
                continue;
            }
            $p = array_map('trim', explode(';', $line));
            $images[] = [
                'idprodu' => (int)($p[0] ?? 0),
                'idcodgusto' => (int)($p[1] ?? 0),
                'rutaimg' => (string)($p[2] ?? ''),
                'old_rutaimg' => (string)($p[3] ?? ''),
            ];
        }
    }
}

// Validate minimal payload
if (!$gustos && !$images) {
    Response::json(['ok' => false, 'error' => 'No data'], 400);
    exit;
}

$repo = new SyncRepo();
try {
    $result = $repo->sync([], $gustos, $images);
    Response::json(['ok' => true] + $result, 200);
} catch (Throwable $e) {
    $repo->log('gustos_sync_error: ' . $e->getMessage());
    Response::json(['ok' => false, 'error' => 'Server error'], 500);
}
