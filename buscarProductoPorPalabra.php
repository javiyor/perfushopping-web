
<?php
include("../config.php");

ini_set('display_errors', 1);
error_reporting(E_ALL);

// Obtener parámetros y sanitizar
$palabraClave = trim($_GET['keyword'] ?? '');
$page = intval($_GET['page'] ?? 1);
$page = max($page, 1); // Asegura que no sea menor a 1

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
            WHERE UPPER(produ) LIKE ? or UPPER(nomgusto) like ?
               ORDER BY produ, nomgusto
            LIMIT ? OFFSET ?";
            //LOWER(produ) LIKE ? OR LOWER(nomgusto) LIKE ?
//          WHERE MATCH(produ) AGAINST (?) OR MATCH(nomgusto) AGAINST (?) 
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("ssii", $patron, $patron, $porPagina, $offset);
        $stmt->execute();
        $resultado = $stmt->get_result();

        while ($fila = $resultado->fetch_assoc()) {
            $fila['fecompra'] = date("d/m/y", strtotime($fila['fecompra']));
            $productos[] = $fila;
        }
    }
}

// Devolver respuesta como JSON
header('Content-Type: application/json');
echo json_encode($productos, JSON_UNESCAPED_UNICODE);
$conn->close();
?>
