<?php
use Perfushopping\Web\Support\Format;

$list = is_array($list ?? null) ? $list : [];
$mes = (string)($mes ?? date('Y-m'));
$totalFletes = (int)($totalFletes ?? 0);
$totalNeto = (float)($totalNeto ?? 0);
$totalBultos = (int)($totalBultos ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Fletes pagados</h4>
        <p class="text-muted small mb-0">Resumen por fecha de recepción y proveedor</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/compras"><i class="bi bi-arrow-left"></i> Volver a compras</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/ordenes-compra/fletes" class="row g-2 align-items-end">
            <div class="col-sm-4 col-md-3">
                <label class="form-label small">Mes de recepción</label>
                <input class="form-control form-control-sm" type="month" name="mes" value="<?= htmlspecialchars($mes) ?>" required />
            </div>
            <div class="col-auto">
                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-search"></i> Consultar</button>
            </div>
            <div class="col-auto">
                <a class="btn btn-outline-secondary btn-sm" href="/admin/ordenes-compra/fletes">Mes actual</a>
            </div>
            <div class="col-12 small text-muted">Se listan grupos con monto de flete mayor a cero. El neto gravado y los bultos suman las compras del proveedor en esa fecha de recepción.</div>
        </form>
    </div>
</div>

<div class="row g-2 mb-3">
    <div class="col-md-4"><div class="card-dashboard text-center"><div class="h5 fw-bold mb-0"><?= Format::moneyFromCents((int)round($totalNeto * 100)) ?></div><div class="small text-muted">Neto gravado recibido</div></div></div>
    <div class="col-md-4"><div class="card-dashboard text-center"><div class="h5 fw-bold mb-0"><?= number_format($totalBultos, 0, ',', '.') ?></div><div class="small text-muted">Bultos recibidos</div></div></div>
    <div class="col-md-4"><div class="card-dashboard text-center"><div class="h5 fw-bold mb-0"><?= Format::moneyFromCents($totalFletes) ?></div><div class="small text-muted">Flete pagado</div></div></div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Recepciones de <?= htmlspecialchars($mes) ?></span>
        <span class="badge bg-secondary"><?= count($list) ?> proveedor/fecha</span>
    </div>
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Fecha recepción</th>
                    <th>Proveedor</th>
                    <th class="text-end">Neto gravado</th>
                    <th class="text-end">Bultos recibidos</th>
                    <th class="text-end">Flete pagado</th>
                    <th>Observación de recepción</th>
                    <th>Comprobantes con flete</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="7" class="text-muted text-center py-4">No hay fletes pagados registrados para este mes.</td></tr>
                <?php else: foreach ($list as $row): ?>
                    <tr>
                        <td class="text-nowrap"><?= htmlspecialchars((string)($row['fecha_recepcion'] ?? '')) ?></td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars((string)($row['proveedor'] ?? 'Proveedor sin identificar')) ?></div>
                            <?php if (!empty($row['cuit_proveedor'])): ?><div class="small text-muted">CUIT <?= htmlspecialchars((string)$row['cuit_proveedor']) ?></div><?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap"><?= Format::moneyFromCents((int)round((float)($row['neto_gravado'] ?? 0) * 100)) ?></td>
                        <td class="text-end"><?= number_format((int)($row['bultos_recibidos'] ?? 0), 0, ',', '.') ?></td>
                        <td class="text-end fw-semibold text-nowrap"><?= Format::moneyFromCents((int)($row['flete_pagado_cents'] ?? 0)) ?></td>
                        <td class="small"><?= htmlspecialchars((string)($row['bultos_observacion'] ?? '')) ?: '<span class="text-muted">—</span>' ?></td>
                        <td class="small">
                            <?php $ids = array_values(array_filter(array_map('intval', explode(',', (string)($row['compra_ids'] ?? ''))))); ?>
                            <?php foreach ($ids as $idx => $id): ?>
                                <?= $idx ? ', ' : '' ?><a href="/admin/compras/<?= $id ?>">#<?= $id ?></a>
                            <?php endforeach; ?>
                            <span class="text-muted">(<?= (int)($row['fletes_pagados'] ?? 0) ?>)</span>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            </tbody>
            <?php if ($list): ?>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2" class="text-end">Total</td>
                        <td class="text-end text-nowrap"><?= Format::moneyFromCents((int)round($totalNeto * 100)) ?></td>
                        <td class="text-end"><?= number_format($totalBultos, 0, ',', '.') ?></td>
                        <td class="text-end text-nowrap"><?= Format::moneyFromCents($totalFletes) ?></td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
