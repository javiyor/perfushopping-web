<?php
use Perfushopping\Web\Support\Barcode;

$product = $product ?? [];
$variants = $variants ?? [];
$showPrice = $showPrice ?? true;
$showDesc = $showDesc ?? true;
$showVariant = $showVariant ?? true;

$productName = htmlspecialchars((string)($product['produ'] ?? ''));
$descripcion = trim((string)($product['observ'] ?? ''));
$priceGross = number_format((float)($product['precio'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
$priceGrossWs = number_format((float)($product['precio1'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Etiquetas - <?= $productName ?></title>
<style>
@page { margin:0; size:80mm 297mm; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,Helvetica,sans-serif; width:80mm; }
.config-bar { display:none; }
.label-grid { display:flex; flex-wrap:wrap; padding:2mm 0 0 2mm; }
.label {
    width:35mm; height:20mm; margin:0 0 2mm 2mm;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    border:1px dashed #ccc; overflow:hidden; padding:1mm;
}
.label .name { font-size:7px; font-weight:bold; text-align:center; line-height:1.1; max-height:5mm; overflow:hidden; word-break:break-all; }
.label .desc { font-size:6px; color:#555; text-align:center; line-height:1.1; max-height:4mm; overflow:hidden; margin:1px 0; }
.label .price { font-size:10px; font-weight:bold; margin:1px 0; }
.label .price-ws { font-size:6px; color:#666; }
.label .barcode-wrap { margin-top:1px; }
@media print {
    .label { border:none; border:1px dashed #ccc; }
    .config-bar { display:none !important; }
}
@media screen {
    body { padding:10px; background:#f5f5f5; }
    .config-bar { display:block; background:#fff; border-radius:8px; padding:10px; margin-bottom:10px; max-width:180mm; font-family:Arial,sans-serif; font-size:13px; }
    .config-bar label { margin-right:15px; cursor:pointer; }
    .config-bar .btn-print { margin-left:10px; }
    .label-grid { background:#fff; border-radius:8px; padding:4mm; max-width:180mm; }
    .label { border:1px solid #ddd; border-radius:2px; }
    .no-print { display:block; }
}
</style>
</head><body>
<div class="config-bar" id="configBar">
    <label><input type="checkbox" id="chkPrice" <?= $showPrice ? 'checked' : '' ?> onchange="toggleLabels()"> Precio</label>
    <label><input type="checkbox" id="chkDesc" <?= $showDesc ? 'checked' : '' ?> onchange="toggleLabels()"> Descripción</label>
    <label><input type="checkbox" id="chkVariant" <?= $showVariant ? 'checked' : '' ?> onchange="toggleLabels()"> Variedad</label>
    <button class="btn-print" onclick="window.print()">Imprimir</button>
</div>
<div class="label-grid" id="labelGrid">
<?php foreach ($variants as $v):
    $codscan = trim((string)($v['codscan'] ?? ''));
    $ean = ($codscan !== '' && strlen($codscan) === 13 && ctype_digit($codscan))
        ? $codscan
        : Barcode::ean13((int)($v['idcodgusto'] ?? 0));
    $variantName = htmlspecialchars((string)($v['nomgusto'] ?? ''));
    $displayName = htmlspecialchars($productName);
    $displayFull = $variantName ? $productName . ' - ' . $variantName : $productName;
    if (mb_strlen($displayFull) > 25) $displayFull = mb_substr($displayFull, 0, 24) . '…';
?>
    <div class="label">
        <div class="name"><span class="name-base"><?= htmlspecialchars($productName) ?></span><?php if ($variantName): ?> <span class="name-variant">- <?= $variantName ?></span><?php endif; ?></div>
        <?php if ($descripcion !== '' && $showDesc): ?>
        <div class="desc"><?= htmlspecialchars(mb_substr($descripcion, 0, 40)) ?></div>
        <?php endif; ?>
        <div class="price">$<?= $priceGross ?></div>
        <div class="price-ws">May: $<?= $priceGrossWs ?></div>
        <div class="barcode-wrap"><?= Barcode::ean13Svg($ean) ?></div>
    </div>
<?php endforeach; ?>
</div>
<script>
function toggleLabels() {
    const showPrice = document.getElementById('chkPrice').checked;
    const showDesc = document.getElementById('chkDesc').checked;
    const showVar = document.getElementById('chkVariant').checked;
    document.querySelectorAll('#labelGrid .label').forEach(function(el) {
        el.querySelector('.price').style.display = showPrice ? '' : 'none';
        el.querySelector('.price-ws').style.display = showPrice ? '' : 'none';
        const descEl = el.querySelector('.desc');
        if (descEl) descEl.style.display = showDesc ? '' : 'none';
        const varEl = el.querySelector('.name-variant');
        if (varEl) varEl.style.display = showVar ? '' : 'none';
    });
}
toggleLabels();
</script>
<script>window.print();</script>
</body></html>
