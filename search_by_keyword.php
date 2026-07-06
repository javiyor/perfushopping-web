<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

include("../config.php");  // Asegurate de que este archivo define correctamente $conn (MySQLi)

// Validar si se recibió el parámetro
if (!isset($_GET['keyword'])) {
    echo json_encode(array("error" => "Falta el parámetro 'keyword'"));
    exit;
}

$keyword = $_GET['keyword'];

// Preparar consulta con parámetros para evitar inyección SQL
$sql = "SELECT p.idprodu, p.produ, p.precio, g.nomgusto, g.codscan, p.iva, i.tiva
        FROM gustos g
        INNER JOIN producto p ON g.idprodu = p.idprodu
        INNER JOIN ivaprodu i ON p.iva = i.codivaprodu
        WHERE p.produ LIKE CONCAT('%', ?, '%')" ;

// Usar statement preparado
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $keyword);
$stmt->execute();
$result = $stmt->get_result();

$products = array();
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);

// Cerrar recursos
$stmt->close();
$conn->close();
?>
