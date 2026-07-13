<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Perfushopping\Web\Infra\Db;

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

try {
    $palabraClave = trim($_GET['keyword'] ?? '');
    $page = max(1, (int)($_GET['page'] ?? 1));

    $porPagina = 80;
    $offset = ($page - 1) * $porPagina;

    $productos = [];

    if ($palabraClave !== '') {
        $patron = '%' . strtoupper($palabraClave) . '%';

        $sql = "SELECT producto.idprodu, 
                       produ, 
                       ROUND(precio,0) + ROUND(precio * tiva / 100, 0) AS precio, 
                       fecompra, 
                       nomgusto, 
                       codscan, 
                       tiva 
                FROM gustos 
                INNER JOIN producto ON gustos.idprodu = producto.idprodu 
                INNER JOIN ivaprodu ON producto.iva = ivaprodu.codivaprodu 
                WHERE UPPER(produ) LIKE ? OR UPPER(nomgusto) LIKE ?
                ORDER BY produ, nomgusto
                LIMIT ? OFFSET ?";

        $st = Db::pdo()->prepare($sql);
        $st->execute([$patron, $patron, $porPagina, $offset]);

        while ($fila = $st->fetch()) {
            $fila['fecompra'] = date("d/m/y", strtotime($fila['fecompra']));
            $productos[] = $fila;
        }
    }

    echo json_encode($productos, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
