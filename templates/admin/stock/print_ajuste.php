<?php
use Perfushopping\Web\Support\Format;

// Datos del ajuste desde URL
$ajuste = $ajuste ?? [];
$idAjuste = (int)($ajuste['id'] ?? 0);
$fecha = (string)($ajuste['fecha'] ?? '');
$motivo = (string)($ajuste['motivo'] ?? '');
$depodesde = (int)($ajuste['depodesde'] ?? 0);
$depohasta = (int)($ajuste['depohasta'] ?? 0);
$items = $ajuste['items'] ?? [];

$montoInicial = 0; // No aplica para stock, pero mantenemos estructura
$totalItemsCents = 0;

$empresa = $empresa ?? [];
$sucursal = $sucursal ?? [];
$auth = new \Perfushopping\Web\Service\AdminAuthService(); // para getPuntoVenta
$puntoVenta = $auth->getPuntoVenta();

$turno = 'Ajuste de stock';
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Impresión Ajuste de Stock - <?= htmlspecialchars($empresa['razon_social'] ?? 'PERFUSHOPPING') ?></title>
    <style>
        @page {
            size: auto;
            margin: 0mm 0mm 0mm 0mm;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 10px;
            margin: 0;
            padding: 0;
            background: white;
        }
        .ticket {
            width: 696px;
            margin: 0 auto;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding: 5px 0;
            margin-bottom: 5px;
        }
        .header h1 {
            font-size: 14px;
            margin: 0;
            font-weight: bold;
        }
        .header p {
            font-size: 10px;
            margin: 2px 0 0 0;
        }
        .section {
            border-bottom: 1px solid #ccc;
            padding: 3px 0;
            margin: 3px 0;
        }
        .section .label {
            font-weight: bold;
            float: left;
            width: 45%;
            font-size: 10px;
        }
        .section .value {
            float: right;
            width: 55%;
            text-align: right;
            font-size: 10px;
        }
        .total {
            border-top: 2px solid #333;
            padding: 5px 0;
            margin: 5px 0;
            font-size: 12px;
            font-weight: bold;
        }
        .footer {
            text-align: center;
            font-size: 8px;
            margin-top: 8px;
            border-top: 1px solid #333;
            padding: 4px 0;
        }
    </style>
</head>
<body>
<div class="ticket">
    <div class="header">
        <h1><?= htmlspecialchars($empresa['razon_social'] ?? 'PERFUSHOPPING') ?></h1>
        <p>RUC/CUIT: <?= htmlspecialchars($empresa['cuit'] ?? '') ?></p>
        <p>Sucursal: <?= htmlspecialchars($sucursal['nombre'] ?? '') ?> - Punto de Venta: <?= htmlspecialchars($puntoVenta) ?></p>
        <p>Fecha: <?= htmlspecialchars($fecha) ?> - Turno: <?= htmlspecialchars($turno) ?></p>
    </div>

    <div class="section">
        <span class="label">Motivo</span>
        <span class="value"><?= htmlspecialchars($motivo) ?></span>
    </div>

    <div class="section">
        <span class="label">Depósito desde</span>
        <span class="value"><?= $depodesde > 0 ? htmlspecialchars($depodesde) : 'Ninguno' ?></span>
    </div>

    <div class="section">
        <span class="label">Depósito hasta</span>
        <span class="value"><?= $depohasta > 0 ? htmlspecialchars($depohasta) : 'Ninguno' ?></span>
    </div>

    <div class="section total">
        <span class="label">Productos ajustados</span>
        <span class="value"><?= count($items) ?> producto(s)</span>
    </div>

    <table style="width:100%; border-collapse:collapse; margin:5px 0;">
        <thead>
            <tr style="background:#ddd;">
                <th style="width:60%; text-align:left; font-size:9px;">Producto</th>
                <th style="width:20%; text-align:center; font-size:9px;">Cantidad</th>
                <th style="width:20%; text-align:right; font-size:9px;">Stock afectado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td style="font-size:9px; text-align:left;">
                    ID: <?= htmlspecialchars($it['idprodu'] ?? '') ?>
                </td>
                <td style="font-size:9px; text-align:center;">
                    <?= (int)($it['cantidad'] ?? 0) ?>
                </td>
                <td style="font-size:9px; text-align:right;">
                    Stock actualizado
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="total">
        <span class="label">Saldo actualizado</span>
        <span class="value">Stock modificado según los registros</span>
    </div>

    <div class="footer">
        <p>ID Ajuste: #<?= $idAjuste ?></p>
        <p>Impreso el <?= date('d/m/Y H:i') ?></p>
    </div>
</div>
</body>
</html>