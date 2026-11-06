<?php
use Perfushopping\Web\Support\Format;

$liquidacion = $liquidacion ?? [];
$detalle = $detalle ?? [];
$adelantos = $adelantos ?? [];
$empresa = $empresa ?? [];
$fechaImpresion = $fechaImpresion ?? date('d/m/Y');
$baseCents = (int)($liquidacion['sueldo_base_cents'] ?? 0);
$horasCents = (int)($liquidacion['horas_cents'] ?? 0);
$comisionCents = (int)($liquidacion['comision_cents'] ?? 0);
$totalBrutoCents = $baseCents + $horasCents + $comisionCents;
$descuentosCents = array_sum(array_map(static fn(array $a): int => (int)($a['importe_cents'] ?? 0), $adelantos));
if (!$adelantos && $totalBrutoCents > (int)($liquidacion['total_cents'] ?? 0)) {
    $descuentosCents = $totalBrutoCents - (int)$liquidacion['total_cents'];
}

$renderRecibo = static function (bool $encabezadoEmpresa) use ($empresa, $liquidacion, $detalle, $adelantos, $baseCents, $horasCents, $comisionCents, $totalBrutoCents, $descuentosCents, $fechaImpresion): string {
    ob_start();
    ?>
    <section class="recibo-copia">
        <?php if ($encabezadoEmpresa): ?>
        <header class="encabezado-empresa">
            <?php if (!empty($empresa['razon_emp'])): ?><div class="razon"><?= htmlspecialchars($empresa['razon_emp']) ?></div><?php endif; ?>
            <?php if (!empty($empresa['cuit'])): ?><div>CUIT: <?= htmlspecialchars($empresa['cuit']) ?></div><?php endif; ?>
        </header>
        <h1>RECIBO DE SUELDO</h1>
        <?php endif; ?>

        <div class="datos-empleado">
            <span><strong>Fecha:</strong> <?= htmlspecialchars($fechaImpresion ?? date('d/m/Y')) ?></span>
            <span><strong>Período:</strong> <?= htmlspecialchars($liquidacion['periodo'] ?? '') ?></span>
            <span><strong>Empleado:</strong> <?= htmlspecialchars($liquidacion['nombre'] ?? $liquidacion['username'] ?? '') ?></span>
        </div>

        <div class="detalle-recibo">
            <div>
                <div class="subtitulo">Haberes</div>
                <table>
                    <tbody>
                        <tr><td>Básico</td><td><?= Format::moneyFromCents($baseCents) ?></td></tr>
                        <?php if ($horasCents > 0 || (float)($detalle['total_horas'] ?? 0) > 0): ?>
                        <tr><td>Horas trabajadas<?php if ((float)($detalle['total_horas'] ?? 0) > 0): ?> (<?= htmlspecialchars((string)$detalle['total_horas']) ?> h)<?php endif; ?></td><td><?= Format::moneyFromCents($horasCents) ?></td></tr>
                        <?php endif; ?>
                        <?php if ($comisionCents !== 0): ?><tr><td>Comisiones</td><td><?= Format::moneyFromCents($comisionCents) ?></td></tr><?php endif; ?>
                        <tr class="total-haberes"><td>Total haberes</td><td><?= Format::moneyFromCents($totalBrutoCents) ?></td></tr>
                    </tbody>
                </table>
            </div>
            <div>
                <div class="subtitulo">Gastos y adelantos descontados</div>
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
                <table><tbody><tr><td>Adelantos de sueldo</td><td><?= Format::moneyFromCents($descuentosCents) ?></td></tr></tbody></table>
                <?php else: ?>
                <div class="sin-gastos">Sin gastos ni adelantos descontados.</div>
                <?php endif; ?>
                <div class="total-descuentos"><span>Total gastos y adelantos</span><strong>− <?= Format::moneyFromCents($descuentosCents) ?></strong></div>
            </div>
        </div>

        <div class="saldo"><span>Saldo a pagar</span><strong><?= Format::moneyFromCents((int)($liquidacion['total_cents'] ?? ($totalBrutoCents - $descuentosCents))) ?></strong></div>
        <p class="declaracion">Dejo constancia que mi empleador no tiene deudas hacia mi persona por el trabajo realizado.</p>
        <div class="firmas">
            <div>Firma</div><div>Aclaración</div><div>DNI</div>
        </div>
    </section>
    <?php
    return (string)ob_get_clean();
};
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Recibo de sueldo - <?= htmlspecialchars($liquidacion['nombre'] ?? $liquidacion['username'] ?? '') ?></title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #111; font: 10px/1.25 Arial, sans-serif; }
        .no-print { margin: 12px 0; text-align: center; }
        .no-print button { padding: 8px 24px; cursor: pointer; }
        .recibo-copia { width: 100%; padding: 3mm 2mm 4mm; }
        .encabezado-empresa { margin-bottom: 2mm; text-align: center; font-size: 10px; }
        .razon { font-size: 13px; font-weight: 700; }
        h1 { margin: 0 0 2mm; text-align: center; font-size: 14px; letter-spacing: .03em; }
        .datos-empleado { display: flex; flex-wrap: wrap; justify-content: space-between; gap: 3mm 8mm; margin: 2mm 0; }
        .detalle-recibo { display: grid; grid-template-columns: 1fr 1.5fr; gap: 8mm; }
        .subtitulo { margin: 1mm 0; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; }
        th, td { padding: 1.2mm 1.5mm; border-bottom: 1px solid #aaa; text-align: left; }
        th:last-child, td:last-child { text-align: right; white-space: nowrap; }
        .total-haberes td { border-top: 1px solid #333; font-weight: 700; }
        .total-descuentos { display: flex; justify-content: space-between; border-top: 1px solid #777; padding: 1.5mm; }
        .sin-gastos { padding: 2mm 1.5mm; }
        .saldo { display: flex; justify-content: space-between; margin-top: 2mm; padding: 2mm 1.5mm; border-top: 2px solid #111; border-bottom: 2px solid #111; font-size: 13px; font-weight: 700; }
        .declaracion { margin: 2mm 0; }
        .firmas { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12mm; margin: 8mm 4mm 0; text-align: center; }
        .firmas div { border-top: 1px dotted #222; padding-top: 1mm; }
        .separador-copias { margin: 2mm 0; border: 0; border-top: 2px dotted #444; }
        @media print {
            .no-print { display: none !important; }
            .recibo-copia { break-inside: avoid; }
            .separador-copias { break-after: avoid; }
        }
    </style>
</head>
<body>
    <div class="no-print"><button type="button" onclick="window.print()">Imprimir recibos</button></div>
    <?= $renderRecibo(true) ?>
    <hr class="separador-copias" />
    <?= $renderRecibo(false) ?>
</body>
</html>
