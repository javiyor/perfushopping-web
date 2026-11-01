<?php
use Perfushopping\Web\Support\Format;

$preview = $preview ?? null;
$stats = $stats ?? null;
$proveedores = $proveedores ?? [];
$previewProveedor = is_array($preview) ? (string)($preview['proveedor'] ?? '') : '';
$results = is_array($preview) ? ($preview['results'] ?? []) : [];
$total = (int)($preview['total'] ?? 0);
$found = (int)($preview['found'] ?? 0);
$notFound = (int)($preview['notFound'] ?? 0);
$modoPreview = (string)($preview['modo_precio'] ?? (!empty($preview['precios_con_iva']) ? 'con_iva' : 'neto'));
$revisionPrecioCount = count(array_filter($results, static fn(array $item): bool => !empty($item['requiere_revision_precio'])));

$updated = (int)($stats['updated'] ?? 0);
$errors = (int)($stats['errors'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Importar productos</h4>
        <p class="text-muted small">Subí un CSV para actualizar precios y costos por código de proveedor o código de barra. El stock no se modifica en ningún modo.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos">Volver a productos</a>
</div>

<?php if ($stats): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle"></i>
        Importación completada: <strong><?= $updated ?> productos</strong> actualizados<?= $errors ? ", {$errors} errores" : '' ?>.
    </div>
<?php endif; ?>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">
        <i class="bi bi-upload"></i> Subir archivo CSV
    </div>
    <div class="card-body">
        <div class="alert alert-info small">
            <strong>Formato del CSV:</strong> La primera fila debe contener los encabezados.
            Columnas: <code>codprodup</code>, <code>codscan</code>, <code>precio_sin_iva</code>, <code>costo_sin_iva</code>, <code>ganan1</code>, <code>ganan2</code>, <code>precio1_sin_iva</code>
            <br>
            <strong>Matching:</strong> Busca primero por <code>codprodup</code> (código de proveedor), y si no encuentra, por <code>codscan</code> (código de barra).
            <br>
            <strong>Precios:</strong> <code>precio_sin_iva</code> y <code>costo_sin_iva</code> deben ser montos <strong>sin IVA</strong> (neto).
            Usá punto (<code>.</code>) como separador decimal. Precios negativos o vacíos no se actualizan.
            <br>
            <strong>Márgenes:</strong> Si incluís <code>ganan1</code> y <code>ganan2</code> (porcentajes), se actualizan los márgenes del producto.
            <hr class="my-2">
            <strong>Modo "Precios con IVA incluido":</strong>
            Columnas: <code>codscan</code>, <code>precio_con_iva</code>, <code>costo_con_iva</code> (opcional). El stock no se modifica.
            <br>
            <strong>Matching:</strong> Busca por <code>codscan</code> (código de barra de la variante).
            <br>
            <strong>Cálculo automático:</strong> <code>precio_con_iva</code> → calcula <code>precio</code> (sin IVA), <code>precomp</code> (costo) usando margen <code>ganan1</code> existente, y <code>precio1</code> (mayorista) usando margen <code>ganan2</code> existente.
            <br>
            <strong>Precios:</strong> <code>precio_con_iva</code> debe ser monto <strong>con IVA</strong> (retail). Usá punto (<code>.</code>) como separador decimal.
            <hr class="my-2">
            <strong>Modo "Costo sin IVA":</strong>
            Columnas: <code>codscan</code> o <code>codprodup</code>, y <code>costo_sin_iva</code>. El stock no se modifica.
            <br>
            <strong>Matching:</strong> Busca primero por <code>codscan</code>; si no encuentra el artículo, usa <code>codprodup</code> cuando seleccionás el proveedor correspondiente.
            <br>
            <strong>Cálculo automático:</strong> <code>costo_sin_iva</code> → calcula <code>precio</code> (minorista) con margen <code>ganan1</code> existente y <code>precio1</code> (mayorista) con margen <code>ganan2</code> existente. Los márgenes no se modifican.

        <form method="post" action="/admin/productos/importar/preview" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="row g-2 align-items-end">
                <div class="col-md-5">
                    <label class="form-label small">Proveedor (opcional: filtra solo sus productos)</label>
                    <select class="form-select form-select-sm" name="idprovee">
                        <option value="0">Todos los proveedores</option>
                        <?php foreach ($proveedores as $pr): ?>
                            <option value="<?= (int)$pr['idprovee'] ?>"><?= htmlspecialchars((string)($pr['razon'] ?? '')) ?> (<?= (int)($pr['product_count'] ?? 0) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <input class="form-control form-control-sm" type="file" name="csv_file" accept=".csv,.txt" required />
                </div>
                <div class="col-md-3">
                    <label class="form-label small">Modo de precios</label>
                    <select class="form-select form-select-sm" name="modo_precio">
                        <option value="neto">Precios netos (sin IVA)</option>
                        <option value="con_iva">Precios con IVA incluido</option>
                        <option value="costo">Costo sin IVA (calcula precios)</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-eye"></i> Previsualizar</button>
                </div>
            </div>
        </form>

        <hr class="my-3" />
        <p class="small text-muted mb-0">
            <i class="bi bi-download"></i> Descargá una
            <a href="#" onclick="return downloadSampleCsv()">plantilla de ejemplo</a>,
            <a href="#" onclick="return downloadSampleCsvIva()">plantilla con IVA</a> o
            <a href="#" onclick="return downloadSampleCsvCosto()">plantilla de costo</a>.
        </p>
    </div>
</div>

<?php if ($preview): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table"></i> Vista previa<?php if ($previewProveedor !== ''): ?> · Proveedor: <strong><?= htmlspecialchars($previewProveedor) ?></strong><?php endif; ?></span>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-success"><?= $found ?> encontrados</span>
            <?php if ($revisionPrecioCount > 0): ?>
                <button class="btn btn-outline-warning btn-sm" type="button" id="btnFiltroCambiosPrecio" onclick="return toggleCambiosPrecio()">
                    Revisar variaciones &gt;15% (<?= $revisionPrecioCount ?>)
                </button>
            <?php endif; ?>
            <?php if ($modoPreview === 'con_iva'): ?>
                <span class="badge bg-info text-dark">Precios con IVA</span>
            <?php elseif ($modoPreview === 'costo'): ?>
                <span class="badge bg-info text-dark">Costo sin IVA</span>
            <?php endif; ?>
            <?php if ($notFound > 0): ?>
                <span class="badge bg-warning"><?= $notFound ?> sin match</span>
                <button type="button" class="btn btn-outline-secondary btn-sm py-0" id="toggleSinMatch" onclick="return toggleSinMatch()">Mostrar sin match</button>
            <?php endif; ?>
            <span class="badge bg-secondary"><?= $total ?> filas</span>
        </div>
    </div>
    <?php if ($revisionPrecioCount > 0): ?>
        <div class="alert alert-warning small mb-0 rounded-0 border-0 border-top">
            <strong><?= $revisionPrecioCount ?> producto(s)</strong> tienen una variación superior al 15% en precio minorista, mayorista o costo. Quedan desmarcados y requieren selección individual después de revisarlos.
        </div>
    <?php endif; ?>
    <div class="card-body p-0">
        <form method="post" action="/admin/productos/importar/confirm" id="importConfirmForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

            <div class="table-responsive">
                <table class="table table-sm table-admin mb-0" id="importPreviewTable">
                    <thead>
                        <tr>
                            <?php if ($found > 0): ?>
                                <th style="width:36px">
                                    <input class="form-check-input" type="checkbox" id="selectAll" onchange="toggleAll(this)" />
                                </th>
                            <?php endif; ?>
                            <th>#</th>
                            <th>Cod.Prov</th>
                            <th>Cód.Barra</th>
                            <th>Producto</th>
                            <th>Variedad</th>
                            <th>Precio ant.</th>
                            <th>Precio nuevo</th>
                            <th>Diff</th>
                            <th>Variación de precio</th>
                            <th>Precio1 ant.</th>
                            <th>Precio1 nuevo</th>
                            <th>Costo ant.</th>
                            <th>Costo nuevo</th>
                            <th>Diff</th>
                            <th>G1 ant.</th>
                            <th>G1 nuevo</th>
                            <th>G2 ant.</th>
                            <th>G2 nuevo</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $item): ?>
                            <?php
                                $matched = $item['matched'] ?? false;
                                $hasChanges = $item['has_changes'] ?? false;
                                $requiereRevisionPrecio = !empty($item['requiere_revision_precio']);
                                $rowIdx = 'row_' . ($item['row'] ?? 0);
                                $precioDiff = $item['precio_diff'] ?? null;
                                $costoDiff = $item['costo_diff'] ?? null;
                                $rowClass = !$matched ? 'table-warning row-sin-match' : ($requiereRevisionPrecio ? 'table-warning row-revision-precio' : ($hasChanges ? '' : 'table-light'));
                            ?>
                            <tr class="<?= $rowClass ?>" data-revision-precio="<?= $requiereRevisionPrecio ? '1' : '0' ?>"<?= !$matched ? ' style="display:none"' : '' ?>>
                                <?php if ($found > 0): ?>
                                    <td>
                                        <?php if ($matched && $hasChanges && !$requiereRevisionPrecio): ?>
                                            <input class="form-check-input row-select" type="checkbox" name="selected[]" value="<?= htmlspecialchars($rowIdx) ?>" checked />
                                        <?php elseif ($matched): ?>
                                            <input class="form-check-input row-select" type="checkbox" name="selected[]" value="<?= htmlspecialchars($rowIdx) ?>" />
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                                <td class="small text-muted"><?= (int)($item['row'] ?? 0) ?></td>
                                <td><code><?= htmlspecialchars((string)($item['codprodup'] ?? '')) ?></code></td>
                                <td><code><?= htmlspecialchars((string)($item['codscan'] ?? '')) ?></code></td>
                                <td>
                                    <?php if ($matched): ?>
                                        <strong><?= htmlspecialchars(mb_substr((string)($item['producto'] ?? ''), 0, 50)) ?></strong>
                                        <div class="small text-muted">#<?= (int)($item['idprodu'] ?? 0) ?> · <?= htmlspecialchars((string)($item['marca'] ?? '')) ?></div>
                                    <?php else: ?>
                                        <span class="text-muted">— No encontrado —</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= $matched ? htmlspecialchars((string)($item['variedad'] ?? '')) : '<span class="text-muted">-</span>' ?></td>
                                <td class="text-end"><?= $matched ? htmlspecialchars(Format::moneyFromCents((int)round(($item['precio_old'] ?? 0) * 100))) : '-' ?></td>
                                <td class="text-end">
                                    <?php if ($matched && $item['precio_new'] !== null): ?>
                                        <strong><?= htmlspecialchars(Format::moneyFromCents((int)round(($item['precio_new'] ?? 0) * 100))) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">=</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($precioDiff !== null && abs($precioDiff) > 0.001): ?>
                                        <span class="<?= $precioDiff > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                            <?= ($precioDiff > 0 ? '+' : '') . number_format($precioDiff, 2, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($requiereRevisionPrecio): ?>
                                        <span class="badge bg-warning text-dark">Revisión</span>
                                        <div class="small"><?= htmlspecialchars((string)($item['revision_precio_detalle'] ?? '')) ?></div>
                                    <?php elseif (($item['precio_variacion_pct'] ?? null) !== null): ?>
                                        <span class="small text-muted"><?= ($item['precio_variacion_pct'] > 0 ? '+' : '') . number_format((float)$item['precio_variacion_pct'], 1, ',', '.') ?>%</span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= $matched ? htmlspecialchars(Format::moneyFromCents((int)round(($item['precio1_old'] ?? 0) * 100))) : '-' ?></td>
                                <td class="text-end">
                                    <?php if ($matched && $item['precio1_new'] !== null): ?>
                                        <strong><?= htmlspecialchars(Format::moneyFromCents((int)round(($item['precio1_new'] ?? 0) * 100))) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">=</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= $matched ? htmlspecialchars(Format::moneyFromCents((int)round(($item['costo_old'] ?? 0) * 100))) : '-' ?></td>
                                <td class="text-end">
                                    <?php if ($matched && $item['costo_new'] !== null): ?>
                                        <strong><?= htmlspecialchars(Format::moneyFromCents((int)round(($item['costo_new'] ?? 0) * 100))) ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">=</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($costoDiff !== null && abs($costoDiff) > 0.001): ?>
                                        <span class="<?= $costoDiff > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                            <?= ($costoDiff > 0 ? '+' : '') . number_format($costoDiff, 2, ',', '.') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= $matched ? htmlspecialchars((string)($item['ganan1_old'] ?? '0')) : '-' ?></td>
                                <td class="text-end">
                                    <?php if ($matched && $item['ganan1_new'] !== null): ?>
                                        <strong><?= htmlspecialchars((string)$item['ganan1_new']) ?>%</strong>
                                    <?php else: ?>
                                        <span class="text-muted">=</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end"><?= $matched ? htmlspecialchars((string)($item['ganan2_old'] ?? '0')) : '-' ?></td>
                                <td class="text-end">
                                    <?php if ($matched && $item['ganan2_new'] !== null): ?>
                                        <strong><?= htmlspecialchars((string)$item['ganan2_new']) ?>%</strong>
                                    <?php else: ?>
                                        <span class="text-muted">=</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($matched): ?>
                                        <span class="badge bg-success">OK</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Sin match</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div class="p-3 border-top d-flex justify-content-between align-items-center">
                <div>
                    <?php if ($found > 0): ?>
                        <span class="small text-muted" id="selectedCount"><?= $found ?> filas seleccionadas</span>
                    <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos/importar?cancelar=1">Cancelar</a>
                    <?php if ($found > 0): ?>
                        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Confirmar importación</button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
let soloCambiosPrecioGrandes = false;
let mostrarFilasSinMatch = false;

function toggleAll(master) {
    document.querySelectorAll('#importPreviewTable .row-select').forEach(cb => {
        const row = cb.closest('tr');
        if (row && row.style.display !== 'none' && row.dataset.revisionPrecio !== '1') cb.checked = master.checked;
    });
    updateSelectedCount();
}
function toggleSinMatch() {
    mostrarFilasSinMatch = !mostrarFilasSinMatch;
    const btn = document.getElementById('toggleSinMatch');
    if (btn) btn.textContent = mostrarFilasSinMatch ? 'Ocultar sin match' : 'Mostrar sin match';
    aplicarFiltrosPreview();
    return false;
}
function toggleCambiosPrecio() {
    soloCambiosPrecioGrandes = !soloCambiosPrecioGrandes;
    const btn = document.getElementById('btnFiltroCambiosPrecio');
    if (btn) btn.textContent = soloCambiosPrecioGrandes ? 'Mostrar todas las filas' : 'Revisar variaciones >15% (<?= $revisionPrecioCount ?>)';
    aplicarFiltrosPreview();
    return false;
}
function aplicarFiltrosPreview() {
    document.querySelectorAll('#importPreviewTable tbody tr').forEach(tr => {
        const esSinMatch = tr.classList.contains('row-sin-match');
        const esVariacionGrande = tr.dataset.revisionPrecio === '1';
        tr.style.display = (mostrarFilasSinMatch || !esSinMatch) && (!soloCambiosPrecioGrandes || esVariacionGrande) ? '' : 'none';
    });
    updateSelectedCount();
}
function updateSelectedCount() {
    const el = document.getElementById('selectedCount');
    const master = document.getElementById('selectAll');
    const checks = Array.from(document.querySelectorAll('#importPreviewTable .row-select'));
    const checked = checks.filter(cb => cb.checked).length;
    if (el) el.textContent = checked + ' fila' + (checked !== 1 ? 's' : '') + ' seleccionada' + (checked !== 1 ? 's' : '');
    if (master) {
        const visible = checks.filter(cb => cb.closest('tr')?.style.display !== 'none');
        const visibleChecked = visible.filter(cb => cb.checked).length;
        const allRequireReview = visible.length > 0 && visible.every(cb => cb.closest('tr')?.dataset.revisionPrecio === '1');
        master.disabled = allRequireReview;
        master.checked = !master.disabled && visible.length > 0 && visibleChecked === visible.length;
        master.indeterminate = !master.disabled && visibleChecked > 0 && visibleChecked < visible.length;
    }
}
document.querySelectorAll('.row-select').forEach(cb => cb.addEventListener('change', updateSelectedCount));
aplicarFiltrosPreview();

function downloadSampleCsv() {
    const bom = '\uFEFF';
    const headers = 'codprodup;codscan;precio_sin_iva;costo_sin_iva;ganan1;ganan2;precio1_sin_iva';
    const rows = [
        'PROV001;7791234567890;15000.00;8500.00;40;20;12000.00',
        'PROV002;;22000.00;12000.00;35;15;18000.00',
        ';7790987654321;8500.00;4200.00;;;',
        'PROV003;7791112223334;30000.00;18000.00;50;25;25000.00',
    ];
    const csv = bom + headers + '\n' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'importar_productos.csv';
    a.click();
    return false;
}

function downloadSampleCsvIva() {
    const bom = '\uFEFF';
    const headers = 'codscan;precio_con_iva;costo_con_iva';
    const rows = [
        '7791234567890;18150.00;',
        '7790987654321;10285.00;',
        '7791112223334;36300.00;21780.00',
    ];
    const csv = bom + headers + '\n' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'importar_productos_con_iva.csv';
    a.click();
    return false;
}

function downloadSampleCsvCosto() {
    const bom = '\uFEFF';
    const headers = 'codscan;codprodup;costo_sin_iva';
    const rows = [
        '7791234567890;PROV001;10714.29',
        ';PROV002;5000.00',
        '7791112223334;PROV003;18000.00',
    ];
    const csv = bom + headers + '\n' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'importar_productos_costo.csv';
    a.click();
    return false;
}

updateSelectedCount();
</script>
