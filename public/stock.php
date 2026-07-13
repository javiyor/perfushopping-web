<?php
header("Content-Type: application/json");
header('Access-Control-Allow-Origin: *');

try {
    include("../conectar.php");

    $keyword = $_GET['keyword'] ?? '';
    $iddepo = (int)($_GET['iddepo'] ?? 0);

    $whereKeyword = "";
    if ($keyword !== '') {
        $keywordEscaped = $conn->real_escape_string($keyword);
        $whereKeyword = "AND (p.produ LIKE '%$keywordEscaped%' OR g.nomgusto LIKE '%$keywordEscaped%')";
    }

    $whereDeposito = "";
    if ($iddepo > 0) {
        $whereDeposito = "AND c.iddepoh = $iddepo";
    }

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
            "precomp" => floatval($row["precomp"]),
        ];
    }

    echo json_encode($data);
    $conn->close();
} catch (\Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
