<?php
header('Content-Type: text/plain; charset=Windows-1252');
function kv_enc(string $s): string {
  $s = str_replace(["\r","\n"], ' ', $s);
  $out = @iconv('UTF-8','Windows-1252//TRANSLIT',$s);
  return $out !== false ? $out : $s;
}
function kv_out(int $code, array $data): void {
  http_response_code($code);
  foreach ($data as $k => $v) {
    $v = is_null($v) ? '' : (string)$v;
    echo $k . '=' . kv_enc($v) . "\n";
  }
  exit;
}
$API_KEY = 'PONER_TU_API_KEY';
$reqKey = trim((string)($_SERVER['HTTP_X_API_KEY'] ?? ($_POST['api_key'] ?? '')));
if (!hash_equals($API_KEY, $reqKey)) kv_out(401, ['ok'=>'0','error'=>'No autorizado']);
$lote = (string)($_POST['lote'] ?? '');
$lote = str_replace("\r\n", "\n", $lote);
$lote = str_replace("\r", "\n", $lote);
$lote = trim($lote);
if ($lote === '') kv_out(400, ['ok'=>'0','error'=>'Lote vacio']);
$dsn  = 'mysql:host=TU_HOST;dbname=TU_DB;charset=utf8mb4';
$user = 'TU_USER';
$pass = 'TU_PASS';
$inserted = 0;
$processed = 0;
$errors = 0;
try {
  $pdo = new PDO($dsn, $user, $pass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_EMULATE_PREPARES => false,
  ]);
  $sql = "INSERT INTO gustos (idcodgustos, descripcion, codscan, stock, ruta)
          VALUES (:id, :des, :cod, :stk, :rut)
          ON DUPLICATE KEY UPDATE
            descripcion=VALUES(descripcion),
            codscan=VALUES(codscan),
            stock=VALUES(stock),
            ruta=VALUES(ruta)";
  $st = $pdo->prepare($sql);
  $pdo->beginTransaction();
  $lines = explode("\n", $lote);
  foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    $processed++;
    $parts = explode(';', $line);
    $id  = (int)trim($parts[0] ?? '0');
    $des = trim($parts[1] ?? '');
    $cod = trim($parts[2] ?? '');
    $stk = (float)str_replace(',', '.', trim($parts[3] ?? '0'));
    $rut = trim($parts[4] ?? '');
    if ($id <= 0 || $des === '') { $errors++; continue; }
    $st->execute([
      ':id'  => $id,
      ':des' => $des,
      ':cod' => ($cod === '' ? null : $cod),
      ':stk' => $stk,
      ':rut' => ($rut === '' ? null : $rut),
    ]);
    // rowCount puede ser 1 (insert) o 2 (update) en MySQL; lo contamos como “ok”
    $inserted++;
  }
  $pdo->commit();
  kv_out(200, [
    'ok' => '1',
    'processed' => (string)$processed,
    'saved' => (string)$inserted,
    'errors' => (string)$errors,
  ]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  kv_out(500, ['ok'=>'0','error'=>'Error servidor']);
}