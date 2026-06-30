<?php
use Perfushopping\Web\Support\Format;

$recibo = $recibo ?? null;
$pagos = $pagos ?? [];
if (!$recibo) exit;

$formaPagoLabels = [
    'efectivo'=>'Efectivo', 'transferencia'=>'Transferencia', 'tarjeta_credito'=>'Tarjeta crédito',
    'tarjeta_debito'=>'Tarjeta débito', 'mercadopago'=>'Mercado Pago', 'cuenta_corriente'=>'Cta. cte.',
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Recibo <?= htmlspecialchars($recibo['codigo'] ?? '') ?></title>
    <style>
        @page { margin:15mm 10mm; }
        body { font-family:'Courier New',Courier,monospace; font-size:12px; line-height:1.4; color:#000; width:80mm; margin:0 auto; }
        h1 { font-size:16px; text-align:center; margin:0 0 4px; }
        .header { text-align:center; margin-bottom:10px; }
        .header .razon { font-size:14px; font-weight:bold; }
        hr { border:none; border-top:1px dashed #000; margin:6px 0; }
        table { width:100%; border-collapse:collapse; font-size:11px; }
        th, td { padding:2px 4px; text-align:left; }
        th { border-bottom:1px solid #000; }
        .text-right { text-align:right; }
        .text-center { text-align:center; }
        .total-row { font-size:16px; font-weight:bold; border-top:2px solid #000; padding-top:4px; margin-top:4px; }
        .footer { text-align:center; margin-top:12px; font-size:10px; color:#666; }
        @media print {
            body { margin:0; padding:0; }
            .no-print { display:none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <img class="logo" src="/assets/brand/logo-header.png" alt="Perfushopping" style="max-width:120px;margin-bottom:4px" />
        <div class="razon">PERFUSHOPPING S.R.L.</div>
        <div class="data">CUIT: 30-12345678-9</div>
        <hr />
        <h1>RECIBO</h1>
        <div class="data">Código: <strong><?= htmlspecialchars($recibo['codigo'] ?? '') ?></strong></div>
        <div class="data">Fecha: <?= htmlspecialchars($recibo['fecha'] ?? '') ?></div>
        <hr />
        <div class="data" style="text-align:left">
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
