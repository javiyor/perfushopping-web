<?php
use Perfushopping\Web\Support\Format;

$q = (string)($q ?? '');
$codsub = (int)($codsub ?? 0);
$codprove = (string)($codprove ?? '');
$fecompraDesde = (string)($fecompraDesde ?? '');
$fecompraHasta = (string)($fecompraHasta ?? '');
$brands = $brands ?? [];
$categories = $categories ?? [];
$proveedores = $proveedores ?? [];
$products = $products ?? [];
$page = (int)($page ?? 1);
$perPage = (int)($perPage ?? 50);
$total = (int)($total ?? 0);
$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
$from = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
$to = min($page * $perPage, $total);

$preserve = [];
if ($q !== '') $preserve['q'] = $q;
if ($codsub > 0) $preserve['codsub'] = (string)$codsub;
if ($codprove !== '') $preserve['codprove'] = $codprove;
if ($fecompraDesde !== '') $preserve['fecompra_desde'] = $fecompraDesde;
if ($fecompraHasta !== '') $preserve['fecompra_hasta'] = $fecompraHasta;

$pageUrl = fn(array $extra) => '/admin/productos/actualizar-precios?' . http_build_query(array_merge($preserve, $extra));
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Actualizar precios</h4>
        <p class="text-muted small">Aplica un porcentaje de aumento o descuento a productos seleccionados</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/productos/actualizar-precios" class="row g-2">
            <div class="col-lg-3">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, código o ID" />
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="codsub">
                    <option value="0">Todas las marcas</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)($brand['codsub'] ?? 0) ?>" <?= $codsub === (int)($brand['codsub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($brand['nomsub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="codprove">
                    <option value="">Todos los proveedores</option>
                    <?php foreach ($proveedores as $pv): ?>
                        <option value="<?= htmlspecialchars((string)($pv['codprove'] ?? '')) ?>" <?= $codprove === (string)($pv['codprove'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars(mb_substr((string)($pv['razon'] ?? ''), 0, 40)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <input class="form-control form-control-sm" type="date" name="fecompra_desde" value="<?= htmlspecialchars($fecompraDesde) ?>" placeholder="F.compra desde" />
            </div>
            <div class="col-lg-2">
                <input class="form-control form-control-sm" type="date" name="fecompra_hasta" value="<?= htmlspecialchars($fecompraHasta) ?>" placeholder="F.compra hasta" />
            </div>
            <div class="col-lg-1 d-flex gap-1">
                <button class="btn btn-accent btn-sm flex-fill" type="submit"><i class="bi bi-search"></i></button>
                <?php if ($q !== '' || $codsub > 0 || $codprove !== '' || $fecompraDesde !== '' || $fecompraHasta !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos/actualizar-precios"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if ($total > 0): ?>
<form method="post" action="/admin/productos/actualizar-precios" id="price-update-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />

    <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="small text-muted">
            Mostrando <?= $from ?>–<?= $to ?> de <?= $total ?> productos
        </div>
        <div class="d-flex align-items-center gap-2">
            <select class="form-select form-select-sm" style="width:auto" onchange="window.location.href='<?= htmlspecialchars($pageUrl(['page' => '1', 'per_page' => ''])) ?>' + this.value">
                <?php foreach ([30, 50, 100, 200] as $pp): ?>
                    <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>><?= $pp ?> por página</option>
                <?php endforeach; ?>
            </select>
            <?php if ($totalPages > 1): ?>
                <nav aria-label="Paginación">
                    <ul class="pagination pagination-sm mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page - 1])) ?>">&laquo;</a>
                        </li>
                        <?php
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        if ($startPage > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => 1])) ?>">1</a>
                            </li>
                            <?php if ($startPage > 2): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif;
                        endif;
                        for ($i = $startPage; $i <= $endPage; $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $i])) ?>"><?= $i ?></a>
                            </li>
                        <?php endfor;
                        if ($endPage < $totalPages):
                            if ($endPage < $totalPages - 1): ?>
                                <li class="page-item disabled"><span class="page-link">...</span></li>
                            <?php endif; ?>
                            <li class="page-item">
                                <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $totalPages])) ?>"><?= $totalPages ?></a>
                            </li>
                        <?php endif; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page + 1])) ?>">&raquo;</a>
                        </li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow-sm mb-3">
        <div class="table-responsive">
            <table class="table table-sm table-admin mb-0">
                <thead>
                    <tr>
                        <th style="width:40px">
                            <input class="form-check-input" type="checkbox" id="select-all" />
                        </th>
                        <th>ID</th>
                        <th>Código</th>
                        <th>Producto</th>
                        <th>Marca</th>
                        <th>Proveedor</th>
                        <th class="text-end">Precomp</th>
                        <th class="text-end">Precio</th>
                        <th class="text-end">Precio1</th>
                        <th>F.Compra</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $item):
                        $itemId = (int)($item['idprodu'] ?? 0);
                    ?>
                        <tr>
                            <td>
                                <input class="form-check-input product-checkbox" type="checkbox" name="productos[]" value="<?= $itemId ?>" />
                            </td>
                            <td><strong>#<?= $itemId ?></strong></td>
                            <td><code><?= htmlspecialchars((string)($item['codprodu'] ?? '-')) ?></code></td>
                            <td><?= htmlspecialchars(mb_substr((string)($item['produ'] ?? ''), 0, 60)) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($item['nomsub'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($item['nomprove'] ?? '-')) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round((float)($item['precomp'] ?? 0) * 100))) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round((float)($item['precio'] ?? 0) * 100))) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round((float)($item['precio1'] ?? 0) * 100))) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($item['fecompra'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body d-flex align-items-center gap-3 flex-wrap">
            <div class="fw-semibold small">Aplicar:</div>
            <div class="input-group input-group-sm" style="max-width:180px">
                <input class="form-control form-control-sm" type="number" step="0.01" name="porcentaje" id="porcentaje" placeholder="Ej: 10" required />
                <span class="input-group-text">%</span>
            </div>
            <button class="btn btn-accent btn-sm" type="submit" id="btn-aplicar">
                <i class="bi bi-check-lg"></i> Actualizar seleccionados
            </button>
            <span class="small text-muted" id="selected-count">0 productos seleccionados</span>
        </div>
    </div>
</form>
<?php elseif ($q !== '' || $codsub > 0 || $codprove !== '' || $fecompraDesde !== '' || $fecompraHasta !== ''): ?>
    <div class="alert alert-info">No se encontraron productos con los filtros seleccionados.</div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
<div class="d-flex justify-content-center mt-3">
    <nav>
        <ul class="pagination pagination-sm mb-0">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page - 1])) ?>">&laquo;</a>
            </li>
            <?php
            $startPage = max(1, $page - 2);
            $endPage = min($totalPages, $page + 2);
            if ($startPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => 1])) ?>">1</a>
                </li>
                <?php if ($startPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif;
            endif;
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $i])) ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            if ($endPage < $totalPages):
                if ($endPage < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $totalPages])) ?>"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page + 1])) ?>">&raquo;</a>
            </li>
        </ul>
    </nav>
</div>
<?php endif; ?>

<script>
document.getElementById('select-all')?.addEventListener('change', function() {
    document.querySelectorAll('.product-checkbox').forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

document.querySelectorAll('.product-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const count = document.querySelectorAll('.product-checkbox:checked').length;
    const el = document.getElementById('selected-count');
    if (el) el.textContent = count + ' producto' + (count !== 1 ? 's' : '') + ' seleccionado' + (count !== 1 ? 's' : '');
}

document.getElementById('btn-aplicar')?.addEventListener('click', function(e) {
    const checked = document.querySelectorAll('.product-checkbox:checked').length;
    if (checked === 0) {
        e.preventDefault();
        alert('Selecciona al menos un producto.');
        return;
    }
    const pct = document.getElementById('porcentaje').value.trim();
    if (pct === '' || parseFloat(pct) === 0) {
        e.preventDefault();
        alert('Ingresa un porcentaje distinto de cero.');
        return;
    }
    if (!confirm('¿Aplicar ' + pct + '% a ' + checked + ' producto' + (checked !== 1 ? 's' : '') + '?')) {
        e.preventDefault();
    }
});
</script>
