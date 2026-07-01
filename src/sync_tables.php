<?php
/**
 * Sincronización diaria de producto, gustos, stockcab, stockdet
 * desde la DB local → servidor remoto (perfushopping.sytes.net)
 *
 * Preserva producto.enweb del servidor remoto.
 *
 * USO:
 *   php src/sync_tables.php
 *
 * Para programar todos los días en Windows:
 *   - Abrir "Programador de tareas"
 *   - Crear tarea básica → disparador "Diariamente" a las 04:00
 *   - Acción: iniciar programa
 *     - Programa: php
 *     - Argumentos: "C:\perfushopping\web\src\sync_tables.php"
 *     - Iniciar en: C:\perfushopping\web
 */

declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

use Perfushopping\Web\Infra\Db;

// ── Config ──
$configFile = __DIR__ . '/sync_config.php';
if (!is_file($configFile)) {
    echo "[ERROR] Creá " . $configFile . " primero (copiá sync_config.example.php y completá la contraseña).\n";
    exit(1);
}
$remote = require $configFile;

$chunkSize = 500;
$logFile = __DIR__ . '/../storage/logs/sync_' . date('Y-m-d') . '.log';

$log = function (string $msg) use ($logFile) {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $msg;
    echo $line . "\n";
    $dir = dirname($logFile);
    if (!is_dir($dir)) @mkdir($dir, 0777, true);
    file_put_contents($logFile, $line . "\n", FILE_APPEND);
};

// ── Conectar remota ──
try {
    $dsn = "mysql:host={$remote['host']};port={$remote['port']};dbname={$remote['db']};charset=utf8mb4";
    $rem = new PDO($dsn, $remote['user'], $remote['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $log("[OK] Conexión remota establecida");
} catch (\Throwable $e) {
    $log("[ERROR] No se pudo conectar a {$remote['host']}: {$e->getMessage()}");
    exit(1);
}

$local = Db::pdo();

// ── Helper para escapar ──
function quote(string $s): string
{
    return "'" . str_replace(["'", "\n", "\r"], ["''", '\\n', '\\r'], $s) . "'";
}

// ── Helper: sync tabla con REPLACE ──
function syncReplace(PDO $local, PDO $rem, string $table, array $cols, int $chunkSize, callable $log): void
{
    $total = (int)$local->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
    $log("[{$table}] {$total} registros a sincronizar");

    $insertCols = implode(', ', $cols);
    $placeholders = implode(', ', array_map(fn($c) => ":{$c}", $cols));
    $stmt = $rem->prepare("REPLACE INTO {$table} ({$insertCols}) VALUES ({$placeholders})");

    $offset = 0;
    $synced = 0;
    while ($offset < $total) {
        $rows = $local->query("SELECT * FROM {$table} LIMIT {$chunkSize} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) break;

        $rem->beginTransaction();
        try {
            foreach ($rows as $r) {
                foreach ($cols as $c) {
                    $stmt->bindValue(":{$c}", $r[$c] ?? null);
                }
                $stmt->execute();
                $synced++;
            }
            $rem->commit();
        } catch (\Throwable $e) {
            $rem->rollBack();
            throw $e;
        }
        $offset += $chunkSize;
        $log("[{$table}] {$synced}/{$total}");
    }
    $log("[{$table}] OK — {$synced} registros");
}

// ── Helper: sync producto preservando enweb ──
function syncProducto(PDO $local, PDO $rem, int $chunkSize, callable $log): void
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

    $total = (int)$local->query("SELECT COUNT(*) FROM producto")->fetchColumn();
    $log("[producto] {$total} registros a sincronizar (enweb preservado)");

    $stmt = $rem->prepare(
        "INSERT INTO producto ({$insertCols}) VALUES ({$placeholders})
         ON DUPLICATE KEY UPDATE {$setClauses}"
    );

    $offset = 0;
    $synced = 0;
    while ($offset < $total) {
        $rows = $local->query("SELECT * FROM producto LIMIT {$chunkSize} OFFSET {$offset}")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) break;

        $rem->beginTransaction();
        try {
            foreach ($rows as $r) {
                foreach ($cols as $c) {
                    $stmt->bindValue(":{$c}", $r[$c] ?? null);
                }
                $stmt->execute();
                $synced++;
            }
            $rem->commit();
        } catch (\Throwable $e) {
            $rem->rollBack();
            throw $e;
        }
        $offset += $chunkSize;
        $log("[producto] {$synced}/{$total}");
    }
    $log("[producto] OK — {$synced} registros (enweb intacto)");
}

// ── Ejecutar ──
$log("=== INICIO SINCRONIZACIÓN ===");

try {
    $rem->beginTransaction();
    // Lock tables to prevent FK issues
    $rem->exec("SET FOREIGN_KEY_CHECKS = 0");
    $rem->commit();

    syncProducto($local, $rem, $chunkSize, $log);
    syncReplace($local, $rem, 'gustos', ['idcodgusto', 'idprodu', 'nomgusto', 'codscan', 'precio', 'precio1', 'stockact', 'stockreal', 'discont', 'peso', 'fecbaja', 'fecha', 'codigogusto', 'descripcion'], $chunkSize, $log);
    syncReplace($local, $rem, 'stockcab', ['idcabstock', 'iddepoh', 'iddepod', 'fecha', 'observ'], $chunkSize, $log);
    syncReplace($local, $rem, 'stockdet', ['idstockdet', 'idstockcab', 'idprodu', 'idcodgusto', 'canti'], $chunkSize, $log);

    $rem->exec("SET FOREIGN_KEY_CHECKS = 1");
    $log("=== SINCRONIZACIÓN COMPLETADA ===");
} catch (\Throwable $e) {
    $log("[ERROR] {$e->getMessage()}");
    $rem->exec("SET FOREIGN_KEY_CHECKS = 1");
    exit(1);
}
