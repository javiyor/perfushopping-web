<?php
use Perfushopping\Web\Support\Format;

$empresa = $empresa ?? [];
$sucursal = $sucursal ?? [];
$ptoVta = (int)($ptoVta ?? 0);
$fecha = (string)($fecha ?? date('Y-m-d'));
$turno = (string)($turno ?? '');
$cajaId = (int)($cajaId ?? 0);
$estado = (string)($estado ?? '');

$montoInicial = (int)($montoInicial ?? 0);
$ventasEfectivo = (int)($ventasEfectivo ?? 0);
$totalIngresos = (int)($totalIngresos ?? 0);
$totalEgresos = (int)($totalEgresos ?? 0);
$saldoCaja = (int)($saldoCaja ?? 0);
$montoCierre = (int)($montoCierre ?? 0);
$montoRetirado = (int)($montoRetirado ?? 0);
$proximaApertura = (int)($proximaApertura ?? 0);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Resumen cierre de caja #<?= $cajaId ?></title>
    <style>
        @page { size: auto; margin: 0mm 0mm 0mm 0mm; }
        body { font-family: 'Courier New', monospace; font-size: 10px; margin: 0; padding: 0; background: white; }
        .ticket { width: 696px; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #333; padding: 5px 0; margin-bottom: 5px; }
        .header h1 { font-size: 14px; margin: 0; font-weight: bold; }
        .header p { font-size: 10px; margin: 2px 0 0 0; }
        .section { border-bottom: 1px solid #ccc; padding: 3px 0; margin: 3px 0; overflow: hidden; }
        .section .label { font-weight: bold; float: left; width: 50%; }
        .section .value { float: right; width: 50%; text-align: right; }
        .total { border-top: 2px solid #333; padding: 5px 0; margin: 5px 0; font-size: 12px; font-weight: bold; overflow: hidden; }
        .total .label { float: left; width: 50%; }
        .total .value { float: right; width: 50%; text-align: right; }
        .footer { text-align: center; font-size: 8px; margin-top: 8px; border-top: 1px solid #333; padding: 4px 0; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body>
<div class="noprint" style="text-align:center;padding:8px;font-family:sans-serif">
    <button onclick="window.print()" style="padding:6px 18px;cursor:pointer">Imprimir</button>
    <a href="/admin/caja" style="margin-left:8px">Volver a Caja</a>
</div>
<div class="ticket">
    <div class="header">
        <h1><?= htmlspecialchars($empresa['razon_social'] ?? 'PERFUSHOPPING') ?></h1>
        <p>Resumen de cierre de caja #<?= $cajaId ?> — <?= htmlspecialchars($estado) ?></p>
        <p>Sucursal: <?= htmlspecialchars($sucursal['nombre'] ?? $sucursal['nomsuc'] ?? '') ?> · Pto. vta.: <?= $ptoVta ?: '—' ?></p>
        <p>Fecha: <?= htmlspecialchars($fecha) ?> · Turno: <?= htmlspecialchars(ucfirst($turno)) ?></p>
    </div>

    <div class="section">
        <span class="label">Caja inicial</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($montoInicial) ?></span>
    </div>

    <div class="section">
        <span class="label">Ingresos ef. (ventas)</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($ventasEfectivo) ?></span>
    </div>

    <div class="section">
        <span class="label">Ingresos ef. (movim.)</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($totalIngresos) ?></span>
    </div>

    <div class="section">
        <span class="label">Egresos ef.</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($totalEgresos) ?></span>
    </div>

    <div class="total">
        <span class="label">Saldo caja</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($saldoCaja) ?></span>
    </div>

    <div class="section">
        <span class="label">Monto cierre (contado)</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($montoCierre) ?></span>
    </div>

    <div class="section">
        <span class="label">Pje. a Caja Gral.</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($montoRetirado) ?></span>
    </div>

    <div class="section">
        <span class="label">Saldo próx. apertura</span>
        <span class="value">$<?= Format::moneyRoundedFromCents($proximaApertura) ?></span>
    </div>

    <div class="footer">
        <p>Caja #<?= $cajaId ?> · Impreso el <?= date('d/m/Y H:i') ?></p>
        <p><?= htmlspecialchars($empresa['sitio_web'] ?? 'www.perfushopping.com.ar') ?></p>
    </div>
</div>
<script>window.addEventListener('load', function() { setTimeout(function() { window.print(); }, 300); });</script>
</body>
</html>
