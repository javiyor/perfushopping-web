<?php
$list = $list ?? [];
$q = (string)($q ?? '');
$codepar = (int)($codepar ?? 0);
$stockFilter = (string)($stockFilter ?? '');
$codrub = (int)($codrub ?? 0);
$codsub = (int)($codsub ?? 0);
$codprove = (int)($codprove ?? 0);
$desde = (string)($desde ?? '');
$hasta = (string)($hasta ?? '');
$rubros = $rubros ?? [];
$subrubros = $subrubros ?? [];
$proveedores = $proveedores ?? [];
$stockFilters = ['' => 'Todos', 'sin_stock' => 'Sin stock', 'bajo_stock' => 'Stock bajo (≤5)', 'con_stock' => 'Con stock'];
$isSuper = ($adminUser['rol'] ?? '') === 'superadmin';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Stock</h4>
        <p class="text-muted small">Control de inventario</p>
    </div>
    <div class="d-flex gap-2">
        <form method="post" action="/admin/stock/recalcular" onsubmit="return confirm('Recalcular todo el stock desde los movimientos?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-arrow-repeat"></i> Recalcular</button>
        </form>
        <button class="btn btn-accent btn-sm" id="btnNotaPedido" onclick="irANotaPedido()"><i class="bi bi-file-text"></i> Nota de pedido</button>
        <a class="btn btn-accent btn-sm" href="/admin/stock/ajuste"><i class="bi bi-pencil-square"></i> Ajuste manual</a>
        <a class="btn btn-outline-success btn-sm" href="/admin/stock/exportar-excel?<?= htmlspecialchars($_SERVER['QUERY_STRING'] ?? '') ?>"><i class="bi bi-file-earmark-excel"></i> Excel</a>
        <?php if ($isSuper): ?>
            <button class="btn btn-outline-danger btn-sm" onclick="eliminarDiscontinuadas()"><i class="bi bi-trash"></i> Eliminar disc.</button>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/stock" class="row g-2">
            <div class="col-lg-3">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar producto..." />
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="codprove">
                    <option value="0">Todos los proveedores</option>
                    <?php foreach ($proveedores as $p): ?>
                        <option value="<?= (int)$p['codprove'] ?>" <?= $codprove === (int)$p['codprove'] ? 'selected' : '' ?>><?= htmlspecialchars($p['nomprovee'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="codsub">
                    <option value="0">Todas las marcas</option>
                    <?php foreach ($subrubros as $s): ?>
                        <option value="<?= (int)$s['codsub'] ?>" <?= $codsub === (int)$s['codsub'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nomsub'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="codrub">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($rubros as $r): ?>
                        <option value="<?= (int)$r['codrub'] ?>" <?= $codrub === (int)$r['codrub'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nomrub'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="stock">
                    <?php foreach ($stockFilters as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $stockFilter === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <input type="date" class="form-control form-control-sm" name="desde" value="<?= htmlspecialchars($desde) ?>" />
            </div>
            <div class="col-lg-2">
                <input type="date" class="form-control form-control-sm" name="hasta" value="<?= htmlspecialchars($hasta) ?>" />
            </div>
            <div class="col-lg-1">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-lg-1">
                <?php if ($q !== '' || $codprove > 0 || $codsub > 0 || $codrub > 0 || $stockFilter !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/stock?desde=<?= urlencode($desde) ?>&hasta=<?= urlencode($hasta) ?>">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0" style="font-size:13px">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Producto</th>
                    <th>Variedad</th>
                    <th>Sucursal</th>
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th>Marca</th>
                    <th>Categoría</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Costo</th>
                    <th class="text-center">Stock</th>
                    <th class="text-center">Ventas</th>
                    <th class="text-center" style="width:60px">Pedir</th>
                    <?php if ($isSuper): ?>
                        <th class="text-center" style="width:50px">Disc.</th>
                    <?php endif; ?>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="<?= $isSuper ? 15 : 14 ?>" class="text-muted text-center">Sin productos.</td></tr>
                <?php else: ?>
                    <?php
                    $prevId = -1;
                    $totalStockValor = 0.0;
                    $totalVendidoValor = 0.0;
                    $prevVentaId = -1;
                    ?>
                    <?php foreach ($list as $p): ?>
                        <?php $gid = (int)($p['idcodgusto'] ?? 0); $isFirst = ($gid !== $prevId); $prevId = $gid; ?>
                        <?php $stock = (int)($p['stock_deposito'] ?? 0); ?>
                        <?php
                        $totalStockValor += $stock * (float)($p['precomp'] ?? 0);
                        if ($gid !== $prevVentaId) { $totalVendidoValor += (float)($p['total_vendido'] ?? 0) * (float)($p['precomp'] ?? 0); $prevVentaId = $gid; }
                        ?>
                        <tr>
                            <td>
                                <?php if ($p['imagen'] ?? ''): ?>
                                    <img src="<?= htmlspecialchars(\Perfushopping\Web\Support\Format::uploadUrl((string)$p['imagen'])) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:4px" alt="" />
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars((string)($p['produ'] ?? '-')) ?></strong></td>
                            <td class="small"><?= htmlspecialchars((string)($p['nomgusto'] ?? '-')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($p['nomdepo'] ?? '-')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($p['codscan'] ?: $p['codprodu'] ?? '')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['nomprovee'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['nomsub'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['nomrub'] ?? '-')) ?></td>
                            <td class="text-end small">$<?= number_format((float)($p['precio'] ?? 0), 2, ',', '.') ?></td>
                            <td class="text-end small text-muted">$<?= number_format((float)($p['precomp'] ?? 0), 2, ',', '.') ?></td>
                            <td class="text-center">
                                <?php if ($stock <= 0): ?>
                                    <span class="badge bg-danger">0</span>
                                <?php elseif ($stock <= 5): ?>
                                    <span class="badge bg-warning text-dark"><?= $stock ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $stock ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= $isFirst ? (int)($p['total_vendido'] ?? 0) : '' ?></td>
                            <td class="text-center">
                                <?php if ($isFirst): ?>
                                    <input type="number" class="form-control form-control-sm np-qty" style="width:55px;text-align:center" min="0" value="0"
                                        data-idprodu="<?= (int)($p['idprodu'] ?? 0) ?>"
                                        data-idcodgusto="<?= $gid ?>"
                                        data-producto="<?= htmlspecialchars((string)($p['produ'] ?? ''), ENT_QUOTES) ?>"
                                        data-variedad="<?= htmlspecialchars((string)($p['nomgusto'] ?? ''), ENT_QUOTES) ?>"
                                        data-codscan="<?= htmlspecialchars((string)($p['codscan'] ?? ''), ENT_QUOTES) ?>"
                                        data-codprodup="<?= htmlspecialchars((string)($p['codprodup'] ?? ''), ENT_QUOTES) ?>" />
                                <?php endif; ?>
                            </td>
                            <?php if ($isSuper): ?>
                                <td class="text-center">
                                    <?php if ($isFirst): ?>
                                        <input type="checkbox" class="form-check-input np-disc" style="cursor:pointer"
                                            data-idcodgusto="<?= $gid ?>"
                                            <?= (int)($p['discont'] ?? 0) === 1 ? 'checked' : '' ?> />
                                    <?php endif; ?>
                                </td>
                            <?php endif; ?>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/stock/<?= (int)($p['idprodu'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if ($list): ?>
                <tfoot class="table-light">
                    <tr>
                        <td colspan="10" class="text-end fw-bold">Total</td>
                        <td class="text-center fw-bold">$<?= number_format($totalStockValor, 2, ',', '.') ?></td>
                        <td class="text-center fw-bold">$<?= number_format($totalVendidoValor, 2, ',', '.') ?></td>
                        <td></td>
                        <?php if ($isSuper): ?>
                            <td></td>
                        <?php endif; ?>
                        <td></td>
                    </tr>
                </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>

<script>
function irANotaPedido() {
    const items = [];
    document.querySelectorAll('.np-qty').forEach(function(inp) {
        const qty = parseInt(inp.value) || 0;
        if (qty <= 0) return;
        items.push({
            idprodu: parseInt(inp.dataset.idprodu) || 0,
            idcodgusto: parseInt(inp.dataset.idcodgusto) || 0,
            producto: inp.dataset.producto || '',
            variedad: inp.dataset.variedad || '',
            codscan: inp.dataset.codscan || '',
            codprodup: inp.dataset.codprodup || '',
            qty: qty,
        });
    });
    if (items.length === 0) {
        alert('Primero ingresá cantidades en la columna "Pedir".');
        return;
    }
    try {
        sessionStorage.setItem('np_items', JSON.stringify(items));
    } catch(e) {}
    window.location.href = '/admin/nota-pedido/nueva';
}

const csrfToken = <?= json_encode($csrf ?? '') ?>;

document.querySelectorAll('.np-disc').forEach(function(cb) {
    cb.addEventListener('change', function() {
        const fd = new FormData();
        fd.append('_csrf', csrfToken);
        fd.append('idcodgusto', this.dataset.idcodgusto);
        if (this.checked) fd.append('discont', '1');
        fetch('/admin/stock/discont', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(res => {
                if (!res.ok) {
                    alert('Error al actualizar discontinuada: ' + (res.error || 'desconocido'));
                    this.checked = !this.checked;
                }
            })
            .catch(() => {
                alert('Error de conexión');
                this.checked = !this.checked;
            });
    });
});

function eliminarDiscontinuadas() {
    const ids = [];
    document.querySelectorAll('.np-disc:checked').forEach(function(cb) {
        ids.push(parseInt(cb.dataset.idcodgusto) || 0);
    });
    if (ids.length === 0) {
        alert('No hay variedades discontinuadas seleccionadas.');
        return;
    }
    if (!confirm('Eliminar definitivamente ' + ids.length + ' variedad(es) discontinuada(s)? Esta acción no se puede deshacer.')) return;

    const fd = new FormData();
    fd.append('_csrf', csrfToken);
    ids.forEach(id => fd.append('ids[]', String(id)));
    fetch('/admin/stock/eliminar-discontinuadas', { method: 'POST', body: fd })
        .then(() => window.location.reload())
        .catch(() => alert('Error al eliminar.'));
}
</script>
