<?php
/**
 * Sincronización diaria de producto, gustos, stockcab, stockdet
 *
 * LEE desde: servidor ORIGEN (config en sync_config.php = perfushopping.sytes.net)
 * ESCRIBE en: DB local (.env = perfushopping.ar)
 *
 * Preserva producto.enweb del destino.
 *
 * USO (en el servidor perfushopping.ar):
 *   php src/sync_tables.php
 *
 * CRON (Linux):
 *   0 4 * * * cd /ruta/del/repo && /usr/bin/php src/sync_tables.php >> storage/logs/sync_cron.log 2>&1
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Perfushopping\Web\Infra\Db;

// ── Config ──
$configFile = __DIR__ . '/sync_config.php';
if (!is_file($configFile)) {
    echo "[ERROR] Creá " . $configFile . " primero (copiá sync_config.example.php y completá los datos del ORIGEN).\n";
    echo "  El ORIGEN es el servidor que tiene los datos reales (perfushopping.sytes.net)\n";
    echo "  El DESTINO es la DB local configurada en .env (perfushopping.ar)\n";
    exit(1);
}
$originCfg = require $configFile;

$chunkSize = 500;
$logFile = __DIR__ . '/../storage/logs/sync_' . date('Y-m-d') . '.log';

$log = function (string $msg) use ($logFile) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    echo $line . "\n";
    $dir = dirname($logFile);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    file_put_contents($logFile, $line . "\n", FILE_APPEND);
};

// ── ORIGEN (sytes.net: de donde leemos) ──
try {
    $dsn = "mysql:host={$originCfg['host']};port={$originCfg['port']};dbname={$originCfg['db']};charset=utf8mb4";
    $origin = new PDO($dsn, $originCfg['user'], $originCfg['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $log("[OK] Conexión ORIGEN ({$originCfg['host']}) establecida");
} catch (\Throwable $e) {
    $log("[ERROR] No se pudo conectar al ORIGEN {$originCfg['host']}: {$e->getMessage()}");
    exit(1);
}

// ── DESTINO (perfushopping.ar: donde escribimos) ──
$dest = Db::pdo();
$log("[OK] Conexión DESTINO (local) establecida");

// ── Helper: sync tabla con REPLACE ──
function syncReplace(PDO $origin, PDO $dest, string $table, array $cols, int $chunkSize, callable $log): void
{
    $total = (int)$origin->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $log("[{$table}] {$total} registros a sincronizar");

    $insertCols = implode(', ', $cols);
    $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $cols));
    $stmt = $dest->prepare("REPLACE INTO {$table} ({$insertCols}) VALUES ({$placeholders})");

    $offset = 0;
    $synced = 0;
    while ($offset < $total) {
        $rows = $origin->query("SELECT * FROM {$table} LIMIT {$chunkSize} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) break;

        $dest->beginTransaction();
        try {
            foreach ($rows as $r) {
                foreach ($cols as $c) {
                    $stmt->bindValue(":{$c}", $r[$c] ?? null);
                }
                $stmt->execute();
                $synced++;
            }
            $dest->commit();
        } catch (\Throwable $e) {
            $dest->rollBack();
            throw $e;
        }
        $offset += $chunkSize;
        $log("[{$table}] {$synced}/{$total}");
    }
    $log("[{$table}] OK — {$synced} registros");
}

// ── Helper: sync producto preservando enweb ──
function syncProducto(PDO $origin, PDO $dest, int $chunkSize, callable $log): void
{
    $cols = [
        'idprodu', 'codprodu', 'produ', 'codprodup', 'codprove', 'codrub', 'codsub', 'codepar', 'iva',
        'precio', 'precio1', 'precomp', 'ganan1', 'ganan2', 'stocact', 'stocdep', 'porc',
        'precioventabase', 'fecbaja', 'fecompra', 'fecalta', 'observ', 'imagen', 'idmarca',
        'codbarra', 'preciocostodolar', 'dolar',
    ];
    $setClauses = implode(', ', array_map(fn($c) => "{$c} = VALUES({$c})", $cols));
    $insertCols = implode(', ', $cols);
    $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $cols));

    $total = (int)$origin->query("SELECT COUNT(*) FROM producto")->fetchColumn();
    $log("[producto] {$total} registros a sincronizar (enweb preservado)");

    $stmt = $dest->prepare(
        "INSERT INTO producto ({$insertCols}) VALUES ({$placeholders})
         ON DUPLICATE KEY UPDATE {$setClauses}"
    );

    $offset = 0;
    $synced = 0;
    while ($offset < $total) {
        $rows = $origin->query("SELECT * FROM producto LIMIT {$chunkSize} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) break;

        $dest->beginTransaction();
        try {
            foreach ($rows as $r) {
                foreach ($cols as $c) {
                    $stmt->bindValue(":{$c}", $r[$c] ?? null);
                }
                $stmt->execute();
                $synced++;
            }
            $dest->commit();
        } catch (\Throwable $e) {
            $dest->rollBack();
            throw $e;
        }
        $offset += $chunkSize;
        $log("[producto] {$synced}/{$total}");
    }
    $log("[producto] OK — {$synced} registros (enweb intacto)");
}

// ── Ejecutar ──
$log("=== INICIO SINCRONIZACIÓN ===");
$log("Origen: {$originCfg['host']}/{$originCfg['db']} → Destino: local");

try {
    $dest->beginTransaction();
    $dest->exec("SET FOREIGN_KEY_CHECKS = 0");
    $dest->commit();

    syncProducto($origin, $dest, $chunkSize, $log);
    syncReplace($origin, $dest, 'gustos', ['idcodgusto', 'idprodu', 'nomgusto', 'codscan', 'precio', 'precio1', 'stockact', 'stockreal', 'discont', 'peso', 'fecbaja', 'fecha', 'codigogusto', 'descripcion'], $chunkSize, $log);
    syncReplace($origin, $dest, 'stockcab', ['idcabstock', 'iddepoh', 'iddepod', 'fecha', 'observ'], $chunkSize, $log);
    syncReplace($origin, $dest, 'stockdet', ['idstockdet', 'idstockcab', 'idprodu', 'idcodgusto', 'canti'], $chunkSize, $log);

    $dest->exec("SET FOREIGN_KEY_CHECKS = 1");
    $log("=== SINCRONIZACIÓN COMPLETADA ===");
} catch (\Throwable $e) {
    $log("[ERROR] {$e->getMessage()}");
    $dest->exec("SET FOREIGN_KEY_CHECKS = 1");
    exit(1);
}
