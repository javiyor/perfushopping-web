<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>OC <?= htmlspecialchars($orden['codigo'] ?? '') ?></title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; }
        body { font-family:'Segoe UI',Arial,sans-serif; font-size:12px; color:#222; padding:30px; }
        h1 { font-size:22px; margin-bottom:4px; }
        .header { display:flex; justify-content:space-between; margin-bottom:30px; }
        .header-left { }
        .header-right { text-align:right; }
        .supplier-box { background:#f5f5f5; padding:12px; border-radius:6px; margin-bottom:20px; }
        .supplier-box h3 { font-size:14px; margin-bottom:6px; }
        .supplier-box p { font-size:11px; color:#555; margin-bottom:2px; }
        table { width:100%; border-collapse:collapse; margin-bottom:20px; }
        th, td { border:1px solid #ccc; padding:6px 8px; text-align:left; font-size:11px; }
        th { background:#eaeaea; font-weight:600; }
        .num { text-align:right; }
        .total-row td { font-weight:bold; background:#f0f0f0; }
        .footer { margin-top:30px; font-size:10px; color:#888; text-align:center; border-top:1px solid #ddd; padding-top:10px; }
        .badge { display:inline-block; padding:2px 8px; border-radius:3px; font-size:10px; background:#eee; }
        .badge-pendiente { background:#fff3cd; color:#856404; }
        @media print {
            body { padding:0; }
            .no-print { display:none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:right;margin-bottom:15px">
        <button onclick="window.print()" style="padding:6px 16px;background:#0d6efd;color:#fff;border:none;border-radius:4px;cursor:pointer">
            <i class="bi bi-printer"></i> Imprimir / PDF
        </button>
        <a href="/admin/ordenes-compra/<?= (int)$orden['id'] ?>" style="margin-left:8px;padding:6px 16px;background:#6c757d;color:#fff;border:none;border-radius:4px;text-decoration:none;font-size:13px">
            Volver
        </a>
    </div>

    <div class="header">
        <div class="header-left">
            <h1>Orden de Compra</h1>
            <p style="font-size:16px;color:#0d6efd;font-weight:bold"><?= htmlspecialchars($orden['codigo'] ?? '') ?></p>
            <p>Fecha: <?= htmlspecialchars($orden['fecha'] ?? '') ?></p>
            <p>Fecha estimada: <?= htmlspecialchars($orden['fecha_estimada'] ?? '') ?></p>
            <p>Estado: <span class="badge badge-<?= htmlspecialchars($orden['estado'] ?? '') ?>"><?= htmlspecialchars($orden['estado'] ?? '') ?></span></p>
        </div>
        <div class="header-right">
            <p style="font-size:11px;color:#888">Generado desde Grilla de Reposición</p>
        </div>
    </div>

    <div class="supplier-box">
        <h3><i class="bi bi-building"></i> Proveedor</h3>
        <p><strong><?= htmlspecialchars($orden['proveedor_nombre'] ?? '') ?></strong></p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:40px">#</th>
                <th>Código</th>
                <th>Producto</th>
                <th>Cód. proveedor</th>
                <th>Cód. barra</th>
                <th class="num">Cantidad</th>
                <th class="num">Precio unit.</th>
                <th class="num">Total</th>
            </tr>
        </thead>
        <tbody>
            <?php $i = 1; ?>
            <?php foreach ($items as $it): ?>
                <?php
                    $variedad = $it['variedad'] ?? '';
                    $codprodu = '';
                    $codproveStr = '';
                    $codscanStr = '';
                    if (preg_match('/^Cod: (\S+)(.*)$/', $variedad, $m)) {
                        $codprodu = $m[1];
                        $rest = $m[2];
                        if (preg_match('/Prov: (\S+)/', $rest, $pm)) $codproveStr = $pm[1];
                        if (preg_match('/BAR: (\S+)/', $rest, $bm)) $codscanStr = $bm[1];
                    }
                ?>
                <tr>
                    <td><?= $i++ ?></td>
                    <td><?= htmlspecialchars($codprodu) ?></td>
                    <td><?= htmlspecialchars((string)($it['producto'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($codproveStr) ?></td>
                    <td><?= htmlspecialchars($codscanStr) ?></td>
                    <td class="num"><?= (int)($it['qty'] ?? 0) ?></td>
                    <td class="num">$<?= number_format((int)($it['unit_price_cents'] ?? 0) / 100, 2, ',', '.') ?></td>
                    <td class="num">$<?= number_format((int)($it['total_cents'] ?? 0) / 100, 2, ',', '.') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr class="total-row">
                <td colspan="6"></td>
                <td class="num">Total</td>
                <td class="num">$<?= number_format((int)($orden['total_cents'] ?? 0) / 100, 2, ',', '.') ?></td>
            </tr>
        </tfoot>
    </table>

    <?php if (($orden['notas'] ?? '') !== ''): ?>
    <div style="margin-top:15px;padding:10px;background:#f9f9f9;border-radius:4px">
        <strong>Notas:</strong><br /><?= nl2br(htmlspecialchars($orden['notas'])) ?>
    </div>
    <?php endif; ?>

    <div class="footer">
        <p>Documento generado automáticamente — <?= date('d/m/Y H:i') ?></p>
    </div>
</body>
</html>
