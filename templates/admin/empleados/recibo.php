<?php
use Perfushopping\Web\Support\Format;

$liquidacion = $liquidacion ?? [];
$detalle = $detalle ?? [];
$adelantos = $adelantos ?? [];
$baseCents = (int)($liquidacion['sueldo_base_cents'] ?? 0);
$horasCents = (int)($liquidacion['horas_cents'] ?? 0);
$comisionCents = (int)($liquidacion['comision_cents'] ?? 0);
$totalBrutoCents = $baseCents + $horasCents + $comisionCents;
$descuentosCents = array_sum(array_map(static fn(array $a): int => (int)($a['importe_cents'] ?? 0), $adelantos));
if (!$adelantos && $totalBrutoCents > (int)($liquidacion['total_cents'] ?? 0)) {
    $descuentosCents = $totalBrutoCents - (int)$liquidacion['total_cents'];
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Recibo de sueldo - <?= htmlspecialchars($liquidacion['nombre'] ?? $liquidacion['username'] ?? '') ?></title>
    <style>
        @page { size: A4; margin: 18mm; }
        * { box-sizing: border-box; }
        body { margin: 0 auto; max-width: 780px; color: #111; font: 14px/1.5 Arial, sans-serif; }
        h1 { margin: 0 0 22px; text-align: center; font-size: 21px; letter-spacing: .04em; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 28px; margin-bottom: 22px; }
        .meta strong, .label { font-weight: 700; }
        table { width: 100%; border-collapse: collapse; margin: 12px 0; }
        th, td { padding: 8px 5px; border-bottom: 1px solid #bbb; text-align: left; }
        th:last-child, td:last-child { text-align: right; white-space: nowrap; }
        .section { margin-top: 18px; font-weight: 700; }
        .summary { margin-top: 14px; }
        .summary-row { display: flex; justify-content: space-between; padding: 6px 4px; }
        .gross { border-top: 1px solid #555; font-weight: 700; }
        .net { margin-top: 5px; border-top: 2px solid #111; border-bottom: 2px solid #111; padding: 10px 4px; font-size: 18px; font-weight: 700; }
        .dotted { border: 0; border-top: 2px dotted #555; margin: 26px 0; }
        .declaration { margin: 24px 0 35px; font-size: 15px; }
        .signatures { display: grid; grid-template-columns: 1.2fr 1fr 1fr; gap: 28px; margin-top: 52px; }
        .signature { border-top: 1px dotted #222; padding-top: 7px; text-align: center; }
        .no-print { margin: 22px 0; text-align: center; }
        .no-print button { padding: 8px 24px; cursor: pointer; }
        @media print {
            html, body { width: auto; max-width: none; }
            .no-print { display: none !important; }
            tr, .summary-row, .signatures { break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">Imprimir recibo</button></div>
    <h1>RECIBO DE SUELDO</h1>

    <div class="meta">
        <div><strong>Fecha:</strong> <?= htmlspecialchars($fechaImpresion ?? date('d/m/Y')) ?></div>
        <div><strong>Período:</strong> <?= htmlspecialchars($liquidacion['periodo'] ?? '') ?></div>
        <div style="grid-column:1 / -1"><strong>Empleado:</strong> <?= htmlspecialchars($liquidacion['nombre'] ?? $liquidacion['username'] ?? '') ?></div>
    </div>

    <div class="section">Haberes</div>
    <table>
        <tbody>
            <tr><td>Básico</td><td><?= Format::moneyFromCents($baseCents) ?></td></tr>
            <?php if ($horasCents > 0 || (float)($detalle['total_horas'] ?? 0) > 0): ?>
            <tr>
                <td>Horas trabajadas<?php if ((float)($detalle['total_horas'] ?? 0) > 0): ?> (<?= htmlspecialchars((string)$detalle['total_horas']) ?> h)<?php endif; ?></td>
                <td><?= Format::moneyFromCents($horasCents) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($comisionCents !== 0): ?>
            <tr><td>Comisiones</td><td><?= Format::moneyFromCents($comisionCents) ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary-row gross"><span>Total haberes</span><span><?= Format::moneyFromCents($totalBrutoCents) ?></span></div>

    <div class="section">Gastos y adelantos de sueldo descontados</div>
    <?php if ($adelantos): ?>
    <table>
        <thead><tr><th>Fecha</th><th>Detalle</th><th>Importe</th></tr></thead>
        <tbody>
            <?php foreach ($adelantos as $adelanto): ?>
            <tr>
                <td><?= htmlspecialchars($adelanto['fecha'] ?? '') ?></td>
                <td><?= htmlspecialchars($adelanto['descripcion'] ?? 'Adelanto de sueldo') ?></td>
                <td><?= Format::moneyFromCents((int)($adelanto['importe_cents'] ?? 0)) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php elseif ($descuentosCents > 0): ?>
    <div class="summary-row"><span>Adelantos de sueldo</span><span><?= Format::moneyFromCents($descuentosCents) ?></span></div>
    <?php else: ?>
    <p>Sin gastos ni adelantos descontados.</p>
    <?php endif; ?>

    <div class="summary-row"><span>Total gastos y adelantos descontados</span><span>− <?= Format::moneyFromCents($descuentosCents) ?></span></div>
    <div class="summary-row net"><span>Saldo a pagar</span><span><?= Format::moneyFromCents((int)($liquidacion['total_cents'] ?? ($totalBrutoCents - $descuentosCents))) ?></span></div>

    <hr class="dotted" />
    <p class="declaration">Dejo constancia que mi empleador no tiene deudas hacia mi persona por el trabajo realizado.</p>
    <div class="signatures">
        <div class="signature">Firma</div>
        <div class="signature">Aclaración</div>
        <div class="signature">DNI</div>
    </div>
    <hr class="dotted" />
</body>
</html>
