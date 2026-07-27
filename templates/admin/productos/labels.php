<?php
use Perfushopping\Web\Support\Barcode;

$product = $product ?? [];
$variants = $variants ?? [];
$showPrice = $showPrice ?? true;
$showDesc = $showDesc ?? true;
$showVariant = $showVariant ?? true;

$productName = (string)($product['produ'] ?? '');
$descripcion = trim((string)($product['observ'] ?? ''));
$priceGross = number_format((float)($product['precio'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
$priceGrossWs = number_format((float)($product['precio1'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
$idprodu = (int)($product['idprodu'] ?? 0);
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Etiquetas - <?= htmlspecialchars(mb_substr($productName, 0, 30)) ?></title>
<style>
@page { margin:0; size:80mm 297mm; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,Helvetica,sans-serif; width:80mm; }
.config-bar { display:none; }
.label-grid { display:flex; flex-wrap:wrap; padding:2mm 0 0 2mm; }
.label {
    width:35mm; min-height:16mm; margin:0 0 2mm 2mm;
    display:flex; flex-direction:column; align-items:center;
    border:1px dashed #ccc; overflow:hidden; padding:0.8mm;
}
.label .row-produ { font-size:6px; font-weight:bold; text-align:center; line-height:1.15; max-height:7mm; overflow:hidden; word-break:break-all; width:100%; }
.label .row-id { font-size:6px; color:#555; text-align:center; margin:1px 0; }
.label .row-price { font-size:10px; font-weight:bold; margin:1px 0; }
.label .row-price-ws { font-size:6px; color:#666; }
.label .barcode-wrap { margin-top:1px; line-height:0; }
@media print {
    .label { border:none; }
    .config-bar { display:none !important; }
}
@media screen {
    body { padding:10px; background:#f5f5f5; }
    .config-bar { display:block; background:#fff; border-radius:8px; padding:10px; margin-bottom:10px; max-width:180mm; font-family:Arial,sans-serif; font-size:13px; }
    .config-bar label { margin-right:15px; cursor:pointer; }
    .config-bar .btn-print { margin-left:10px; }
    .label-grid { background:#fff; border-radius:8px; padding:4mm; max-width:180mm; }
    .label { border:1px solid #ddd; border-radius:2px; }
}
</style>
</head><body>
<div class="config-bar" id="configBar">
    <label><input type="checkbox" id="chkPrice" <?= $showPrice ? 'checked' : '' ?> onchange="toggleLabels()"> Precio</label>
    <label><input type="checkbox" id="chkDesc" <?= $showDesc ? 'checked' : '' ?> onchange="toggleLabels()"> Descripción</label>
    <label><input type="checkbox" id="chkVariant" <?= $showVariant ? 'checked' : '' ?> onchange="toggleLabels()"> Variedad</label>
    <label>Cant: <input type="number" id="qtyLabels" value="1" min="1" max="99" style="width:40px"></label>
    <button class="btn-print" onclick="duplicateLabels()">Imprimir</button>
</div>
<div class="label-grid" id="labelGrid">
<?php foreach ($variants as $v):
    $codscan = trim((string)($v['codscan'] ?? ''));
    $ean = ($codscan !== '' && strlen($codscan) === 13 && ctype_digit($codscan))
        ? $codscan
        : Barcode::ean13((int)($v['idcodgusto'] ?? 0));
    $variantName = htmlspecialchars((string)($v['nomgusto'] ?? ''));
?>
    <div class="label">
        <div class="row-produ"><?= htmlspecialchars(mb_substr($productName, 0, 50)) ?></div>
        <div class="row-id">#<?= $idprodu ?><?php if ($variantName): ?> / <span class="name-variant"><?= $variantName ?></span><?php endif; ?></div>
        <div class="row-price">$<?= $priceGross ?></div>
        <div class="row-price-ws">May: $<?= $priceGrossWs ?></div>
        <div class="barcode-wrap"><?= Barcode::ean13Svg($ean, 28) ?></div>
    </div>
<?php endforeach; ?>
</div>
<script>
function toggleLabels() {
    const showPrice = document.getElementById('chkPrice').checked;
    const showDesc = document.getElementById('chkDesc').checked;
    const showVar = document.getElementById('chkVariant').checked;
    document.querySelectorAll('#labelGrid .label').forEach(function(el) {
        el.querySelector('.row-price').style.display = showPrice ? '' : 'none';
        el.querySelector('.row-price-ws').style.display = showPrice ? '' : 'none';
        const varEl = el.querySelector('.name-variant');
        if (varEl) varEl.style.display = showVar ? '' : 'none';
        const idEl = el.querySelector('.row-id');
        if (idEl) {
            const txt = idEl.textContent || '';
            idEl.style.display = (!showVar && txt.includes(' / ')) ? 'none' : '';
        }
    });
}
toggleLabels();

function duplicateLabels() {
    const qty = parseInt(document.getElementById('qtyLabels').value) || 1;
    if (qty <= 1) { window.print(); return; }
    const grid = document.getElementById('labelGrid');
    const originalLabels = Array.from(grid.querySelectorAll('.label'));
    originalLabels.forEach(function(el) {
        for (var i = 1; i < qty; i++) {
            var clone = el.cloneNode(true);
            grid.appendChild(clone);
        }
    });
    window.print();
}
</script>
<script>window.print();</script>
</body></html>
