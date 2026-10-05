<?php
use Perfushopping\Web\Support\Format;

$q = (string)($q ?? '');
$codsub = (int)($codsub ?? 0);
$codrub = (int)($codrub ?? 0);
$sort = (string)($sort ?? 'id');
$order = (string)($order ?? 'desc');
$view = (string)($view ?? 'cards');
$brands = $brands ?? [];
$categories = $categories ?? [];
$products = $products ?? [];
$page = (int)($page ?? 1);
$perPage = (int)($perPage ?? 60);
$total = (int)($total ?? 0);
$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
$from = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
$to = min($page * $perPage, $total);

$sortable = ['id'=>'ID','codprodu'=>'Código','produ'=>'Producto','marca'=>'Marca','categoria'=>'Categoría','precio'=>'Precio','fecompra'=>'F.compra'];
$preserve = [];
if ($q !== '') $preserve['q'] = $q;
if ($codsub > 0) $preserve['codsub'] = (string)$codsub;
if ($codrub > 0) $preserve['codrub'] = (string)$codrub;
if ($view !== 'cards') $preserve['view'] = $view;
$preserve['sort'] = $sort;
$preserve['order'] = $order;
$pageUrl = fn(array $extra) => '/admin/productos?' . http_build_query(array_merge($preserve, $extra));
$sortLink = fn(string $col) => '/admin/productos?' . http_build_query(array_merge($preserve, ['sort' => $col, 'order' => ($sort === $col && $order === 'asc') ? 'desc' : 'asc']));
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Productos</h4>
        <p class="text-muted small">Busca productos, edita precios, visibilidad y más</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-accent btn-sm" href="/admin/productos/nuevo"><i class="bi bi-plus-lg"></i> Nuevo</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/productos/importar"><i class="bi bi-upload"></i> Importar</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/productos/actualizar-precios"><i class="bi bi-percent"></i> Precios</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/productos" class="row g-2">
            <div class="col-lg-5">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por id, nombre, código, variedad o codscan" />
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="codsub">
                    <option value="0">Todas las marcas</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)($brand['codsub'] ?? 0) ?>" <?= $codsub === (int)($brand['codsub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($brand['nomsub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="codrub">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)($category['codrub'] ?? 0) ?>" <?= $codrub === (int)($category['codrub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($category['nomrub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>" />
            <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>" />
            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>" />
            <div class="col-lg-1 d-flex gap-1">
                <button class="btn btn-accent btn-sm flex-fill" type="submit"><i class="bi bi-search"></i></button>
                <?php if ($q !== '' || $codsub > 0 || $codrub > 0): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
        <div class="d-flex gap-2 mt-2">
            <div class="btn-group btn-group-sm">
                <a class="btn <?= $view === 'cards' ? 'btn-accent' : 'btn-outline-secondary' ?>" href="/admin/productos?<?= http_build_query(array_merge($preserve, ['view'=>'cards'])) ?>"><i class="bi bi-grid"></i> Tarjetas</a>
                <a class="btn <?= $view === 'table' ? 'btn-accent' : 'btn-outline-secondary' ?>" href="/admin/productos?<?= http_build_query(array_merge($preserve, ['view'=>'table'])) ?>"><i class="bi bi-list"></i> Tabla</a>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="small text-muted">
        <?php if ($total > 0): ?>
            Mostrando <?= $from ?>–<?= $to ?> de <?= $total ?> productos
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width:auto" onchange="window.location.href='<?= htmlspecialchars($pageUrl(['page' => '1', 'per_page' => ''])) ?>' + this.value">
            <?php foreach ([30, 60, 100, 200] as $pp): ?>
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

<?php if (!$products): ?>
    <div class="alert alert-info">No se encontraron productos.</div>
<?php elseif ($view === 'table'): ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-admin mb-0">
                <thead>
                    <tr>
                        <?php foreach ($sortable as $col => $label):
                            $active = $sort === $col;
                        ?>
                            <th class="<?= $active ? 'sort-active' : '' ?>">
                                <a href="<?= htmlspecialchars($sortLink($col)) ?>" class="text-decoration-none d-flex align-items-center gap-1">
                                    <?= htmlspecialchars($label) ?>
                                    <?php if ($active): ?>
                                        <i class="bi bi-chevron-<?= $order === 'asc' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                        <?php endforeach; ?>
                        <th>Var.</th>
                        <th>Web</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $item):
                        $itemId = (int)($item['idprodu'] ?? 0);
                        $itemIva = (float)($item['tiva'] ?? 0);
                        $itemGross = (float)($item['precio'] ?? 0) * (1 + ($itemIva / 100));
                        $query = [];
                        if ($q !== '') $query['q'] = $q;
                        if ($codsub > 0) $query['codsub'] = (string)$codsub;
                        if ($codrub > 0) $query['codrub'] = (string)$codrub;
                        $href = '/admin/productos/' . $itemId . ($query ? '?' . http_build_query($query) : '');
                    ?>
                        <tr data-idprodu="<?= $itemId ?>"
                            data-precomp="<?= htmlspecialchars((string)($item['precomp'] ?? '0')) ?>"
                            data-ganan1="<?= htmlspecialchars((string)($item['ganan1'] ?? '0')) ?>"
                            data-ganan2="<?= htmlspecialchars((string)($item['ganan2'] ?? '0')) ?>"
                            data-precio="<?= htmlspecialchars((string)($item['precio'] ?? '0')) ?>"
                            data-precio1="<?= htmlspecialchars((string)($item['precio1'] ?? '0')) ?>"
                            data-iva="<?= htmlspecialchars((string)$itemIva) ?>">
                            <td><strong>#<?= $itemId ?></strong></td>
                            <td><code><?= htmlspecialchars((string)($item['codprodu'] ?? '-')) ?></code></td>
                            <td><a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars(mb_substr((string)($item['produ'] ?? ''), 0, 60)) ?></a></td>
                            <td class="small"><?= htmlspecialchars((string)($item['nomsub'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($item['nomrub'] ?? '-')) ?></td>
                            <td class="text-end js-precio-cell"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($itemGross * 100))) ?></td>
                            <td class="small"><?php $fcompra = (string)($item['fecompra'] ?? ''); ?><?= htmlspecialchars(in_array($fcompra, ['', '0000-00-00'], true) ? '—' : $fcompra) ?></td>
                            <td class="text-center"><?= (int)($item['variants_count'] ?? 0) ?></td>
                            <td class="text-center">
                                <span class="badge <?= ((int)($item['enweb'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>" style="font-size:10px"><?= ((int)($item['enweb'] ?? 0) === 1) ? 'ON' : 'OFF' ?></span>
                            </td>
                            <td class="text-nowrap">
                                <button type="button" class="btn btn-sm btn-outline-success py-0 px-1 js-quick-precio" title="Precios rápidos" style="font-size:11px"><i class="bi bi-calculator"></i></button>
                                <a class="btn btn-sm btn-outline-secondary py-0 px-1" href="<?= htmlspecialchars($href) ?>" style="font-size:11px"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <tr id="quickPrecioRow" data-csrf="<?= htmlspecialchars($csrf ?? '') ?>" style="display:none">
                        <td colspan="10">
                            <div class="d-flex align-items-center gap-2 flex-nowrap py-1">
                                <span class="small fw-semibold text-nowrap">Costo</span>
                                <input class="form-control form-control-sm q-costo" style="width:100px" inputmode="decimal" title="Costo (si tiene IVA incluido, marcar IVA inc.)" />
                                <div class="form-check m-0 flex-shrink-0" title="El costo ingresado tiene IVA incluido">
                                    <input class="form-check-input q-iva" type="checkbox" id="qIvaInc" />
                                    <label class="form-check-label small" for="qIvaInc">IVA inc.</label>
                                </div>
                                <div class="input-group input-group-sm flex-shrink-0" style="width:115px">
                                    <input class="form-control form-control-sm q-dto" inputmode="decimal" placeholder="0" title="Descuento % aplicado al costo" aria-label="Descuento %" />
                                    <span class="input-group-text">Dto%</span>
                                </div>
                                <small class="text-muted text-nowrap" id="qResumen" title="Costo neto resultante (ya con descuento e IVA aplicados)"></small>
                                <span class="vr mx-1"></span>
                                <span class="small text-nowrap">Margen minorista</span>
                                <input class="form-control form-control-sm q-g1" style="width:72px" inputmode="decimal" aria-label="Margen minorista %" />
                                <span class="text-muted">&rarr;</span>
                                <input class="form-control form-control-sm q-p1" style="width:105px" inputmode="decimal" title="Precio minorista final (con IVA)" aria-label="Precio minorista" />
                                <span class="vr mx-1"></span>
                                <span class="small text-nowrap">Margen mayorista</span>
                                <input class="form-control form-control-sm q-g2" style="width:72px" inputmode="decimal" aria-label="Margen mayorista %" />
                                <span class="text-muted">&rarr;</span>
                                <input class="form-control form-control-sm q-p2" style="width:105px" inputmode="decimal" title="Precio mayorista final (con IVA)" aria-label="Precio mayorista" />
                                <button type="button" class="btn btn-accent btn-sm text-nowrap q-save"><i class="bi bi-check-lg"></i> Guardar</button>
                                <span class="small fw-semibold q-status"></span>
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($products as $item):
                        $itemId = (int)($item['idprodu'] ?? 0);
                        $itemIva = (float)($item['tiva'] ?? 0);
                        $itemGross = (float)($item['precio'] ?? 0) * (1 + ($itemIva / 100));
                        $query = [];
                        if ($q !== '') $query['q'] = $q;
                        if ($codsub > 0) $query['codsub'] = (string)$codsub;
                        if ($codrub > 0) $query['codrub'] = (string)$codrub;
                        $href = '/admin/productos/' . $itemId . ($query ? '?' . http_build_query($query) : '');
                    ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card shadow-sm h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <strong>#<?= $itemId ?></strong>
                                        <span class="badge <?= ((int)($item['enweb'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>"><?= ((int)($item['enweb'] ?? 0) === 1) ? 'En web' : 'Oculto' ?></span>
                                    </div>
                                    <h6 class="card-title mb-1" style="font-size:14px"><a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none"><?= htmlspecialchars((string)($item['produ'] ?? '')) ?></a></h6>
                                    <div class="small text-muted mb-2">
                                        <?= htmlspecialchars((string)($item['nomsub'] ?? '-')) ?> · <?= htmlspecialchars((string)($item['nomrub'] ?? '-')) ?>
                                    </div>
                                    <textarea class="form-control form-control-sm inline-desc mb-2" rows="2" data-idprodu="<?= $itemId ?>" data-csrf="<?= htmlspecialchars($csrf ?? '') ?>" placeholder="Descripción..."><?= htmlspecialchars((string)($item['observ'] ?? '')) ?></textarea>
                                    <span class="desc-status small text-success" style="display:none">Guardado</span>
                                    <div class="d-flex justify-content-between mt-1 small">
                                        <span><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($itemGross * 100))) ?></span>
                                        <span><?= (int)($item['variants_count'] ?? 0) ?> var.</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
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
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.inline-desc').forEach(function (ta) {
        ta.addEventListener('change', function () {
            const textarea = this;
            const idprodu = textarea.dataset.idprodu;
            const csrf = textarea.dataset.csrf;
            const status = textarea.closest('.card-body').querySelector('.desc-status');
            fetch('/admin/productos/save-description', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({_csrf: csrf, idprodu: idprodu, observ: textarea.value})
            }).then(r => r.json()).then(res => {
                if (status) {
                    status.style.display = 'inline';
                    status.textContent = res.ok ? 'Guardado' : 'Error';
                    status.className = 'desc-status small ' + (res.ok ? 'text-success' : 'text-danger');
                    setTimeout(() => { status.style.display = 'none'; }, 1500);
                }
            }).catch(() => {
                if (status) { status.style.display = 'inline'; status.textContent = 'Error de conexión'; status.className = 'desc-status small text-danger'; }
            });
        });
    });
});
</script>

<script>
(function () {
    var quickRow = document.getElementById('quickPrecioRow');
    if (!quickRow) return;
    var currentTr = null;
    var ivaPct = 0;
    var csrf = quickRow.dataset.csrf || '';

    function qs(sel) { return quickRow.querySelector(sel); }
    function parseImp(v) { return parseFloat(String(v || '0').replace(',', '.')) || 0; }

    function fmt(n) {
        var parts = (Math.round(n * 100) / 100).toFixed(2).split('.');
        return parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.') + ',' + (parts[1] || '00');
    }

    function costoNeto() {
        var c = parseImp(qs('.q-costo').value);
        var dto = parseImp(qs('.q-dto').value);
        if (dto > 0) c = c * (1 - Math.min(dto, 100) / 100);
        if (qs('.q-iva').checked && ivaPct > 0) c = c / (1 + ivaPct / 100);
        return c > 0 ? c : 0;
    }

    function resumen() {
        qs('#qResumen').textContent = 'Neto $' + fmt(costoNeto());
    }

    function calcPrecios() {
        var c = costoNeto();
        if (c > 0) {
            var g1 = parseImp(qs('.q-g1').value);
            var g2 = parseImp(qs('.q-g2').value);
            qs('.q-p1').value = (c * (1 + g1 / 100) * (1 + ivaPct / 100)).toFixed(2);
            qs('.q-p2').value = (c * (1 + g2 / 100) * (1 + ivaPct / 100)).toFixed(2);
        }
        resumen();
    }

    function calcMargenes() {
        var c = costoNeto();
        if (c <= 0) { resumen(); return; }
        var p1 = parseImp(qs('.q-p1').value);
        var p2 = parseImp(qs('.q-p2').value);
        if (p1 > 0) qs('.q-g1').value = (((p1 / (1 + ivaPct / 100)) / c - 1) * 100).toFixed(2);
        if (p2 > 0) qs('.q-g2').value = (((p2 / (1 + ivaPct / 100)) / c - 1) * 100).toFixed(2);
        resumen();
    }

    ['.q-costo', '.q-dto', '.q-g1', '.q-g2'].forEach(function (sel) {
        qs(sel).addEventListener('input', calcPrecios);
    });
    qs('.q-iva').addEventListener('change', calcPrecios);
    ['.q-p1', '.q-p2'].forEach(function (sel) {
        qs(sel).addEventListener('input', calcMargenes);
    });

    function closeQuick() {
        quickRow.style.display = 'none';
        currentTr = null;
    }

    function openQuick(tr) {
        currentTr = tr;
        ivaPct = parseFloat(tr.dataset.iva) || 0;
        qs('.q-costo').value = tr.dataset.precomp || '0';
        qs('.q-dto').value = '0';
        qs('.q-iva').checked = false;
        qs('.q-g1').value = tr.dataset.ganan1 || '0';
        qs('.q-g2').value = tr.dataset.ganan2 || '0';
        qs('.q-p1').value = ((parseFloat(tr.dataset.precio) || 0) * (1 + ivaPct / 100)).toFixed(2);
        qs('.q-p2').value = ((parseFloat(tr.dataset.precio1) || 0) * (1 + ivaPct / 100)).toFixed(2);
        qs('.q-status').textContent = '';
        qs('.q-status').className = 'small fw-semibold q-status';
        tr.insertAdjacentElement('afterend', quickRow);
        quickRow.style.display = '';
        resumen();
    }

    document.addEventListener('click', function (e) {
        var btn = e.target.closest ? e.target.closest('.js-quick-precio') : null;
        if (btn) {
            var tr = btn.closest('tr');
            if (tr && currentTr === tr && quickRow.style.display !== 'none' && quickRow.previousElementSibling === tr) {
                closeQuick();
            } else if (tr) {
                openQuick(tr);
            }
            return;
        }
        if (quickRow.style.display !== 'none' && !e.target.closest('#quickPrecioRow')) closeQuick();
    });

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && quickRow.style.display !== 'none') closeQuick();
    });

    qs('.q-save').addEventListener('click', function () {
        if (!currentTr) return;
        var status = qs('.q-status');
        status.textContent = 'Guardando…';
        status.className = 'small fw-semibold q-status text-muted';
        var tr = currentTr;
        var body = new URLSearchParams({
            _csrf: csrf,
            idprodu: tr.dataset.idprodu || '0',
            precomp: costoNeto().toFixed(2),
            ganan1: qs('.q-g1').value.replace(',', '.') || '0',
            ganan2: qs('.q-g2').value.replace(',', '.') || '0',
            precio_gross: qs('.q-p1').value.replace(',', '.'),
            precio1_gross: qs('.q-p2').value.replace(',', '.')
        });
        fetch('/admin/productos/save-precios', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body
        }).then(function (r) { return r.json(); }).then(function (res) {
            if (res.ok) {
                tr.dataset.precomp = body.get('precomp');
                tr.dataset.ganan1 = body.get('ganan1');
                tr.dataset.ganan2 = body.get('ganan2');
                tr.dataset.precio = res.precio;
                tr.dataset.precio1 = res.precio1;
                var cell = tr.querySelector('.js-precio-cell');
                if (cell) cell.textContent = '$' + new Intl.NumberFormat('es-AR', {maximumFractionDigits: 0}).format(Math.round(res.precio_gross));
                status.textContent = '✓ Guardado';
                status.className = 'small fw-semibold q-status text-success';
                setTimeout(function () {
                    if (status.textContent === '✓ Guardado') status.textContent = '';
                }, 2500);
            } else {
                status.textContent = res.error || 'Error al guardar';
                status.className = 'small fw-semibold q-status text-danger';
            }
        }).catch(function () {
            status.textContent = 'Error de conexión';
            status.className = 'small fw-semibold q-status text-danger';
        });
    });
})();
</script>
