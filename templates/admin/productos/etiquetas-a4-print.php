<?php
$labels = $labels ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Etiquetas de precio A4</title>
<style>
@page { size: A4; margin: 9mm 7mm; }
* { box-sizing: border-box; }
body { margin: 0; font-family: Arial, Helvetica, sans-serif; color: #000; }
.sheet {
    display: grid;
    grid-template-columns: repeat(4, 48mm);
    grid-auto-rows: 30mm;
    gap: 1mm;
}
.label {
    border: 0.3mm dashed #999;
    padding: 1mm 1.5mm;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
}
.lbl-top { font-size: 7.5pt; line-height: 1.15; }
.lbl-id { font-weight: bold; }
.lbl-var { font-size: 7pt; color: #333; }
.lbl-code { font-family: 'Courier New', monospace; font-size: 8pt; letter-spacing: 0.3px; }
.lbl-price { font-size: 12pt; font-weight: bold; text-align: right; line-height: 1; }
.toolbar {
    position: sticky; top: 0; z-index: 10;
    background: #fff; padding: 8px 12px;
    border-bottom: 1px solid #ddd;
    display: flex; gap: 12px; align-items: center;
    font-size: 13px;
}
.toolbar a { color: #555; }
@media print {
    .toolbar { display: none !important; }
    .label { border-color: #ccc; }
}
</style>
</head>
<body>
<div class="toolbar">
    <strong><?= count($labels) ?> etiqueta(s) — A4, 4 por fila (~48×30 mm)</strong>
    <button type="button" onclick="window.print()">Imprimir</button>
    <a href="/admin/productos/etiquetas-a4">Volver al listado</a>
</div>
<div class="sheet">
<?php foreach ($labels as $l): ?>
    <div class="label">
        <div>
            <div class="lbl-top"><span class="lbl-id">#<?= (int)($l['idprodu'] ?? 0) ?></span> <?= htmlspecialchars(mb_substr((string)($l['produ'] ?? ''), 0, 42)) ?></div>
            <?php if ((string)($l['nomgusto'] ?? '') !== ''): ?>
                <div class="lbl-var"><?= htmlspecialchars(mb_substr((string)$l['nomgusto'], 0, 28)) ?></div>
            <?php endif; ?>
        </div>
        <?php if ((string)($l['codscan'] ?? '') !== ''): ?>
            <div class="lbl-code"><?= htmlspecialchars((string)$l['codscan']) ?></div>
        <?php endif; ?>
        <div class="lbl-price">$<?= number_format((float)($l['precio'] ?? 0), 0, ',', '.') ?></div>
    </div>
<?php endforeach; ?>
</div>
</body>
</html>
