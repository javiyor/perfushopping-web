<?php
use Perfushopping\Web\Support\Barcode;

$product = $product ?? [];
$variants = $variants ?? [];
$showPrice = $showPrice ?? true;
$showDesc = $showDesc ?? true;
$showVariant = $showVariant ?? true;
$quantities = $quantities ?? [];
$selectedVariants = $selectedVariants ?? [];

$productName = (string)($product['produ'] ?? '');
$descripcion = trim((string)($product['observ'] ?? ''));
$priceGross = number_format((float)($product['precio'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
$priceGrossWs = number_format((float)($product['precio1'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
$idprodu = (int)($product['idprodu'] ?? 0);
$isPost = count($selectedVariants) > 0;
?>
<!DOCTYPE html>
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
    .selection-form { display:none !important; }
}
@media screen {
    body { padding:10px; background:#f5f5f5; }
    .config-bar { display:block; background:#fff; border-radius:8px; padding:10px; margin-bottom:10px; max-width:180mm; font-family:Arial,sans-serif; font-size:13px; }
    .config-bar label { margin-right:15px; cursor:pointer; }
    .config-bar .btn-print { margin-left:10px; }
    .label-grid { background:#fff; border-radius:8px; padding:4mm; max-width:180mm; }
    .label { border:1px solid #ddd; border-radius:2px; }
    .selection-form { max-width: 180mm; margin-bottom: 20px; padding: 15px; background: #fff; border-radius: 8px; border: 1px solid #ddd; }
    .selection-form h4 { margin: 0 0 15px; font-size: 14px; }
    .variant-row { display: flex; align-items: center; gap: 10px; padding: 8px; margin-bottom: 8px; background: #f8f9fa; border-radius: 4px; }
    .variant-row input[type="checkbox"] { width: 18px; height: 18px; }
    .variant-row .variant-info { flex: 1; font-size: 13px; }
    .variant-row .variant-name { font-weight: bold; }
    .variant-row .variant-code { font-size: 11px; color: #666; }
    .variant-row input[type="number"] { width: 60px; padding: 4px; font-size: 13px; }
    .selection-form .btn { margin-top: 15px; }
    .label-grid { background:#fff; border-radius:8px; padding:4mm; max-width:180mm; }
    .label { border:1px solid #ddd; border-radius:2px; }
}
</style>
</head><body>
<?php if (!$isPost): ?>
<!-- Formulario de selección -->
<div class="selection-form">
    <h4>Seleccionar variedades e indicar cantidades</h4>
    <form method="post" action="/admin/productos/etiquetas/<?= $idprodu ?>">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
        <?php foreach ($variants as $v): 
            $variantId = (int)($v['idcodgusto'] ?? 0);
            $variantName = htmlspecialchars((string)($v['nomgusto'] ?? ''));
            $codscan = htmlspecialchars((string)($v['codscan'] ?? ''));
            $stock = (int)($v['stockact'] ?? 0);
        ?>
        <div class="variant-row">
            <input type="checkbox" name="variants[]" value="<?= $variantId ?>" checked>
            <div class="variant-info">
                <div class="variant-name"><?= $variantName ?></div>
                <?php if ($codscan): ?>
                    <div class="variant-code">Cód: <?= $codscan ?> | Stock: <?= $stock ?></div>
                <?php else: ?>
                    <div class="variant-code">Stock: <?= $stock ?></div>
                <?php endif; ?>
            </div>
            <label>Cant:
                <input type="number" name="qty[<?= $variantId ?>]" value="1" min="1" max="999" style="width:60px">
            </label>
        </div>
        <?php endforeach; ?>
        <button type="submit" class="btn btn-accent btn-print"><i class="bi bi-print"></i> Generar e imprimir etiquetas</button>
    </form>
</div>
<?php else: ?>
<!-- Etiquetas para imprimir -->
<div class="config-bar" id="configBar">
    <label><input type="checkbox" id="chkPrice" <?= $showPrice ? 'checked' : '' ?> onchange="toggleLabels()"> Precio</label>
    <label><input type="checkbox" id="chkDesc" <?= $showDesc ? 'checked' : '' ?> onchange="toggleLabels()"> Descripci&oacute;n</label>
    <label><input type="checkbox" id="chkVariant" <?= $showVariant ? 'checked' : '' ?> onchange="toggleLabels()"> Variedad</label>
    <button class="btn-print" onclick="duplicateLabels()">Imprimir</button>
</div>
<div class="label-grid" id="labelGrid">
<?php foreach ($variants as $v):
    $variantId = (int)($v['idcodgusto'] ?? 0);
    $qty = max(1, (int)($quantities[$variantId] ?? 1));
    $codscan = trim((string)($v['codscan'] ?? ''));
    $ean = ($codscan !== '' && strlen($codscan) === 13 && ctype_digit($codscan))
        ? $codscan
        : Barcode::ean13((int)($v['idcodgusto'] ?? 0));
    $variantName = htmlspecialchars((string)($v['nomgusto'] ?? ''));
?>
    <?php for ($i = 0; $i < $qty; $i++): ?>
    <div class="label">
        <div class="row-produ"><?= htmlspecialchars(mb_substr($productName, 0, 50)) ?></div>
        <div class="row-id">#<?= $idprodu ?><?php if ($variantName): ?> / <span class="name-variant"><?= $variantName ?></span><?php endif; ?></div>
        <div class="row-price">$<?= $priceGross ?></div>
        <div class="row-price-ws">May: $<?= $priceGrossWs ?></div>
        <div class="barcode-wrap"><?= Barcode::ean13Svg($ean, 28) ?></div>
    </div>
    <?php endfor; ?>
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
</script>
<?php endif; ?>
<script>window.print();</script>
</body></html>