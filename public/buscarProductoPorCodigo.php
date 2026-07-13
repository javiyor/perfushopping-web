<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Perfushopping\Web\Infra\Db;

header('Content-Type: application/json');

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
            WHERE TRIM(codscan) = ?";

    $st = Db::pdo()->prepare($sql);
    $st->execute([$codigo]);
    $productos = [];

    while ($fila = $st->fetch()) {
        $fila['fecompra'] = date("d/m/y", strtotime($fila['fecompra']));
        $fila['precio'] = floatval($fila['precio']);
        $fila['tiva'] = floatval($fila['tiva']);

        $productos[] = $fila;
    }

    echo json_encode($productos, JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([]);
}
