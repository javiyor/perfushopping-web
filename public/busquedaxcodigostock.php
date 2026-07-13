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
                       tiva,
                       idcodgusto
                FROM gustos 
                INNER JOIN producto ON gustos.idprodu = producto.idprodu 
                INNER JOIN ivaprodu ON producto.iva = ivaprodu.codivaprodu 
                WHERE TRIM(codscan) = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $codigo);
        $stmt->execute();
        $resultado = $stmt->get_result();

        if ($fila = $resultado->fetch_assoc()) {
            $fila['fecompra'] = date("d/m/y", strtotime($fila['fecompra']));
            $fila['precio'] = floatval($fila['precio']);
            $fila['tiva'] = floatval($fila['tiva']);

            $idcodgusto = $fila['idcodgusto'];

            $stocksQuery = "
                SELECT d.iddepo, d.nomdepo AS nombre_deposito,
                    COALESCE(SUM(CASE WHEN s.iddepoh = d.iddepo THEN sd.canti ELSE 0 END), 0) -
                    COALESCE(SUM(CASE WHEN s.iddepod = d.iddepo THEN sd.canti ELSE 0 END), 0) AS stock
                FROM deposito d
                LEFT JOIN stockcab s ON s.iddepoh = d.iddepo OR s.iddepod = d.iddepo
                LEFT JOIN stockdet sd ON sd.idstockcab = s.idstockcab AND sd.idcodgusto = ?
                WHERE d.marca = 2
                GROUP BY d.iddepo, d.nomdepo
                ORDER BY d.iddepo
            ";

            $stmtStock = $conn->prepare($stocksQuery);
            $stmtStock->bind_param("i", $idcodgusto);
            $stmtStock->execute();
            $stockResult = $stmtStock->get_result();

            $stocks = [];
            while ($s = $stockResult->fetch_assoc()) {
                $stocks[] = [
                    'iddepo' => $s['iddepo'],
                    'nombre_deposito' => $s['nombre_deposito'],
                    'stock' => (int)$s['stock'],
                ];
            }

            $fila['stocks'] = $stocks;

            echo json_encode([$fila], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([]);
        }
    } else {
        echo json_encode([]);
    }

    $conn->close();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
