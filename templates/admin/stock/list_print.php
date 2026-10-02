<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$expHeaders = $expHeaders ?? [];
$expRows = $expRows ?? [];
$q = (string)($q ?? '');
$codepar = (int)($codepar ?? 0);
$stockFilter = (string)($stockFilter ?? '');
$codrub = (int)($codrub ?? 0);
$codsub = (int)($codsub ?? 0);
$codprove = (string)($codprove ?? '');
$iddepo = (int)($iddepo ?? 0);
$desde = (string)($desde ?? '');
$hasta = (string)($hasta ?? '');
$nCols = count($expHeaders) + 1;
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
            <?php foreach ($expHeaders as $h): ?>
                <th><?= htmlspecialchars($h) ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php if (!$expRows): ?>
        <tr><td colspan="<?= $nCols ?>" class="text-center small">No hay stock con los filtros aplicados.</td></tr>
        <?php else: ?>
        <?php $i = 1; ?>
        <?php foreach ($expRows as $row): ?>
        <tr>
            <td class="small"><?= $i++ ?></td>
            <?php foreach ($row as $v): ?>
                <td class="small"><?= htmlspecialchars($v) === '' ? '—' : htmlspecialchars($v) ?></td>
            <?php endforeach; ?>
        </tr>
        <?php endforeach; ?>
        <?php endif; ?>
    </tbody>
</table>

<div style="margin-top: 10px; font-size: 8px; color: #666; text-align: center;">
    Página generada el <?= date('d/m/Y H:i') ?><br>
    Total de registros: <?= count($expRows) ?>
</div>
</body>
</html>