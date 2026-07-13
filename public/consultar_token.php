<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Perfushopping\Web\Infra\Db;

header('Content-Type: application/json');

$sql = "SELECT token FROM tabtoken ORDER BY idtoken DESC LIMIT 1";
$st = Db::pdo()->query($sql);
$row = $st->fetch();

if ($row) {
    echo json_encode(["token" => $row["token"]]);
} else {
    echo json_encode(["error" => "No se encontró el token"]);
}
