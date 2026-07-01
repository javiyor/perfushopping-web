<?php
use Perfushopping\Web\Support\Format;

$recibo = $recibo ?? null;
$pagos = $pagos ?? [];
$formato = in_array($formato ?? '', ['a4','80mm','58mm']) ? $formato : '80mm';
if (!$recibo) exit;

$isTicket = $formato !== 'a4';
$bodyFontSize = $formato === '58mm' ? '10px' : '12px';

$formaPagoLabels = [
    'efectivo'=>'Efectivo', 'transferencia'=>'Transferencia', 'tarjeta_credito'=>'Tarjeta crédito',
    'tarjeta_debito'=>'Tarjeta débito', 'mercadopago'=>'Mercado Pago', 'cuenta_corriente'=>'Cta. cte.',
    'cheque'=>'Cheque',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Recibo <?= htmlspecialchars($recibo['codigo'] ?? '') ?></title>
    <style>
        @page { margin:<?= $isTicket ? '10mm 5mm' : '20mm 15mm' ?>; }
        * { box-sizing:border-box; }
        body {
            font-family:<?= $isTicket ? "'Courier New',Courier,monospace" : "'Segoe UI',Arial,sans-serif" ?>;
            font-size:<?= $bodyFontSize ?>; line-height:1.4; color:#000;
            width:<?= $formato === '58mm' ? '58mm' : ($formato === '80mm' ? '80mm' : '210mm') ?>; margin:0 auto;
        }
        h1 { font-size:<?= $formato === '58mm' ? '13px' : '16px' ?>; text-align:center; margin:0 0 4px; }
        .header { text-align:center; margin-bottom:10px; }
        .header .logo { max-width:<?= $isTicket ? '100px' : '150px' ?>; margin-bottom:4px; }
        .header .razon { font-size:<?= $formato === 'a4' ? '18px' : '14px' ?>; font-weight:bold; }
        .header .data { font-size:<?= $formato === '58mm' ? '9px' : '11px' ?>; text-align:left; }
        hr { border:none; border-top:1px dashed #000; margin:6px 0; }
        table { width:100%; border-collapse:collapse; font-size:<?= $formato === '58mm' ? '9px' : ($isTicket ? '11px' : '12px') ?>; }
        th, td { padding:<?= $isTicket ? '2px 4px' : '6px 8px' ?>; text-align:left; }
        th { border-bottom:1px solid #000; <?= $formato === 'a4' ? 'background:#f5f5f5;' : '' ?> }
        .text-right { text-align:right; }
        .text-center { text-align:center; }
        .total-row { font-size:<?= $formato === '58mm' ? '13px' : '16px' ?>; font-weight:bold; border-top:2px solid #000; padding-top:4px; margin-top:4px; }
        .footer { text-align:center; margin-top:12px; font-size:10px; color:#666; }
        <?php if ($formato === 'a4'): ?>
        .cliente-box { background:#f9f9f9; padding:8px 12px; border-radius:4px; margin-bottom:8px; }
        <?php endif; ?>
        @media print {
            body { margin:0; padding:0; width:100%; }
            .no-print { display:none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img class="logo" src="/assets/brand/logo-header.png" alt="Perfushopping" onerror="this.style.display='none'" />
        <div class="razon">PERFUSHOPPING S.R.L.</div>
        <div class="data" style="text-align:center">CUIT: 30-12345678-9</div>
        <hr />
        <h1>RECIBO</h1>
        <div class="data" style="text-align:center">Código: <strong><?= htmlspecialchars($recibo['codigo'] ?? '') ?></strong></div>
        <div class="data" style="text-align:center">Fecha: <?= htmlspecialchars($recibo['fecha'] ?? '') ?></div>
        <hr />
        <div class="data<?= $formato === 'a4' ? ' cliente-box' : '' ?>">
            <strong>Recibí de:</strong> <?= htmlspecialchars($recibo['cliente_nombre'] ?? '-') ?><br />
            <?php if ($recibo['cliente_cuit'] ?? ''): ?>
            <strong>CUIT:</strong> <?= htmlspecialchars($recibo['cliente_cuit']) ?><br />
            <?php endif; ?>
            <strong>Concepto:</strong> <?= htmlspecialchars($recibo['concepto'] ?? '-') ?>
        </div>
    </div>
    <hr />

    <?php if ($pagos): ?>
    <table>
        <thead>
            <tr>
                <th>Factura</th>
                <th class="text-right">Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pagos as $pg): ?>
            <tr>
                <td><?= htmlspecialchars((string)($pg['factura_codigo'] ?? 'Pago a cuenta')) ?></td>
                <td class="text-right"><?= htmlspecialchars(Format::moneyFromCents((int)($pg['monto_cents'] ?? 0))) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <hr />
    <?php endif; ?>

    <div class="text-right total-row">
        <span>Total: <?= htmlspecialchars(Format::moneyFromCents((int)($recibo['monto_cents'] ?? 0))) ?></span>
    </div>

    <div class="data" style="margin-top:8px">
        <strong>Forma de pago:</strong> <?= htmlspecialchars($formaPagoLabels[$recibo['forma_pago'] ?? ''] ?? $recibo['forma_pago'] ?? '-') ?>
    </div>

    <?php if ($recibo['observaciones'] ?? ''): ?>
    <div class="data" style="margin-top:4px">
        <strong>Observaciones:</strong> <?= htmlspecialchars($recibo['observaciones']) ?>
    </div>
    <?php endif; ?>

    <hr />
    <div class="footer">
        <p>Gracias por su pago</p>
        <p>Perfushopping — www.perfushopping.com</p>
    </div>

    <div class="no-print" style="text-align:center;margin-top:20px">
        <button onclick="window.print()" style="padding:8px 24px;font-size:14px;cursor:pointer">🖨 Imprimir</button>
        <button onclick="window.close()" style="padding:8px 24px;font-size:14px;cursor:pointer">Cerrar</button>
    </div>
</body>
</html>
