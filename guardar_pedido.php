<?php
include "conexion.php";

// Obtener los datos del cuerpo JSON
$data = json_decode(file_get_contents("php://input"), true);

$cliente = $data["cliente"];
$observ = $data["observ"];
$productos = $data["productos"]; // array de productos con idcodgusto y cantidad

// Insertar pedido en cabecera
$stmt = $conn->prepare("INSERT INTO pedido (fecha, cliente, observ) VALUES (NOW(), ?, ?)");
$stmt->bind_param("ss", $cliente, $observ);
$stmt->execute();
$idpedido = $stmt->insert_id;
$stmt->close();

// Insertar detalles
$stmt2 = $conn->prepare("INSERT INTO detpedi (idpedido, idcodgusto, cantidad) VALUES (?, ?, ?)");
foreach ($productos as $prod) {
    $idcodgusto = $prod["idcodgusto"];
    $cantidad = $prod["cantidad"];
    if ($cantidad > 0) {
        $stmt2->bind_param("iii", $idpedido, $idcodgusto, $cantidad);
        $stmt2->execute();
    }
}
$stmt2->close();

echo json_encode(["estado" => "ok", "idpedido" => $idpedido]);
$conn->close();
?>
