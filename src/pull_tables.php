<?php
/**
 * Pull tables desde la API local de la PC de oficina hacia Hostinger
 *
 * USO:
 *   php src/pull_tables.php --url=http://181.105.89.90:8080/sync/local_api.php --token=Ngf75_Jry73_
 *
 * Opcional:
 *   --url=https://MIDOMINIO.sytes.net:8080/sync/local_api.php
 *   --chunk=500
 */

declare(strict_types=1);

// ── Parse args ──
$args = [];
foreach ($argv ?? [] as $a) {
    if (preg_match('/^--(\w+)=(.+)$/', $a, $m)) {
        $args[$m[1]] = $m[2];
    }
}

$apiUrl  = $args['url'] ?? '';
$token   = $args['token'] ?? getenv('SYNC_TOKEN') ?? '';
$chunk   = max(50, min(2000, (int)($args['chunk'] ?? 500)));

if ($apiUrl === '') {
    echo "[ERROR] Usá --url=http://IP:8080/sync/local_api.php\n";
    exit(1);
}
if ($token === '') {
    echo "[ERROR] Usá --token=TOKEN o setéá SYNC_TOKEN\n";
    exit(1);
}

$log = function (string $msg): void {
    echo $msg . "\n";
};

// ── Helper: fetch tabla desde API local ──
function fetchLocal(string $apiUrl, string $token, string $table): ?array
{
    $url = $apiUrl . '?token=' . urlencode($token) . '&table=' . $table;
    $response = @file_get_contents($url, false, stream_context_create([
        'http' => ['timeout' => 300, 'ignore_errors' => true],
    ]));
    if ($response === false) {
        throw new \RuntimeException("Error HTTP al conectar con {$url}");
    }
    $json = json_decode($response, true);
    if (!is_array($json) || !($json['ok'] ?? false)) {
        $err = $json['error'] ?? $response;
        throw new \RuntimeException("API error: {$err}");
    }
    return $json['data'][$table] ?? null;
}

// ── Helper: POST chunk a sync-tables ──
function postChunk(string $table, array $rows): void
{
    global $token;
    $body = json_encode(['table' => $table, 'rows' => $rows], JSON_UNESCAPED_UNICODE);
    if ($body === false) {
        throw new \RuntimeException('json_encode error');
    }

    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$token}",
            'content' => $body,
            'timeout' => 120,
            'ignore_errors' => true,
        ],
    ]);

    $response = @file_get_contents('https://perfushopping.ar/api/v1/sync-tables', false, $ctx);
    if ($response === false) {
        throw new \RuntimeException('Error HTTP al conectar con sync-tables');
    }
    $json = json_decode($response, true);
    if (!is_array($json) || !($json['ok'] ?? false)) {
        $err = $json['error'] ?? $response;
        throw new \RuntimeException("sync-tables error: {$err}");
    }
}

// ── Run ──
$tables = ['producto', 'gustos', 'stockcab', 'stockdet'];
$start = microtime(true);

$log("=== INICIO PULL desde {$apiUrl} ===");
$log("Destino: https://perfushopping.ar/api/v1/sync-tables");

foreach ($tables as $table) {
    try {
        $log("[{$table}] Solicitando datos...");
        $allRows = fetchLocal($apiUrl, $token, $table);
        if ($allRows === null || count($allRows) === 0) {
            $log("[{$table}] Sin datos");
            continue;
        }
        $total = count($allRows);
        $label = $table === 'producto' ? "{$table} (sin enweb)" : $table;
        $log("[{$table}] {$total} registros recibidos ({$label})");

        $sent = 0;
        foreach (array_chunk($allRows, $chunk) as $chunkRows) {
            postChunk($table, $chunkRows);
            $sent += count($chunkRows);
            $log("[{$table}] {$sent}/{$total}");
        }
        $log("[{$table}] OK — {$sent} registros");
    } catch (\Throwable $e) {
        $log("[ERROR] {$table}: {$e->getMessage()}");
        exit(1);
    }
}

$elapsed = round(microtime(true) - $start, 1);
$log("=== PULL COMPLETADO en {$elapsed}s ===");
