<?php
use Perfushopping\Web\Support\Barcode;

$product = $product ?? [];
$variants = $variants ?? [];
$productName = htmlspecialchars((string)($product['produ'] ?? ''));
$priceGross = number_format((float)($product['precio'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
$priceGrossWs = number_format((float)($product['precio1'] ?? 0) * (1 + ((float)($product['tiva'] ?? 0) / 100)), 0, ',', '.');
?><!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Etiquetas - <?= $productName ?></title>
<style>
@page { margin:0; size:80mm 297mm; }
* { margin:0; padding:0; box-sizing:border-box; }
body { font-family:Arial,Helvetica,sans-serif; width:80mm; }
.label-grid { display:flex; flex-wrap:wrap; padding:2mm 0 0 2mm; }
.label {
    width:35mm; height:20mm; margin:0 0 2mm 2mm;
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    border:1px dashed #ccc; overflow:hidden; padding:1mm;
}
.label .name { font-size:7px; font-weight:bold; text-align:center; line-height:1.1; max-height:5mm; overflow:hidden; word-break:break-all; }
.label .price { font-size:10px; font-weight:bold; margin:1px 0; }
.label .price-ws { font-size:6px; color:#666; }
.label .barcode-wrap { margin-top:1px; }
@media print {
    .label { border:none; border:1px dashed #ccc; }
}
@media screen {
    body { padding:10px; background:#f5f5f5; }
    .label-grid { background:#fff; border-radius:8px; padding:4mm; max-width:180mm; }
    .label { border:1px solid #ddd; border-radius:2px; }
}
</style>
</head><body>
<div class="label-grid">
<?php foreach ($variants as $v):
    $ean = Barcode::ean13((int)($v['idcodgusto'] ?? 0));
    $variantName = htmlspecialchars((string)($v['nomgusto'] ?? ''));
    $displayName = $variantName ? $productName . ' - ' . $variantName : $productName;
    if (mb_strlen($displayName) > 25) $displayName = mb_substr($displayName, 0, 24) . '…';
?>
    <div class="label">
        <div class="name"><?= $displayName ?></div>
        <div class="price">$<?= $priceGross ?></div>
        <div class="price-ws">May: $<?= $priceGrossWs ?></div>
        <div class="barcode-wrap"><?= Barcode::ean13Svg($ean) ?></div>
    </div>
<?php endforeach; ?>
</div>
<script>window.print();</script>
</body></html>
