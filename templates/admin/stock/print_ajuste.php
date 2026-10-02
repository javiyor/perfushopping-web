<?php
$ajustes = $ajustes ?? [];
$empresa = $empresa ?? [];
$sucursal = $sucursal ?? [];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Movimiento de ajuste de stock</title>
    <style>
        @page { size: auto; margin: 0mm 0mm 0mm 0mm; }
        body { font-family: 'Courier New', monospace; font-size: 10px; margin: 0; padding: 0; background: white; }
        .ticket { width: 696px; margin: 0 auto; }
        .mov { page-break-after: always; padding: 6px 0; }
        .mov:last-child { page-break-after: auto; }
        .header { text-align: center; border-bottom: 2px solid #333; padding: 5px 0; margin-bottom: 5px; }
        .header h1 { font-size: 14px; margin: 0; font-weight: bold; }
        .header p { font-size: 10px; margin: 2px 0 0 0; }
        .row { overflow: hidden; border-bottom: 1px solid #ccc; padding: 3px 0; }
        .row .label { font-weight: bold; float: left; width: 40%; }
        .row .value { float: right; width: 60%; text-align: right; }
        table.det { width: 100%; border-collapse: collapse; margin: 5px 0; }
        table.det th { background: #ddd; text-align: left; font-size: 9px; padding: 3px; }
        table.det td { font-size: 9px; padding: 3px; border-bottom: 1px solid #ddd; }
        .footer { text-align: center; font-size: 8px; margin-top: 8px; border-top: 1px solid #333; padding: 4px 0; }
        @media print { .noprint { display: none; } }
    </style>
</head>
<body>
<div class="noprint" style="text-align:center;padding:8px;font-family:sans-serif">
    <button onclick="window.print()" style="padding:6px 18px;cursor:pointer">Imprimir</button>
</div>
<div class="ticket">
    <?php foreach ($ajustes as $aj): ?>
    <div class="mov">
        <div class="header">
            <h1><?= htmlspecialchars($empresa['razon_social'] ?? 'PERFUSHOPPING') ?></h1>
            <p>Movimiento de ajuste de stock #<?= (int)($aj['id'] ?? 0) ?></p>
            <p><?= htmlspecialchars($sucursal['nombre'] ?? '') ?> · Fecha: <?= htmlspecialchars((string)($aj['fecha'] ?? '')) ?></p>
        </div>
        <div class="row"><span class="label">Motivo</span><span class="value"><?= htmlspecialchars((string)($aj['motivo'] ?? '')) ?></span></div>
        <div class="row"><span class="label">Desde</span><span class="value"><?= htmlspecialchars((string)($aj['depo_desde'] ?? '—')) ?></span></div>
        <div class="row"><span class="label">Hasta</span><span class="value"><?= htmlspecialchars((string)($aj['depo_hasta'] ?? '—')) ?></span></div>
        <table class="det">
            <thead><tr><th>Producto</th><th>Variedad</th><th>Cód. barra</th><th style="text-align:center">Cant.</th></tr></thead>
            <tbody>
            <?php foreach (($aj['items'] ?? []) as $it): ?>
                <tr>
                    <td><?= htmlspecialchars((string)(($it['produ'] ?? '') . ' (' . ($it['codprodu'] ?? '') . ')')) ?></td>
                    <td><?= htmlspecialchars((string)($it['nomgusto'] ?? '—')) ?></td>
                    <td><?= htmlspecialchars((string)($it['codscan'] ?? '—')) ?></td>
                    <td style="text-align:center"><?= (int)($it['canti'] ?? 0) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="footer">
            <p>Ajuste #<?= (int)($aj['id'] ?? 0) ?> · Impreso el <?= date('d/m/Y H:i') ?></p>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<script>window.addEventListener('load', function() { setTimeout(function() { window.print(); }, 300); });</script>
</body>
</html>
