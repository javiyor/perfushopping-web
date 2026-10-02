<?php
use Perfushopping\Web\Support\Format;

// Datos de la apertura
$apertura = $apertura ?? [];
$empresa = $empresa ?? [];
$sucursal = $sucursal ?? [];
$fecha = (string)($fecha ?? date('Y-m-d'));
$turno = (string)($turno ?? '');

$montoInicial = (int)($apertura['monto_inicial_cents'] ?? 0);
$ventasEfectivo = (int)($ventasEfectivo ?? 0);
$totalIngresos = (int)($totalIngresos ?? 0);
$totalEgresos = (int)($totalEgresos ?? 0);
$efectivoDisponible = (int)($efectivoDisponible ?? 0);
$montoCierre = (int)($montoCierre ?? 0);
$montoRetirado = (int)($montoRetirado ?? 0);
$proximaApertura = (int)($proximaApertura ?? 0);

// Calculos
$salidaEfectivo = $montoInicial + $ventasEfectivo - $totalEgresos;
$saldoCaja = $efectivoDisponible; // o $salidaEfectivo según criterio
$diferencia = $saldoCaja - $montoInicial - $ventasEfectivo + $totalEgresos;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen Cierre de Caja - <?= htmlspecialchars($empresa['razon_social'] ?? 'Perfushopping') ?></title>
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
            width: 696px; /* 80mm ~ 230px at 300dpi, but we scale for receipt */
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
        <p>Sucursal: <?= htmlspecialchars($sucursal['nombre'] ?? '') ?> - Punto de Venta: <?= htmlspecialchars($auth->getPuntoVenta() ?? '') ?></p>
        <p>Fecha: <?= htmlspecialchars($fecha) ?> - Turno: <?= htmlspecialchars(ucfirst($turno)) ?></p>
    </div>

    <div class="section">
        <span class="label">Caja Inicial</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($montoInicial) ?></span>
    </div>

    <div class="section">
        <span class="label">Ingresos Efectivo</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($ventasEfectivo) ?></span>
    </div>

    <div class="section">
        <span class="label">Egresos Efectivo</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($totalEgresos) ?></span>
    </div>

    <div class="section total">
        <span class="label">Saldo de Caja</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($saldoCaja) ?></span>
    </div>

    <?php if ($montoRetirado > 0): ?>
    <div class="section">
        <span class="label">Pasaje a Caja General</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($montoRetirado) ?></span>
    </div>
    <?php endif; ?>

    <?php if ($proximaApertura > 0): ?>
    <div class="section">
        <span class="label">Saldo Próxima Apertura</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($proximaApertura) ?></span>
    </div>
    <?php endif; ?>

    <div class="section total">
        <span class="label">Diferencia</span>
        <span class="value" style="color: <?= $diferencia >= 0 ? 'green' : 'red' ?>">
            $<?= Format::moneyRoundedFromCents($diferencia) ?> <?= ($diferencia >= 0 ? '+' : '') ?>
        </span>
    </div>

    <div class="footer">
        <p>Total de movimientos: <?= count($apertura['movimientos'] ?? []) ?> registros</p>
        <p>Impreso el <?= date('d/m/Y H:i') ?></p>
    </div>
</div>
</body>
</html>