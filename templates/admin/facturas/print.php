<?php
use Perfushopping\Web\Support\Format;

$factura = $factura ?? null;
$items = $items ?? [];
$pagos = $pagos ?? [];
$formato = in_array($formato ?? '', ['a4','80mm','58mm']) ? $formato : '80mm';
if (!$factura) exit;

$autoPrint = isset($_GET['auto']) && $_GET['auto'] === '1';

$tipoLabels = ['FACT-A'=>'Factura A','FACT-B'=>'Factura B','FACT-C'=>'Factura C','NC'=>'Nota de Crédito','ND'=>'Nota de Débito'];
$formaPagoLabels = [
    'efectivo'=>'Efectivo', 'transferencia'=>'Transferencia', 'tarjeta_credito'=>'Tarjeta crédito',
    'tarjeta_debito'=>'Tarjeta débito', 'mercadopago'=>'Mercado Pago', 'cuenta_corriente'=>'Cta. cte.',
    'cheque'=>'Cheque',
];
$isTicket = $formato !== 'a4';
$bodyWidth = $formato === '58mm' ? '58mm' : ($formato === '80mm' ? '80mm' : '210mm');
$bodyFontSize = $formato === '58mm' ? '10px' : '12px';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($factura['codigo'] ?? '') ?></title>
    <style>
        @page { margin:<?= $isTicket ? '10mm 5mm' : '20mm 15mm' ?>; }
        * { box-sizing:border-box; }
        body {
            font-family:<?= $isTicket ? "'Courier New',Courier,monospace" : "'Segoe UI',Arial,sans-serif" ?>;
            font-size:<?= $bodyFontSize ?>; line-height:1.4; color:#000;
            width:<?= $bodyWidth ?>; margin:0 auto;
        }
        h1 { font-size:<?= $formato === '58mm' ? '13px' : '16px' ?>; text-align:center; margin:0 0 4px; }
        .header { text-align:center; margin-bottom:10px; }
        .header .logo { max-width:<?= $isTicket ? '100px' : '150px' ?>; margin-bottom:4px; }
        .header .razon { font-size:<?= $formato === 'a4' ? '18px' : '14px' ?>; font-weight:bold; }
        .header .data { font-size:<?= $formato === '58mm' ? '9px' : '11px' ?>; }
        hr { border:none; border-top:1px dashed #000; margin:6px 0; }
        table { width:100%; border-collapse:collapse; font-size:<?= $formato === '58mm' ? '9px' : ($isTicket ? '11px' : '12px') ?>; }
        th, td { padding:<?= $isTicket ? '2px 4px' : '6px 8px' ?>; text-align:left; }
        th { border-bottom:1px solid #000; <?= $formato === 'a4' ? 'background:#f5f5f5;' : '' ?> }
        .text-right { text-align:right; }
        .text-center { text-align:center; }
        .totals { margin-top:6px; }
        .totals .row { display:flex; justify-content:space-between; padding:2px 0; }
        .totals .total { font-size:<?= $formato === '58mm' ? '13px' : '16px' ?>; font-weight:bold; border-top:2px solid #000; padding-top:4px; margin-top:4px; }
        .footer { text-align:center; margin-top:12px; font-size:10px; color:#666; }
        .a4-layout { display:none; }
        <?php if ($formato === 'a4'): ?>
        body { width:210mm; }
        .a4-layout { display:block; }
        .totals { border:1px solid #ddd; padding:12px; border-radius:4px; }
        .cliente-box { background:#f9f9f9; padding:8px 12px; border-radius:4px; <?= $formato === 'a4' ? 'margin-bottom:8px;' : '' ?> }
        <?php endif; ?>
        @media print {
            body { margin:0; padding:0; width:100%; }
            .no-print { display:none; }
        }
    </style>
</head>
<body>
    <?php if ($autoPrint): ?><script>window.onload=function(){setTimeout(function(){window.print()},500)}</script><?php endif; ?>
    <div class="header">
        <img class="logo" src="/assets/brand/logo-header.png" alt="Perfushopping" onerror="this.style.display='none'" />
        <div class="razon">PERFUSHOPPING S.R.L.</div>
        <div class="data">CUIT: 30-12345678-9</div>
        <div class="data">Dirección fiscal</div>
        <hr />
        <h1><?= htmlspecialchars($tipoLabels[$factura['tipo_comprobante'] ?? 'FACT-B'] ?? $factura['tipo_comprobante'] ?? '') ?></h1>
        <div class="data">Código: <strong><?= htmlspecialchars($factura['codigo'] ?? '') ?></strong></div>
        <div class="data">Fecha: <?= htmlspecialchars($factura['fecha'] ?? '') ?></div>
        <?php if ($factura['punto_venta'] ?? 0): ?>
        <div class="data">Punto de venta: <?= (int)$factura['punto_venta'] ?></div>
        <?php endif; ?>
        <?php if ($factura['cae'] ?? ''): ?>
        <div class="data">CAE: <strong><?= htmlspecialchars($factura['cae']) ?></strong></div>
        <div class="data">Vto. CAE: <?= htmlspecialchars($factura['cae_vto'] ?? '') ?></div>
        <?php endif; ?>
        <hr />
        <div class="data<?= $formato === 'a4' ? ' cliente-box' : '' ?>" style="text-align:left">
            <strong>Cliente:</strong> <?= htmlspecialchars($factura['cliente_nombre'] ?? 'Consumidor Final') ?><br />
            <?php if ($factura['cliente_cuit'] ?? ''): ?>
            <strong>CUIT:</strong> <?= htmlspecialchars($factura['cliente_cuit']) ?><br />
            <?php endif; ?>
            <?php if ($factura['cliente_direc'] ?? ''): ?>
            <strong>Direc:</strong> <?= htmlspecialchars($factura['cliente_direc']) ?><br />
            <?php endif; ?>
            <strong>Cond. IVA:</strong> <?= htmlspecialchars($factura['cliente_condicion_iva'] ?? 'Consumidor Final') ?>
        </div>
    </div>
    <hr />

    <table>
        <thead>
            <tr>
                <th>Cant</th>
                <th>Producto</th>
                <th class="text-right">Precio</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $it): ?>
            <tr>
                <td class="text-center"><?= (int)($it['qty'] ?? 0) ?></td>
                <td><?= htmlspecialchars((string)($it['producto'] ?? '')) ?><?= ($it['variedad'] ?? '') ? ' (' . htmlspecialchars($it['variedad']) . ')' : '' ?></td>
                <td class="text-right"><?= htmlspecialchars(Format::moneyFromCents((int)($it['unit_price_cents'] ?? 0))) ?></td>
                <td class="text-right"><?= htmlspecialchars(Format::moneyFromCents((int)($it['total_cents'] ?? 0))) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="totals">
        <div class="row"><span>Subtotal:</span><span><?= htmlspecialchars(Format::moneyFromCents((int)($factura['subtotal_cents'] ?? 0))) ?></span></div>
        <div class="row"><span>IVA:</span><span><?= htmlspecialchars(Format::moneyFromCents((int)($factura['iva_cents'] ?? 0))) ?></span></div>
        <div class="row total"><span>TOTAL:</span><span><?= htmlspecialchars(Format::moneyFromCents((int)($factura['total_cents'] ?? 0))) ?></span></div>
    </div>

    <?php if ($pagos): ?>
    <hr />
    <div class="totals">
        <?php foreach ($pagos as $pg): ?>
        <div class="row"><span><?= htmlspecialchars($formaPagoLabels[$pg['forma_pago'] ?? ''] ?? $pg['forma_pago'] ?? '') ?>:</span><span><?= htmlspecialchars(Format::moneyFromCents((int)($pg['monto_cents'] ?? 0))) ?></span></div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <hr />
    <div class="footer">
        <p>Gracias por su compra</p>
        <p>Perfushopping — www.perfushopping.com</p>
    </div>

    <div class="no-print" style="text-align:center;margin-top:20px">
        <button onclick="window.print()" style="padding:8px 24px;font-size:14px;cursor:pointer">🖨 Imprimir</button>
        <button onclick="window.close()" style="padding:8px 24px;font-size:14px;cursor:pointer">Cerrar</button>
    </div>
</body>
</html>
