<?php
$rows = $rows ?? [];
$mon = static fn ($v) => number_format((float)$v, 2, ',', '.');
$hasRows = count($rows) > 0;
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Importar comprobantes de compra</h4>
        <p class="text-muted small"><?= $hasRows ? 'Comprobantes leídos del archivo. Marcá los que querés importar y confirmá.' : 'Subí el Excel (.xlsx) descargado de ARCA (Mis Comprobantes → Compras) o un CSV.' ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/compras"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if (!$hasRows): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Subir archivo</div>
    <div class="card-body">
        <form method="post" action="/admin/compras/importar" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="mb-2">
                <label class="form-label small">Archivo (Excel .xlsx de ARCA o .csv)</label>
                <input class="form-control form-control-sm" type="file" name="archivo" accept=".xlsx,.csv,.txt" required />
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-accent" type="submit"><i class="bi bi-upload"></i> Leer archivo</button>
                <a class="btn btn-outline-secondary" href="/admin/compras/nueva"><i class="bi bi-plus-lg"></i> Cargar manual</a>
            </div>
            <div class="form-text mt-2">
                Se reconocen las columnas de ARCA (Fecha, Tipo, Punto de Venta, Número Desde/Hasta, Cód. Autorización, Nro. Doc. Emisor, Denominación Emisor, Tipo Cambio, Moneda, Imp. Neto Gravado/No Gravado, Exentas, Otros Tributos, IVA, Imp. Total).
            </div>
        </form>
    </div>
</div>
<?php else: ?>

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
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const checkAll = document.getElementById('checkAll');
    if (!checkAll) return;
    checkAll.addEventListener('change', function() {
        document.querySelectorAll('.rowCheck').forEach(c => { if (!c.disabled) c.checked = checkAll.checked; });
    });
    const dupCountEl = document.getElementById('dupCount');
    if (dupCountEl) dupCountEl.textContent = document.querySelectorAll('.rowCheck:disabled').length;
});
</script>
