<?php

include("../conectar.php");  // o "../config.php" si está fuera de public_html

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

$sql = "SELECT token FROM tabtoken ORDER BY idtoken DESC LIMIT 1";
$result = $conn->query($sql);

if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(["token" => $row["token"]]);
} else {
    echo json_encode(["error" => "No se encontró el token"]);
}

$conn->close();
?>
