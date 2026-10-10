<?php
$q = (string)($q ?? '');
$codsub = (int)($codsub ?? 0);
$codrub = (int)($codrub ?? 0);
$fecDesde = (string)($fecDesde ?? '');
$fecHasta = (string)($fecHasta ?? '');
$iddepo = (int)($iddepo ?? 0);
$conStock = (bool)($conStock ?? true);
$rows = $rows ?? [];
$brands = $brands ?? [];
$categories = $categories ?? [];
$depositos = $depositos ?? [];
$csrf = (string)($csrf ?? '');
$page = (int)($page ?? 1);
$perPage = (int)($perPage ?? 50);
$total = (int)($total ?? 0);
$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
$from = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
$to = min($page * $perPage, $total);

$preserve = ['stock' => $conStock ? '1' : '0'];
if ($q !== '') $preserve['q'] = $q;
if ($codsub > 0) $preserve['codsub'] = (string)$codsub;
if ($codrub > 0) $preserve['codrub'] = (string)$codrub;
if ($fecDesde !== '') $preserve['fecdesde'] = $fecDesde;
if ($fecHasta !== '') $preserve['fechasta'] = $fecHasta;
if ($iddepo > 0) $preserve['iddepo'] = (string)$iddepo;
$pageUrl = fn(array $extra) => '/admin/productos/etiquetas-a4?' . http_build_query(array_merge($preserve, $extra));

$fecompraClass = static function (string $fcompra): string {
    if (in_array($fcompra, ['', '0000-00-00', '0000-00-00 00:00:00'], true)) {
        return 'text-muted';
    }
    try {
        $d = new DateTime(substr($fcompra, 0, 10));
        $today = new DateTime('today');
        $days = (int)$today->diff($d)->format('%r%a');
    } catch (\Throwable $e) {
        return 'text-muted';
    }
    if ($days < -90) return 'text-danger fw-bold';
    if ($days < -60) return 'text-warning fw-bold';
    return 'text-success';
};
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Etiquetas de precio A4</h4>
        <p class="text-muted small">Elegí variedades, cantidades e imprimí en hoja A4 (~48×30 mm, 4 por fila)</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos"><i class="bi bi-arrow-left"></i> Productos</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/productos/etiquetas-a4" class="row g-2">
            <div class="col-lg-3">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por id, nombre, código, variedad o codscan" />
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
                <select class="form-select form-select-sm" name="codrub">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)($category['codrub'] ?? 0) ?>" <?= $codrub === (int)($category['codrub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($category['nomrub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="iddepo">
                    <option value="0">Todos los depósitos</option>
                    <?php foreach ($depositos as $dep): ?>
                        <option value="<?= (int)($dep['iddepo'] ?? 0) ?>" <?= $iddepo === (int)($dep['iddepo'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($dep['nomdepo'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1">
                <input class="form-control form-control-sm" type="date" name="fecdesde" value="<?= htmlspecialchars($fecDesde) ?>" title="F.compra desde" placeholder="F.compra desde" />
            </div>
            <div class="col-lg-1">
                <input class="form-control form-control-sm" type="date" name="fechasta" value="<?= htmlspecialchars($fecHasta) ?>" title="F.compra hasta" placeholder="F.compra hasta" />
            </div>
            <div class="col-lg-1">
                <select class="form-select form-select-sm" name="stock" title="Stock en depósito">
                    <option value="1" <?= $conStock ? 'selected' : '' ?>>Con stock</option>
                    <option value="0" <?= !$conStock ? 'selected' : '' ?>>Todo</option>
                </select>
            </div>
            <div class="col-lg-12 d-flex justify-content-end">
                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<form method="post" action="/admin/productos/etiquetas-a4/imprimir" id="formEtiquetas">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="text-muted small"><?= number_format($from, 0, ',', '.') ?>–<?= number_format($to, 0, ',', '.') ?> de <?= number_format($total, 0, ',', '.') ?> variedades<?= $conStock ? ' (solo con stock en depósito)' : '' ?></div>
                <div class="d-flex gap-2 align-items-center">
                    <a href="#" class="small" id="linkTildarTodo">Tildar todo</a>
                    <span class="badge bg-accent text-dark" id="contadorSel">0 etiquetas</span>
                    <button class="btn btn-accent btn-sm" type="submit" id="btnImprimir"><i class="bi bi-printer"></i> Imprimir</button>
                </div>
            </div>
            <?php if (!$rows): ?>
                <div class="text-muted">No hay variedades con esos filtros.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm align-middle">
                        <thead>
                            <tr>
                                <th style="width:30px"><input type="checkbox" id="checkAll" /></th>
                                <th style="width:70px">Cant.</th>
                                <th>ID</th>
                                <th>Producto</th>
                                <th>Variedad</th>
                                <th>Codscan</th>
                                <th>Marca</th>
                                <th>Categoría</th>
                                <th>F.compra</th>
                                <th class="text-end">Stock</th>
                                <th class="text-end">P. minorista</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rows as $r):
                                $idg = (int)($r['idcodgusto'] ?? 0);
                                $minorista = number_format((float)($r['precio'] ?? 0) * (1 + ((float)($r['tiva'] ?? 0) / 100)), 0, ',', '.');
                            ?>
                                <tr>
                                    <td><input type="checkbox" class="checkSel" name="sel[]" value="<?= $idg ?>" /></td>
                                    <td><input type="number" class="form-control form-control-sm" name="qty[<?= $idg ?>]" value="1" min="1" max="999" /></td>
                                    <td><?= (int)($r['idprodu'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars(mb_substr((string)($r['produ'] ?? ''), 0, 40)) ?></td>
                                    <td><?= htmlspecialchars((string)($r['nomgusto'] ?? '')) ?></td>
                                    <td><code><?= htmlspecialchars((string)($r['codscan'] ?? '')) ?></code></td>
                                    <td><?= htmlspecialchars((string)($r['nomsub'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($r['nomrub'] ?? '')) ?></td>
                                    <td class="<?= $fecompraClass((string)($r['fecompra'] ?? '')) ?>"><span class="small"><?= $r['fecompra'] && !in_array($r['fecompra'], ['0000-00-00', '0000-00-00 00:00:00'], true) ? htmlspecialchars(substr((string)$r['fecompra'], 0, 10)) : '—' ?></span></td>
                                    <td class="text-end"><?= (int)($r['stock_deposito'] ?? 0) ?></td>
                                    <td class="text-end fw-bold"><?= $minorista ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            <?php if ($totalPages > 1): ?>
                <nav>
                    <ul class="pagination pagination-sm justify-content-center mb-0">
                        <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page - 1])) ?>">«</a></li>
                        <?php for ($p = max(1, $page - 4); $p <= min($totalPages, $page + 4); $p++): ?>
                            <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $p])) ?>"><?= $p ?></a></li>
                        <?php endfor; ?>
                        <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>"><a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page + 1])) ?>">»</a></li>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</form>

<script>
(function () {
    const checkAll = document.getElementById('checkAll');
    const selBoxes = () => document.querySelectorAll('.checkSel');
    const contador = document.getElementById('contadorSel');
    const btnImprimir = document.getElementById('btnImprimir');

    function actualizar() {
        let n = 0;
        selBoxes().forEach(function (b) {
            if (!b.checked) return;
            const qtyInput = document.querySelector('input[name="qty[' + b.value + ']"]');
            n += Math.max(1, parseInt(qtyInput && qtyInput.value, 10) || 1);
        });
        contador.textContent = n + ' etiqueta' + (n === 1 ? '' : 's');
        btnImprimir.disabled = n === 0;
    }

    if (checkAll) {
        checkAll.addEventListener('change', function () {
            selBoxes().forEach(function (b) { b.checked = checkAll.checked; });
            actualizar();
        });
    }
    const linkTildar = document.getElementById('linkTildarTodo');
    if (linkTildar) {
        linkTildar.addEventListener('click', function (e) {
            e.preventDefault();
            selBoxes().forEach(function (b) { b.checked = true; });
            if (checkAll) checkAll.checked = true;
            actualizar();
        });
    }
    document.addEventListener('change', function (e) {
        if (e.target.classList.contains('checkSel') || e.target.id === 'checkAll') actualizar();
    });
    document.addEventListener('input', function (e) {
        if (e.target.name && e.target.name.indexOf('qty[') === 0) actualizar();
    });
    document.getElementById('formEtiquetas').addEventListener('submit', function (e) {
        let n = 0;
        selBoxes().forEach(function (b) { if (b.checked) n++; });
        if (n === 0) {
            e.preventDefault();
            alert('Tildá al menos una variedad para imprimir.');
        }
    });
    actualizar();
})();
</script>
