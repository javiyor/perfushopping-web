<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    include("../conectar.php");

    $codigo = $_GET['codigo'] ?? '';

    if ($codigo !== '') {
        $sql = "SELECT produ, 
                       nomgusto, 
                       fecompra, 
                       ROUND(precio,0) + ROUND(precio * tiva / 100, 0) AS precio, 
                       tiva 
                FROM gustos 
                INNER JOIN producto ON gustos.idprodu = producto.idprodu 
                INNER JOIN ivaprodu ON producto.iva = ivaprodu.codivaprodu 
                WHERE TRIM(codscan) = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();
        $productos = [];

        if ($fila = $resultado->fetch_assoc()) {
            $fila['fecompra'] = date("d/m/y", strtotime($fila['fecompra']));
            $fila['precio'] = floatval($fila['precio']);
            $fila['tiva'] = floatval($fila['tiva']);
            $productos[] = $fila;
        }

        echo json_encode($productos, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([]);
    }

    $conn->close();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
