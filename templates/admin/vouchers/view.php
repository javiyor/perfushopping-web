<?php
use Perfushopping\Web\Support\Format;

$voucher = $voucher ?? [];
$empresa = $empresa ?? [];
$branches = $branches ?? [];
$formato = ($formato ?? 'a5') === 'etiqueta' ? 'etiqueta' : 'a5';
$esEtiqueta = $formato === 'etiqueta';
$phoneTo = preg_replace('/\D+/', '', (string)($voucher['destinatario_telefono'] ?? '')) ?? '';
$shareText = 'Te enviaron un voucher de regalo de ' . Format::moneyFromCents((int)($voucher['importe_cents'] ?? 0)) . ': ' . (string)($voucherUrl ?? '');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Voucher regalo <?= htmlspecialchars($voucher['codigo'] ?? '') ?></title>
    <style>
        @page { size: <?= $esEtiqueta ? '100mm 60mm' : '148mm 210mm' ?>; margin: <?= $esEtiqueta ? '3mm' : '8mm' ?>; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #252a32; background: #f2f3f5; font: 14px/1.45 Arial, sans-serif; }
        .actions { max-width: 860px; margin: 16px auto; padding: 12px; display:flex; flex-wrap:wrap; gap:8px; justify-content:center; }
        .actions a, .actions button { border:1px solid #b9bdc4; border-radius:5px; background:#fff; color:#242a32; padding:8px 12px; text-decoration:none; cursor:pointer; }
        .voucher { width: 100%; max-width: 132mm; margin: 12px auto 28px; padding: 9mm; background:#fff; border:2px solid #c6a04b; border-radius:10px; box-shadow:0 3px 18px #0002; }
        .voucher-head { display:flex; align-items:center; justify-content:center; gap:8mm; text-align:center; border-bottom:1px solid #ddd; padding-bottom:4mm; }
        .voucher-head img { max-width:35mm; max-height:20mm; object-fit:contain; }
        .company-name { font-size:17px; font-weight:bold; }
        .company-id { font-size:11px; color:#555; }
        .branches { border-bottom:1px solid #ddd; padding:3mm 0; font-size:10px; }
        .branches-title { font-weight:bold; margin-bottom:1mm; }
        .branch { margin:1mm 0; }
        .voucher-title { text-align:center; color:#99752b; font-size:24px; font-weight:bold; letter-spacing:.08em; margin:5mm 0; }
        .recipient { text-align:center; margin:5mm 0 3mm; font-size:13px; }
        .recipient-name { display:block; font-size:21px; font-weight:bold; margin-top:1mm; }
        .amount { text-align:center; font-size:23px; font-weight:bold; margin:4mm 0; }
        .sender { text-align:center; margin:3mm 0; }
        .code-box { margin:5mm 0 2mm; padding:4mm 2mm; text-align:center; border:2px dashed #b28a36; border-radius:6px; }
        .code-label { font-size:10px; text-transform:uppercase; letter-spacing:.1em; color:#555; }
        .code { display:block; margin-top:1mm; font-size:24px; font-weight:bold; letter-spacing:.12em; color:#1d2634; }
        .terms { text-align:center; color:#555; font-size:9px; margin-top:2mm; }
        .voucher-label { max-width:94mm; width:94mm; height:54mm; margin:12px auto; padding:3mm; border:1px solid #c6a04b; border-radius:3mm; box-shadow:0 2px 10px #0002; font-size:9px; line-height:1.2; overflow:hidden; }
        .voucher-label .voucher-head { justify-content:flex-start; gap:2mm; padding-bottom:1mm; }
        .voucher-label .voucher-head img { max-width:18mm; max-height:10mm; }
        .voucher-label .company-name { font-size:10px; }
        .voucher-label .company-id { font-size:7px; }
        .voucher-label .voucher-title { font-size:12px; margin:1mm 0; }
        .voucher-label .recipient { font-size:8px; margin:1mm 0; }
        .voucher-label .recipient-name { font-size:12px; margin:0; }
        .voucher-label .amount { font-size:13px; margin:1mm 0; }
        .voucher-label .sender { font-size:7px; margin:1mm 0; }
        .voucher-label .code-box { margin:1mm 0; padding:1mm; }
        .voucher-label .code-label { font-size:6px; }
        .voucher-label .code { font-size:13px; margin:0; }
        .voucher-label .terms { font-size:6px; margin:0; }
        .voucher-label .branches { display:block; border:0; padding:0; margin:.5mm 0; font-size:5px; line-height:1; max-height:8mm; overflow:hidden; }
        .voucher-label .branches-title { display:none; }
        .voucher-label .branch { margin:0; }
        @media print {
            body { background:#fff; }
            .actions { display:none !important; }
            .voucher { max-width:none; width:100%; margin:0; border:2px solid #c6a04b; border-radius:0; box-shadow:none; }
            .voucher-label { width:100%; height:100%; max-width:none; margin:0; box-shadow:none; }
        }
    </style>
</head>
<body>
    <nav class="actions">
        <button type="button" onclick="window.print()">Imprimir <?= $esEtiqueta ? 'etiqueta 10 × 6 cm' : 'voucher A5' ?></button>
        <?php if (!$esEtiqueta): ?><a target="_blank" href="/voucher/<?= rawurlencode((string)$voucher['codigo']) ?>?formato=etiqueta">Vista etiqueta 10 × 6</a><?php else: ?><a target="_blank" href="/voucher/<?= rawurlencode((string)$voucher['codigo']) ?>">Vista A5</a><?php endif; ?>
        <a href="/voucher/<?= rawurlencode((string)$voucher['codigo']) ?>?formato=<?= $esEtiqueta ? 'etiqueta' : 'a5' ?>&descargar=pdf">Descargar PDF</a>
        <a href="/voucher/<?= rawurlencode((string)$voucher['codigo']) ?>?formato=<?= $esEtiqueta ? 'etiqueta' : 'a5' ?>&descargar=jpg">Descargar JPG</a>
        <?php if ($phoneTo !== ''): ?><a target="_blank" rel="noopener" href="https://wa.me/<?= htmlspecialchars($phoneTo) ?>?text=<?= rawurlencode($shareText) ?>">Enviar por WhatsApp</a><?php endif; ?>
    </nav>

    <main class="voucher<?= $esEtiqueta ? ' voucher-label' : '' ?>">
        <header class="voucher-head">
            <?php if (!empty($logoUrl)): ?><img src="<?= htmlspecialchars($logoUrl) ?>" alt="Logo" /><?php endif; ?>
            <div>
                <div class="company-name"><?= htmlspecialchars($empresa['razon_emp'] ?? $empresa['nomemp'] ?? 'Perfushopping') ?></div>
                <?php if (!empty($empresa['cuit'])): ?><div class="company-id">CUIT: <?= htmlspecialchars($empresa['cuit']) ?></div><?php endif; ?>
                <?php if (!empty($empresa['dire_emp'])): ?><div class="company-id"><?= htmlspecialchars($empresa['dire_emp']) ?></div><?php endif; ?>
                <?php if (!empty($empresa['telefono'])): ?><div class="company-id">Tel. <?= htmlspecialchars($empresa['telefono']) ?></div><?php endif; ?>
            </div>
        </header>
        <section class="branches">
            <div class="branches-title"><?= $esEtiqueta ? 'Sucursales activas' : 'Sucursales habilitadas para el canje' ?></div>
            <?php foreach ($branches as $branch): ?>
            <div class="branch"><strong><?= htmlspecialchars($branch['nomsuc'] ?? '') ?></strong> — <?= htmlspecialchars($branch['direccion'] ?? '') ?> — Tel. <?= htmlspecialchars($branch['telefono'] ?? '') ?></div>
            <?php endforeach; ?>
        </section>
        <div class="voucher-title">VOUCHER DE REGALO</div>
        <div class="recipient">Para:<span class="recipient-name"><?= htmlspecialchars($voucher['destinatario_nombre'] ?? '') ?></span></div>
        <div class="amount">Valor: <?= Format::moneyFromCents((int)($voucher['importe_cents'] ?? 0)) ?></div>
        <div class="sender">De: <strong><?= htmlspecialchars($voucher['remitente_nombre'] ?? '') ?></strong> · Tel. <?= htmlspecialchars($voucher['remitente_telefono'] ?? '') ?></div>
        <div class="code-box"><span class="code-label">Código único de canje</span><strong class="code"><?= htmlspecialchars($voucher['codigo'] ?? '') ?></strong></div>
        <div class="terms"><?= ($voucher['estado'] ?? '') === 'activo' ? 'Canjeable una sola vez. Si la compra supera el valor del voucher, aboná la diferencia con otro medio de pago.' : 'Estado: '.htmlspecialchars($voucher['estado'] ?? '') ?></div>
    </main>
</body>
</html>
