<?php
include("../config.php"); // tu conexión MySQL

$sql = "
    SELECT p.razon,
           SUM(c.debe) AS total_debe,
           SUM(c.haber) AS total_haber,
           SUM(c.debe - c.haber) AS saldo
    FROM (
        SELECT cod_cli, debe, haber FROM ctaclic WHERE marca <> 1
        UNION ALL
        SELECT cod_cli, debe, haber FROM ctacli2c WHERE marca <> 1
    ) AS c
    JOIN proveedo p ON c.cod_cli = p.idprovee
    GROUP BY p.idprovee, p.razon
    ORDER BY p.razon
";

$resultado = $conn->query($sql);

if (!$resultado) {
    die("❌ Error en la consulta: " . $conn->error);
}

$datos = [];

while ($fila = $resultado->fetch_assoc()) {
    $datos[] = [
        'razon' => $fila['razon'],
        'debe' => floatval($fila['total_debe']),
        'haber' => floatval($fila['total_haber']),
        'saldo' => floatval($fila['saldo']),
    ];
}

header('Content-Type: application/json');
echo json_encode($datos, JSON_UNESCAPED_UNICODE);

$conn->close();
?>
