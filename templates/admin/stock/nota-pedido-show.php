<?php
$nota = $nota ?? null;
$items = $items ?? [];
if (!$nota) { echo '<div class="alert alert-warning">Nota no encontrada.</div>'; return; }
?>
<style>
@media print { .no-print { display:none !important; } body { background:#fff; } }
.np-print-header { text-align:center; margin-bottom:20px; }
.np-print-header h3 { margin:0; font-weight:800; }
.np-print-header .codigo { font-size:18px; color:#6c757d; }
.np-box { border:1px solid #dee2e6; border-radius:8px; padding:14px; margin-bottom:14px; background:#fff; }
.np-box h6 { font-weight:700; font-size:13px; margin:0 0 8px; color:#6c757d; text-transform:uppercase; }
</style>

<div class="no-print d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($nota['codigo'] ?? '') ?></h4>
        <p class="text-muted small">Nota de pedido</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-accent btn-sm" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/stock">Volver a stock</a>
    </div>
</div>

<div class="np-print-header">
    <h3>Nota de Pedido</h3>
    <div class="codigo"><?= htmlspecialchars($nota['codigo'] ?? '') ?></div>
    <div class="text-muted small">Fecha: <?= htmlspecialchars($nota['created_at'] ?? '') ?></div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="np-box">
            <h6>Proveedor</h6>
            <p class="mb-1 fw-semibold"><?= htmlspecialchars($nota['proveedor_nombre'] ?? '—') ?></p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="np-box">
            <h6>Transporte</h6>
            <p class="mb-1"><?= htmlspecialchars($nota['transporte'] ?? '—') ?></p>
            <?php if ($nota['notas'] ?? ''): ?>
                <p class="small text-muted mb-0"><?= nl2br(htmlspecialchars($nota['notas'])) ?></p>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-6">
        <div class="np-box">
            <h6>Datos del envío</h6>
            <p class="mb-0">Dirección: <?= htmlspecialchars($nota['envio_direccion'] ?? '—') ?></p>
            <p class="mb-0">Ciudad: <?= htmlspecialchars($nota['envio_ciudad'] ?? '—') ?></p>
            <p class="mb-0">Teléfono: <?= htmlspecialchars($nota['envio_telefono'] ?? '—') ?></p>
        </div>
    </div>
</div>

<div class="np-box mt-3">
    <h6>Productos</h6>
    <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0" style="font-size:13px">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Producto</th>
                    <th>Variedad</th>
                    <th>Cód. barra</th>
                    <th>Cód. proveedor</th>
                    <th class="text-center">Cant.</th>
                </tr>
            </thead>
            <tbody>
                <?php $idx = 0; foreach ($items as $it): $idx++; ?>
                <tr>
                    <td><?= $idx ?></td>
                    <td><?= htmlspecialchars($it['producto'] ?: ($it['produ'] ?? '')) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($it['variedad'] ?: ($it['nomgusto'] ?? '—')) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($it['codscan'] ?: ($it['g_codscan'] ?? '')) ?></td>
                    <td class="text-muted"><?= htmlspecialchars($it['codprodup'] ?: ($it['p_codprodup'] ?? '')) ?></td>
                    <td class="text-center fw-bold"><?= (int)($it['qty'] ?? 0) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$items): ?>
                <tr><td colspan="6" class="text-center text-muted">Sin productos</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="text-muted small mt-3 no-print">
    Generado por: <?= htmlspecialchars($nota['created_by_nombre'] ?? '—') ?>
</div>
