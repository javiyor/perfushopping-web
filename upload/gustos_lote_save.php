<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config.php';

$TOKEN_VALIDO = 'Ngf75_Jry73_';

function responder($ok, $data = [], $code = 200) {
    http_response_code($code);

    echo json_encode(
        array_merge(['ok' => $ok], $data),
        JSON_UNESCAPED_UNICODE
    );

    exit;
}

function limpiar_nombre($nombre) {

    $nombre = trim($nombre);
    $nombre = str_replace('\\', '/', $nombre);
    $nombre = basename($nombre);
    $nombre = strtolower($nombre);

    $nombre = str_replace(
        ['á','é','í','ó','ú','ñ','ü',' '],
        ['a','e','i','o','u','n','u','-'],
        $nombre
    );

    $nombre = preg_replace('/[^a-z0-9._-]/', '', $nombre);
    $nombre = preg_replace('/-+/', '-', $nombre);

    return $nombre;
}

$token = $_POST['token'] ?? '';

if ($token !== $TOKEN_VALIDO) {
    responder(false, [
        'error' => 'Token incorrecto'
    ], 401);
}

$rutaimg = $_POST['rutaimg'] ?? '';
$idprodu = isset($_POST['idprodu']) ? (int)$_POST['idprodu'] : 0;
$idcodgusto = isset($_POST['idcodgusto']) ? (int)$_POST['idcodgusto'] : 0;
$idimagen = isset($_POST['idimagen']) ? (int)$_POST['idimagen'] : 0;

$rutaimg = limpiar_nombre($rutaimg);

if ($rutaimg === '') {
    responder(false, [
        'error' => 'Falta rutaimg'
    ], 400);
}

if ($idprodu <= 0) {
    responder(false, [
        'error' => 'Falta idprodu'
    ], 400);
}

if ($idcodgusto <= 0) {
    responder(false, [
        'error' => 'Falta idcodgusto'
    ], 400);
}

$rutaFisica = __DIR__ . DIRECTORY_SEPARATOR . $rutaimg;

if (!file_exists($rutaFisica)) {
    responder(false, [
        'error' => 'La imagen no existe en /upload',
        'archivo' => $rutaimg
    ], 404);
}

try {

    /*
      config.php debe tener:

      define('DB_HOST', 'localhost');
      define('DB_NAME', 'u427706089_ps');
      define('DB_USER', 'u427706089_ps');
      define('DB_PASS', 'xxxxx');
    */

    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    if ($idimagen <= 0) {

        $stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM imagen
            WHERE idcodgusto = ?
        ");

        $stmt->execute([$idcodgusto]);

        $cantidad = (int)$stmt->fetchColumn();

        if ($cantidad >= 6) {
            responder(false, [
                'error' => 'Maximo 6 imagenes por gusto'
            ], 400);
        }

        $stmt = $pdo->query("
            SELECT COALESCE(MAX(idimagen),0) + 1
            FROM imagen
        ");

        $idimagen = (int)$stmt->fetchColumn();

        $stmt = $pdo->prepare("
            INSERT INTO imagen
            (
                idimagen,
                rutaimg,
                idprodu,
                idcodgusto
            )
            VALUES
            (
                ?, ?, ?, ?
            )
        ");

        $stmt->execute([
            $idimagen,
            $rutaimg,
            $idprodu,
            $idcodgusto
        ]);

    } else {

        $stmt = $pdo->prepare("
            UPDATE imagen
            SET
                rutaimg = ?,
                idprodu = ?,
                idcodgusto = ?
            WHERE idimagen = ?
        ");

        $stmt->execute([
            $rutaimg,
            $idprodu,
            $idcodgusto,
            $idimagen
        ]);

        if ($stmt->rowCount() <= 0) {

            $stmt = $pdo->prepare("
                INSERT INTO imagen
                (
                    idimagen,
                    rutaimg,
                    idprodu,
                    idcodgusto
                )
                VALUES
                (
                    ?, ?, ?, ?
                )
            ");

            $stmt->execute([
                $idimagen,
                $rutaimg,
                $idprodu,
                $idcodgusto
            ]);
        }
    }

    responder(true, [
        'mensaje' => 'Imagen registrada correctamente',
        'idimagen' => $idimagen,
        'rutaimg' => $rutaimg,
        'idprodu' => $idprodu,
        'idcodgusto' => $idcodgusto,
        'url' => 'https://perfushopping.ar/upload/' . rawurlencode($rutaimg)
    ]);

} catch (Throwable $e) {

    responder(false, [
        'error' => 'Error MySQL',
        'detalle' => $e->getMessage()
    ], 500);
}