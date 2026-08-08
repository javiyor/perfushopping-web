<?php
$rows = $rows ?? [];
$mon = static fn ($v) => number_format((float)$v, 2, ',', '.');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Importar comprobantes de compra</h4>
        <p class="text-muted small">Comprobantes leídos del archivo. Marcá los que querés importar y confirmá.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/compras"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Archivo importado</span>
        <span class="badge bg-secondary"><?= count($rows) ?> comprobantes</span>
    </div>
    <div class="card-body">
        <form method="post" action="/admin/compras/importar/confirmar" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="d-flex align-items-center gap-2 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="checkAll" checked />
                    <label class="form-check-label" for="checkAll">Marcar todos</label>
                </div>
                <span class="text-muted small">Duplicados ya cargados: <strong class="text-danger" id="dupCount">0</strong></span>
            </div>
            <div class="table-responsive">
                <table class="table table-admin table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width:34px"></th>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Punto venta</th>
                            <th>Número</th>
                            <th>Proveedor</th>
                            <th>CUIT</th>
                            <th>Moneda</th>
                            <th class="text-end">Neto gravado</th>
                            <th class="text-end">IVA</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($rows as $i => $r): ?>
                            <?php $dup = !empty($r['dup']); ?>
                            <tr class="<?= $dup ? 'table-warning' : '' ?>">
                                <td><input type="checkbox" name="sel[]" value="<?= (int)$i ?>" class="rowCheck" <?= $dup ? '' : 'checked' ?> <?= $dup ? 'disabled' : '' ?> /></td>
                                <td class="small"><?= htmlspecialchars((string)($r['fecha'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($r['tipo'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($r['punto_venta'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($r['numero'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($r['razon'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($r['cuit'] ?? '')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($r['moneda'] ?? 'PES')) ?></td>
                                <td class="text-end small"><?= $mon($r['neto']) ?></td>
                                <td class="text-end small"><?= $mon($r['iva']) ?></td>
                                <td class="text-end small fw-bold"><?= $mon($r['total']) ?></td>
                                <td class="small">
                                    <?php if ($dup): ?>
                                        <span class="badge bg-warning text-dark">Duplicada</span>
                                    <?php elseif (!empty($r['proveedor_match'])): ?>
                                        <span class="badge bg-success">Proveedor ok</span>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">Nuevo proveedor</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="mt-3 d-flex justify-content-end">
                <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Confirmar importación</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    if (!checkAll) return;
    checkAll.addEventListener('change', function() {
        document.querySelectorAll('.rowCheck').forEach(c => { if (!c.disabled) c.checked = checkAll.checked; });
    });
    const dupCount = document.querySelectorAll('.rowCheck:disabled').length;
    document.getElementById('dupCount').textContent = dupCount;
});
</script>
