<?php
include "conexion.php";
header("Content-Type: application/json");

$keyword = isset($_GET['keyword']) ? $conn->real_escape_string($_GET['keyword']) : '';
$iddepo = isset($_GET['iddepo']) ? intval($_GET['iddepo']) : 0;

$whereKeyword = "";
if (!empty($keyword)) {
    $whereKeyword = "AND (p.produ LIKE '%$keyword%' OR g.nomgusto LIKE '%$keyword%')";
}

$whereDeposito = "";
if ($iddepo > 0) {
    $whereDeposito = "AND c.iddepoh = $iddepo";
}

// Consulta SQL
$sql = "
    SELECT 
        g.idcodgusto,
        p.produ,
        g.nomgusto,
        SUM(d.canti) as stock,
        p.precomp
    FROM gustos g
    JOIN producto p ON p.idprodu = g.idprodu
    JOIN stockdet d ON d.idcodgusto = g.idcodgusto
    JOIN stockcab c ON c.idcabstock = d.idstockcab
    WHERE 1=1 $whereKeyword $whereDeposito
    GROUP BY g.idcodgusto
    ORDER BY p.produ, g.nomgusto
";

$result = $conn->query($sql);
$data = [];

while ($row = $result->fetch_assoc()) {
    $data[] = [
        "idcodgusto" => $row["idcodgusto"],
        "producto" => $row["produ"],
        "gusto" => $row["nomgusto"],
        "stock" => intval($row["stock"]),
        "precomp" => floatval($row["precomp"])
    ];
}

echo json_encode($data);
$conn->close();
?>
