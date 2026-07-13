<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    include("../conectar.php");

    $sql = "SELECT token FROM tabtoken ORDER BY idtoken DESC LIMIT 1";
    $result = $conn->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(["token" => $row["token"]]);
    } else {
        echo json_encode(["error" => "No se encontró el token"]);
    }

    $conn->close();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
