<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Cargar la clase
require_once __DIR__ . '/../../afip-lib/AfipPadronA5.php';

// Leer JSON de entrada
$data = json_decode(file_get_contents("php://input"), true);
$cuit = $data['cuit'] ?? null;

if (!$cuit) {
    echo json_encode(['error' => 'CUIT requerido']);
    exit;
}

// Consultar AFIP
$p = AfipPadronA5::consultar($cuit);

// Buscar domicilio fiscal
$dom = null;
if (isset($p->domicilios)) {
    foreach ($p->domicilios as $d) {
        if ($d->tipoDomicilio == 'FISCAL') {
            $dom = $d;
            break;
        }
    }
}

// Respuesta FINAL (un solo echo)
echo json_encode([
    "razonSocial" => $p->razonSocial ?? "",
    "estado" => $p->estadoClave ?? "",
    "direccion" => $dom->direccion ?? "",
    "ciudad" => $dom->localidad ?? "",
    "provincia" => $dom->provincia ?? "",
    "pais" => $dom->pais ?? "ARGENTINA"
]);

]);
