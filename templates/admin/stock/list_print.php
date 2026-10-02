<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$codepar = (int)($codepar ?? 0);
$stockFilter = (string)($stockFilter ?? '');
$codrub = (int)($codrub ?? 0);
$codsub = (int)($codsub ?? 0);
$codprove = (string)($codprove ?? '');
$iddepo = (int)($iddepo ?? 0);
$desde = (string)($desde ?? '');
$hasta = (string)($hasta ?? '');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Listado de Stock - Imprimir</title>
    <style>
        @page {
            size: auto;
            margin: 10mm 15mm 10mm 15mm;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 9px;
            margin: 0;
            padding: 0;
            background: white;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding: 5px 0;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 12px;
            margin: 0;
            font-weight: bold;
        }
        .header p {
            font-size: 9px;
            margin: 2px 0 0 0;
        }
        .filtros {
            text-align: center;
            font-size: 8px;
            margin-bottom: 8px;
            color: #666;
        }
        .filtros div {
            margin: 2px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }
        th {
            background: #ddd;
            text-align: left;
            padding: 4px;
            font-size: 8px;
            border: 1px solid #ccc;
        }
        td {
            padding: 3px;
            font-size: 8px;
            border: 1px solid #ccc;
        }
        .total-row {
            font-weight: bold;
            background: #e7e7e7;
        }
        .small {
            font-size: 7px;
        }
    </style>
</head>
<body>
<div class="header">
    <h1>Listado de Stock</h1>
    <p>Perfushopping - Administrador</p>
</div>

<div class="filtros">
    <div>Producto: <?= htmlspecialchars($q) ?: 'Todos' ?></div>
    <div>Rubro: <?= $codrub > 0 ? 'Rubro ' . $codrub : '' ?></div>
    <div>Subrubro: <?= $codsub > 0 ? 'Subrubro ' . $codsub : '' ?></div>
    <div>Proveedor: <?= htmlspecialchars($codprove) ?: 'Todos' ?></div>
    <div>Depósito: <?= $iddepo > 0 ? 'Depósito ' . $iddepo : 'Todos' ?></div>
    <div>Fecha: <?= $desde ?> - <?= $hasta ?></div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:40px">#</th>
            <th style="width:200px">Producto</th>
            <th style="width:100px">Variedad</th>
            <th style="width:80px">Cód. Barras</th>
            <th style="width:100px">Cód. Proveedor</th>
            <th style="width:150px">Proveedor</th>
            <th style="width:60px">Stock</th>
            <th style="width:60px">Ventas</th>
            <th style="width:80px">Precio</th>
            <th style="width:80px">Costo</th>
        </tr>
    </thead>
    <tbody>
        <?php if (!$list): ?>
        <tr><td colspan="10" class="text-center small">No hay stock con los filtros aplicados.</td></tr>
        <?php else: ?>
        <?php $i = 1; ?>
        <?php foreach ($list as $p): ?>
        <tr>
            <td class="small"><?= $i++ ?></td>
            <td class="small" style="text-align:left;">
                <?= htmlspecialchars($p['produ'] ?? '') ?><br>
                <span class="text-muted small">Cód.: <?= htmlspecialchars($p['codprodu'] ?? '') ?></span>
            </td>
            <td class="small"><?= htmlspecialchars($p['nomgusto'] ?? '') ?: '—' ?></td>
            <td class="small"><?= htmlspecialchars($p['codscan'] ?? '') ?: '—' ?></td>
            <td class="small"><?= htmlspecialchars($p['codprodup'] ?? '') ?: '—' ?></td>
            <td class="small"><?= htmlspecialchars($p['nomprovee'] ?? '') ?: '—' ?></td>
            <td class="small text-center" style="color:<?= ($p['stock_deposito'] ?? 0) < 10 ? 'red' : 'black' ?>"><?= (int)($p['stock_deposito'] ?? 0) ?></td>
            <td class="small text-center"><?= (int)($p['total_vendido'] ?? 0) ?></td>
            <td class="small text-right">$<?= number_format((float)($p['precio'] ?? 0), 2, ',', '.') ?></td>
            <td class="small text-right">$<?= number_format((float)($p['precomp'] ?? 0), 2, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div style="margin-top: 10px; font-size: 8px; color: #666; text-align: center;">
    Página generada el <?= date('d/m/Y H:i') ?><br>
    Total de registros: <?= count($list) ?>
</div>
</body>
</html>