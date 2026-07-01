<?php
use Perfushopping\Web\Support\Format;

$preview = $preview ?? null;
$stats = $stats ?? null;
$results = is_array($preview) ? ($preview['results'] ?? []) : [];
$total = (int)($preview['total'] ?? 0);
$found = (int)($preview['found'] ?? 0);
$notFound = (int)($preview['notFound'] ?? 0);

$updated = (int)($stats['updated'] ?? 0);
$errors = (int)($stats['errors'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Importar productos</h4>
        <p class="text-muted small">Subí un CSV para actualizar precios, costos y stock por código de proveedor o código de barra</p>
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
            Columnas: <code>codprodup</code>, <code>codscan</code>, <code>precio_sin_iva</code>, <code>costo_sin_iva</code>, <code>stock</code>, <code>ganan1</code>, <code>ganan2</code>, <code>precio1_sin_iva</code>
            <br>
            <strong>Matching:</strong> Busca primero por <code>codprodup</code> (código de proveedor), y si no encuentra, por <code>codscan</code> (código de barra).
            <br>
            <strong>Precios:</strong> <code>precio_sin_iva</code> y <code>costo_sin_iva</code> deben ser montos <strong>sin IVA</strong> (neto).
            Usá punto (<code>.</code>) como separador decimal. Precios negativos o vacíos no se actualizan.
            <br>
            <strong>Márgenes:</strong> Si incluís <code>ganan1</code> y <code>ganan2</code> (porcentajes), se actualizan los márgenes del producto.
        </div>

        <form method="post" action="/admin/productos/importar/preview" enctype="multipart/form-data">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="row g-2 align-items-end">
                <div class="col-md-8">
                    <input class="form-control form-control-sm" type="file" name="csv_file" accept=".csv,.txt" required />
                </div>
                <div class="col-md-4">
                    <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-eye"></i> Previsualizar</button>
                </div>
            </div>
        </form>

        <hr class="my-3" />
        <p class="small text-muted mb-0">
            <i class="bi bi-download"></i> Descargá una
            <a href="#" onclick="return downloadSampleCsv()">plantilla de ejemplo</a>.
        </p>
    </div>
</div>

<?php if ($preview): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-table"></i> Vista previa</span>
        <div class="d-flex gap-2 align-items-center">
            <span class="badge bg-success"><?= $found ?> encontrados</span>
            <?php if ($notFound > 0): ?>
                <span class="badge bg-warning"><?= $notFound ?> sin match</span>
            <?php endif; ?>
            <span class="badge bg-secondary"><?= $total ?> filas</span>
        </div>
    </div>
    <div class="card-body p-0">
        <form method="post" action="/admin/productos/importar/confirm" id="importConfirmForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

            <div class="table-responsive">
                <table class="table table-sm table-admin mb-0">
                    <thead>
                        <tr>
                            <?php if ($found > 0): ?>
                                <th style="width:36px">
                                    <input class="form-check-input" type="checkbox" id="selectAll" checked onchange="toggleAll(this)" />
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
                            <th>Precio1 ant.</th>
                            <th>Precio1 nuevo</th>
                            <th>Costo ant.</th>
                            <th>Costo nuevo</th>
                            <th>Diff</th>
                            <th>G1 ant.</th>
                            <th>G1 nuevo</th>
                            <th>G2 ant.</th>
                            <th>G2 nuevo</th>
                            <th>Stock ant.</th>
                            <th>Stock nuevo</th>
                            <th>Diff</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($results as $item): ?>
                            <?php
                                $matched = $item['matched'] ?? false;
                                $hasChanges = $item['has_changes'] ?? false;
                                $rowIdx = 'row_' . ($item['row'] ?? 0);
                                $precioDiff = $item['precio_diff'] ?? null;
                                $costoDiff = $item['costo_diff'] ?? null;
                                $stockDiff = $item['stock_diff'] ?? null;
                            ?>
                            <tr class="<?= !$matched ? 'table-warning' : ($hasChanges ? '' : 'table-light') ?>">
                                <?php if ($found > 0): ?>
                                    <td>
                                        <?php if ($matched && $hasChanges): ?>
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
                                <td class="text-end"><?= $matched ? (int)($item['stock_old'] ?? 0) : '-' ?></td>
                                <td class="text-end">
                                    <?php if ($matched && $item['stock_new'] !== null): ?>
                                        <strong><?= (int)$item['stock_new'] ?></strong>
                                    <?php else: ?>
                                        <span class="text-muted">=</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($stockDiff !== null && $stockDiff !== 0): ?>
                                        <span class="<?= $stockDiff > 0 ? 'text-success' : 'text-danger' ?> fw-bold">
                                            <?= ($stockDiff > 0 ? '+' : '') . $stockDiff ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
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
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos/importar">Cancelar</a>
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
function toggleAll(master) {
    document.querySelectorAll('.row-select').forEach(cb => cb.checked = master.checked);
    updateSelectedCount();
}
function updateSelectedCount() {
    const el = document.getElementById('selectedCount');
    if (!el) return;
    const n = document.querySelectorAll('.row-select:checked').length;
    el.textContent = n + ' fila' + (n !== 1 ? 's' : '') + ' seleccionada' + (n !== 1 ? 's' : '');
}
document.querySelectorAll('.row-select').forEach(cb => cb.addEventListener('change', updateSelectedCount));

function downloadSampleCsv() {
    const bom = '\uFEFF';
    const headers = 'codprodup;codscan;precio_sin_iva;costo_sin_iva;stock;ganan1;ganan2;precio1_sin_iva';
    const rows = [
        'PROV001;7791234567890;15000.00;8500.00;25;40;20;12000.00',
        'PROV002;;22000.00;12000.00;10;35;15;18000.00',
        ';7790987654321;8500.00;4200.00;50;;;',
        'PROV003;7791112223334;30000.00;18000.00;5;50;25;25000.00',
    ];
    const csv = bom + headers + '\n' + rows.join('\n');
    const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'importar_productos.csv';
    a.click();
    return false;
}

updateSelectedCount();
</script>
