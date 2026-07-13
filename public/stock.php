<?php
require_once __DIR__ . '/../src/bootstrap.php';

use Perfushopping\Web\Infra\Db;

header("Content-Type: application/json");

$keyword = $_GET['keyword'] ?? '';
$iddepo = (int)($_GET['iddepo'] ?? 0);

$params = [];
$where = [];

if ($keyword !== '') {
    $where[] = "(p.produ LIKE ? OR g.nomgusto LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

if ($iddepo > 0) {
    $where[] = "c.iddepoh = ?";
    $params[] = $iddepo;
}

$whereClause = $where ? 'AND ' . implode(' AND ', $where) : '';

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
    WHERE 1=1 $whereClause
    GROUP BY g.idcodgusto
    ORDER BY p.produ, g.nomgusto
";

$st = Db::pdo()->prepare($sql);
$st->execute($params);
$data = [];

while ($row = $st->fetch()) {
    $data[] = [
        "idcodgusto" => $row["idcodgusto"],
        "producto" => $row["produ"],
        "gusto" => $row["nomgusto"],
        "stock" => (int)$row["stock"],
        "precomp" => floatval($row["precomp"]),
    ];
}

echo json_encode($data);
