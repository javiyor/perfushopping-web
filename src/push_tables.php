<?php
/**
 * Push local tables a Hostinger via HTTP API
 *
 * Lee desde la DB local (sync_config.php) y envía los datos a
 * https://perfushopping.ar/api/v1/sync-tables usando el SYNC_TOKEN.
 *
 * USO:
 *   php src/push_tables.php --token=MISECRET
 *
 * Opcional:
 *   --url=https://perfushopping.ar/api/v1/sync-tables
 *   --chunk=500
 *   --tables=producto,gustos,stockcab,stockdet
 */

declare(strict_types=1);

// ── Parse args ──
$args = [];
foreach ($argv ?? [] as $a) {
    if (preg_match('/^--(\w+)=(.+)$/', $a, $m)) {
        $args[$m[1]] = $m[2];
    }
}

$apiUrl = $args['url'] ?? 'https://perfushopping.ar/api/v1/sync-tables';
$token  = $args['token'] ?? getenv('SYNC_TOKEN') ?? '';
$chunk  = max(50, min(2000, (int)($args['chunk'] ?? 500)));
$tables = isset($args['tables']) ? explode(',', $args['tables']) : ['producto', 'gustos', 'stockcab', 'stockdet'];
$tables = array_intersect($tables, ['producto', 'gustos', 'stockcab', 'stockdet']);

if ($token === '') {
    echo "[ERROR] Usá --token=MISECRET o setéá SYNC_TOKEN en el entorno\n";
    exit(1);
}

// ── Config DB origen ──
$cfgFile = __DIR__ . '/sync_config.php';
if (!is_file($cfgFile)) {
    echo "[ERROR] No se encuentra sync_config.php\n";
    exit(1);
}
$cfg = require $cfgFile;

$dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['db']};charset=utf8mb4";
$origin = new PDO($dsn, $cfg['user'], $cfg['pass'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
echo "[OK] Conectado a {$cfg['host']}/{$cfg['db']}\n";

// ── Helper: POST chunk ──
function postChunk(string $url, string $token, string $table, array $rows): void
{
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

    $response = @file_get_contents($url, false, $ctx);
    if ($response === false) {
        throw new \RuntimeException('Error HTTP al conectar con ' . $url);
    }

    $json = json_decode($response, true);
    if (!is_array($json) || !($json['ok'] ?? false)) {
        $err = $json['error'] ?? $response;
        throw new \RuntimeException("API error: {$err}");
    }
}

// ── Helper: get columns for a table, exclude enweb for producto ──
/** @return string[] */
function getColumns(PDO $origin, string $table): array
{
    $db = $origin->query('SELECT DATABASE()')->fetchColumn();
    $st = $origin->prepare("
        SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t
        ORDER BY ORDINAL_POSITION
    ");
    $st->execute([':db' => $db, ':t' => $table]);
    $cols = $st->fetchAll(PDO::FETCH_COLUMN);
    if ($table === 'producto') {
        $cols = array_values(array_filter($cols, fn($c) => $c !== 'enweb'));
    }
    return $cols;
}

// ── Helper: get columns + nullability defaults ──
/** @return array<string, mixed> column_name => default_value */
function getColumnDefaults(PDO $origin, string $table): array
{
    $db = $origin->query('SELECT DATABASE()')->fetchColumn();
    $st = $origin->prepare("
        SELECT COLUMN_NAME, IS_NULLABLE, COLUMN_DEFAULT, DATA_TYPE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t
        ORDER BY ORDINAL_POSITION
    ");
    $st->execute([':db' => $db, ':t' => $table]);
    $defaults = [];
    while ($row = $st->fetch(PDO::FETCH_ASSOC)) {
        $name = $row['COLUMN_NAME'];
        if ($table === 'producto' && $name === 'enweb') {
            continue;
        }
        if ($row['IS_NULLABLE'] === 'YES') {
            $defaults[$name] = null; // null is ok
        } else {
            $def = $row['COLUMN_DEFAULT'];
            if ($def !== null && $def !== '') {
                $defaults[$name] = $def;
            } else {
                // Infer default by data type
                $type = $row['DATA_TYPE'];
                if (in_array($type, ['int','smallint','tinyint','bigint','decimal','float','double','numeric'], true)) {
                    $defaults[$name] = 0;
                } elseif (in_array($type, ['date','datetime','timestamp'], true)) {
                    $defaults[$name] = $type === 'timestamp' ? '0000-00-00 00:00:00' : '0000-00-00';
                } else {
                    $defaults[$name] = '';
                }
            }
        }
    }
    return $defaults;
}

// ── Clean rows: replace nulls with defaults ──
/** @param array<string, mixed> $defaults */
function cleanRows(array &$rows, array $defaults): void
{
    foreach ($rows as &$row) {
        foreach ($defaults as $col => $def) {
            if (array_key_exists($col, $row) && $row[$col] === null && $def !== null) {
                $row[$col] = $def;
            }
        }
    }
    unset($row);
}

// ── Sync a table ──
function syncTable(PDO $origin, string $url, string $token, string $table, int $chunk): void
{
    $cols = getColumns($origin, $table);
    $selectCols = implode(', ', $cols);
    $defaults = getColumnDefaults($origin, $table);

    $total = (int)$origin->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $label = $table === 'producto' ? "{$table} (sin enweb)" : $table;
    echo "[{$table}] {$total} registros ({$label})\n";

    $offset = 0;
    $sent = 0;
    while ($offset < $total) {
        $rows = $origin->query("SELECT {$selectCols} FROM {$table} LIMIT {$chunk} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            break;
        }
        cleanRows($rows, $defaults);
        postChunk($url, $token, $table, $rows);
        $sent += count($rows);
        echo "[{$table}] {$sent}/{$total}\n";
        $offset += $chunk;
    }
    echo "[{$table}] OK — {$sent} registros\n";
}

// ── Sync producto (sin enweb ── mismo que syncTable, solo existe para claridad)
function syncProducto(PDO $origin, string $url, string $token, int $chunk): void
{
    syncTable($origin, $url, $token, 'producto', $chunk);
}

// ── Run ──
echo "=== INICIO PUSH ===\n";
echo "Destino: {$apiUrl}\n";

$start = microtime(true);
foreach ($tables as $table) {
    try {
        if ($table === 'producto') {
            syncProducto($origin, $apiUrl, $token, $chunk);
        } else {
            syncTable($origin, $apiUrl, $token, $table, $chunk);
        }
    } catch (\Throwable $e) {
        echo "[ERROR] {$table}: {$e->getMessage()}\n";
        exit(1);
    }
}
// Full stock recalculation as safety net
try {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer {$token}",
            'timeout' => 300,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($apiUrl . '/../recalcular-stock', false, $ctx);
    if ($resp !== false) {
        $j = json_decode($resp, true);
        echo "[RECALCULAR] " . ($j['info'] ?? $resp) . "\n";
    }
} catch (\Throwable $e) {
    echo "[RECALCULAR] warning: {$e->getMessage()}\n";
}

// Full stock recalculation as safety net
$recalcUrl = str_replace('/sync-tables', '/recalcular-stock', $apiUrl);
try {
    $ctx = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer {$token}",
            'timeout' => 300,
            'ignore_errors' => true,
        ],
    ]);
    $resp = @file_get_contents($recalcUrl, false, $ctx);
    if ($resp !== false) {
        $j = json_decode($resp, true);
        echo "[RECALCULAR] " . ($j['info'] ?? $resp) . "\n";
    } else {
        echo "[RECALCULAR] warning: sin respuesta\n";
    }
} catch (\Throwable $e) {
    echo "[RECALCULAR] warning: {$e->getMessage()}\n";
}

$elapsed = round(microtime(true) - $start, 1);
echo "=== PUSH COMPLETADO en {$elapsed}s ===\n";
