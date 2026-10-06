<?php
$depositos = $depositos ?? [];
$producto = $producto ?? null;
$variantes = $variantes ?? [];
$initialAjusteItems = $initialAjusteItems ?? [];
$solicitudesPendientes = $solicitudesPendientes ?? [];
$misSolicitudes = $misSolicitudes ?? [];
$historialAjustes = $historialAjustes ?? [];
$esSuperadmin = $esSuperadmin ?? false;
$histDesde = $histDesde ?? '';
$histHasta = $histHasta ?? '';
$histDepDesde = (int)($histDepDesde ?? 0);
$histDepHasta = (int)($histDepHasta ?? 0);
$histPage = max(1, (int)($histPage ?? 1));
$histPages = max(1, (int)($histPages ?? 1));
$histTotal = (int)($histTotal ?? count($historialAjustes));
$histHayFiltros = $histDesde !== '' || $histHasta !== '' || $histDepDesde > 0 || $histDepHasta > 0;
$histBase = '/admin/stock/ajuste' . ((int)($producto['idprodu'] ?? 0) > 0 ? '/' . (int)$producto['idprodu'] : '');
$histQuery = http_build_query(array_filter([
    'desde' => $histDesde,
    'hasta' => $histHasta,
    'dep_desde' => $histDepDesde > 0 ? $histDepDesde : '',
    'dep_hasta' => $histDepHasta > 0 ? $histDepHasta : '',
], static fn ($v) => $v !== ''));
$histPageUrl = static function (int $p) use ($histBase, $histQuery): string {
    $qs = $histQuery !== '' ? '&' . $histQuery : '';
    return $histBase . '?page=' . $p . $qs;
};
$histWinStart = max(1, min($histPage - 3, $histPages - 6));
$histWinEnd = min($histPages, $histWinStart + 6);
$histWinStart = max(1, $histWinEnd - 6);
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/stock">Stock</a></li>
        <li class="breadcrumb-item active">Ajuste manual</li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Registrar ajuste de stock</div>
            <div class="card-body">
                <form method="post" action="/admin/stock/ajuste/guardar" id="ajusteForm">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Agregar productos al movimiento</label>
                        <div class="input-group">
                            <input class="form-control form-control-sm" id="productoSearch" placeholder="Buscar por nombre, código o ID y tocar para agregar" autocomplete="off" />
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="clearProductoSearch"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <div id="productoResults" class="list-group mt-1" style="display:none;position:absolute;z-index:1050;max-height:300px;overflow-y:auto"></div>
                    </div>

                    <div class="mb-3">
                        <div class="table-responsive">
                            <table class="table table-sm align-middle">
                                <thead>
                                    <tr>
                                        <th style="min-width:220px">Producto</th>
                                        <th style="min-width:180px">Variante</th>
                                        <th style="width:140px">Stock en destino</th>
                                        <th style="width:110px">Cantidad</th>
                                        <th style="width:70px"></th>
                                    </tr>
                                </thead>
                                <tbody id="itemsBody"></tbody>
                            </table>
                        </div>
                        <div class="small text-muted" id="itemsHelp">Podés cargar todos los productos que necesites en un solo movimiento.</div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Depósito desde <span class="text-muted">(resta)</span></label>
                            <select class="form-select form-select-sm" name="iddepodesde" id="iddepodesde">
                                <option value="">Ninguno (solo ingreso)</option>
                                <?php foreach ($depositos as $d): ?>
                                    <option value="<?= (int)$d['iddepo'] ?>"><?= htmlspecialchars($d['nomdepo'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Depósito hasta <span class="text-muted">(suma)</span></label>
                            <select class="form-select form-select-sm" name="iddepohasta" id="iddepohasta">
                                <option value="">Ninguno (solo egreso)</option>
                                <?php foreach ($depositos as $d): ?>
                                    <option value="<?= (int)$d['iddepo'] ?>"><?= htmlspecialchars($d['nomdepo'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Motivo del ajuste</label>
                        <textarea class="form-control form-control-sm" name="motivo" rows="2" required placeholder="Ej: Rotura, vencimiento, sobrante de inventario, corrección..."></textarea>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Registrar ajuste</button>
                    <a class="btn btn-outline-secondary" href="/admin/stock">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <?php if ($esSuperadmin): ?>
        <div class="card shadow-sm mb-3" id="solicitudesPendientes">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Autorizaciones pendientes</span>
                <span class="badge bg-danger"><?= count($solicitudesPendientes) ?></span>
            </div>
            <div class="card-body small p-0">
                <?php if (!$solicitudesPendientes): ?>
                    <div class="p-3 text-muted">No hay solicitudes pendientes.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($solicitudesPendientes as $s): ?>
                            <?php $esPropia = (int)($s['requested_by'] ?? 0) === (int)($adminUser['id'] ?? 0); ?>
                            <div class="list-group-item">
                                <div class="fw-semibold mb-1"><?= htmlspecialchars((string)($s['produ'] ?? 'Producto')) ?></div>
                                <?php if (($s['nomgusto'] ?? '') !== ''): ?>
                                    <div class="text-muted">Variante: <?= htmlspecialchars((string)$s['nomgusto']) ?></div>
                                <?php endif; ?>
                                <div>Desde: <strong><?= htmlspecialchars((string)($s['depo_desde_nombre'] ?? 'Ninguno')) ?></strong></div>
                                <div>Hasta: <strong><?= htmlspecialchars((string)($s['depo_hasta_nombre'] ?? 'Ninguno')) ?></strong></div>
                                <div>Cantidad: <strong><?= (int)($s['cantidad'] ?? 0) ?></strong></div>
                                <div class="text-muted">Motivo: <?= htmlspecialchars((string)($s['motivo'] ?? '')) ?></div>
                                <div class="text-muted">Solicitó: <?= htmlspecialchars((string)($s['requested_by_nombre'] ?? '')) ?> · <?= htmlspecialchars((string)($s['created_at'] ?? '')) ?></div>
                                <?php if ($esPropia): ?>
                                    <div class="mt-2 text-warning">No podés autorizar tu propia solicitud.</div>
                                <?php else: ?>
                                    <div class="d-flex gap-2 mt-2">
                                        <form method="post" action="/admin/stock/ajuste/aprobar" onsubmit="return confirm('Aprobar esta solicitud de ajuste?')">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                            <input type="hidden" name="solicitud_id" value="<?= (int)$s['id'] ?>" />
                                            <button class="btn btn-sm btn-accent" type="submit"><i class="bi bi-check-lg"></i> Aprobar</button>
                                        </form>
                                        <form method="post" action="/admin/stock/ajuste/rechazar" onsubmit="return confirm('Rechazar esta solicitud?')">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                            <input type="hidden" name="solicitud_id" value="<?= (int)$s['id'] ?>" />
                                            <input class="form-control form-control-sm" type="text" name="nota_rechazo" placeholder="Motivo rechazo" style="max-width:150px" />
                                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i></button>
                                        </form>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3" id="misSolicitudes">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Mis solicitudes recientes</span>
                <button class="btn btn-sm btn-outline-secondary" type="button" id="btnActualizarSolicitudes" title="Actualizar solicitudes"><i class="bi bi-arrow-repeat"></i> Actualizar</button>
            </div>
            <div class="card-body small p-0">
                <?php if (!$misSolicitudes): ?>
                    <div class="p-3 text-muted">Todavía no tenés solicitudes.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($misSolicitudes as $s): ?>
                            <?php
                                $status = (string)($s['status'] ?? 'pendiente');
                                $statusClass = $status === 'aprobada' ? 'success' : ($status === 'rechazada' ? 'danger' : ($status === 'procesando' ? 'warning' : 'secondary'));
                                $warningPolitica = $status === 'pendiente'
                                    && (int)($s['depo_desde_marca'] ?? 0) === 2
                                    && (int)($s['depo_hasta_marca'] ?? 0) !== 2;
                            ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="fw-semibold text-truncate" style="max-width:70%"><?= htmlspecialchars((string)($s['produ'] ?? 'Producto')) ?></div>
                                    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                                </div>
                                <?php if ($warningPolitica): ?>
                                    <div class="mb-1"><span class="badge bg-warning text-dark">Pendiente por politica de deposito</span></div>
                                <?php endif; ?>
                                <div><?= (int)($s['cantidad'] ?? 0) ?> u. · <?= htmlspecialchars((string)($s['depo_desde_nombre'] ?? 'Ninguno')) ?> -> <?= htmlspecialchars((string)($s['depo_hasta_nombre'] ?? 'Ninguno')) ?></div>
                                <div class="text-muted"><?= htmlspecialchars((string)($s['created_at'] ?? '')) ?></div>
                                <?php if (($s['rejection_note'] ?? '') !== ''): ?>
                                    <div class="text-danger">Rechazo: <?= htmlspecialchars((string)$s['rejection_note']) ?></div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Instrucciones</div>
            <div class="card-body small">
                <p>Usá este formulario para registrar ajustes manuales de stock:</p>
                <ul class="mb-0">
                    <li><strong>Depósito desde:</strong> origen del movimiento (resta stock)</li>
                    <li><strong>Depósito hasta:</strong> destino del movimiento (suma stock)</li>
                    <li>La cantidad es <strong>siempre positiva</strong></li>
                    <li>Si solo querés <strong>ingresar</strong> stock, dejá "Desde" vacío</li>
                    <li>Si solo querés <strong>egresar</strong> stock, dejá "Hasta" vacío</li>
                    <li>Para <strong>transferir</strong> entre depósitos, completá ambos</li>
                    <li>Si el movimiento sale de un depósito con <strong>marca 2</strong> hacia un depósito con marca distinta de 2 (o egreso sin destino) y no sos superadmin, se enviará a autorización</li>
                    <li>Si el producto tiene variantes, podés ajustar una específica o dejar "Todas" para ajustar el producto base</li>
                    <li>El motivo es obligatorio para mantener trazabilidad</li>
                </ul>
                <hr />
                <p class="text-muted mb-0">Los ajustes quedan registrados en <code>stockcab</code>/<code>stockdet</code> y se actualizan las tablas <code>stock</code>, <code>producto.stocact</code> y <code>gustos.stockact</code> automáticamente.</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Grupos de ajuste generados</span>
        <span class="badge bg-secondary"><?= $histTotal ?><?= $histPages > 1 ? ' · pág. ' . $histPage . '/' . $histPages : '' ?></span>
    </div>
    <div class="card-body pb-2">
        <form method="get" action="<?= htmlspecialchars($histBase) ?>" class="row g-2 align-items-end">
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Fecha desde</label>
                <input type="date" class="form-control form-control-sm" name="desde" value="<?= htmlspecialchars($histDesde) ?>" />
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Fecha hasta</label>
                <input type="date" class="form-control form-control-sm" name="hasta" value="<?= htmlspecialchars($histHasta) ?>" />
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Depósito desde</label>
                <select class="form-select form-select-sm" name="dep_desde">
                    <option value="">Todos</option>
                    <?php foreach ($depositos as $d): ?>
                        <option value="<?= (int)($d['iddepo'] ?? 0) ?>" <?= $histDepDesde === (int)($d['iddepo'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($d['nomdepo'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-6 col-md-2">
                <label class="form-label small mb-1">Depósito hasta</label>
                <select class="form-select form-select-sm" name="dep_hasta">
                    <option value="">Todos</option>
                    <?php foreach ($depositos as $d): ?>
                        <option value="<?= (int)($d['iddepo'] ?? 0) ?>" <?= $histDepHasta === (int)($d['iddepo'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($d['nomdepo'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                <?php if ($histHayFiltros): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($histBase) ?>"><i class="bi bi-x-lg"></i> Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead><tr><th>#</th><th>Fecha</th><th>Tipo</th><th>Motivo</th><th>Desde → Hasta</th><th class="text-center">Ítems</th><th class="text-center">Unid.</th><th style="width:90px"></th></tr></thead>
            <tbody>
            <?php if (!$historialAjustes): ?>
                <tr><td colspan="8" class="text-center text-muted"><?= $histHayFiltros ? 'Sin ajustes para los filtros aplicados.' : 'Sin ajustes registrados.' ?></td></tr>
            <?php else: foreach ($historialAjustes as $h): ?>
                <tr>
                    <td><?= (int)$h['id'] ?></td>
                    <td class="small"><?= htmlspecialchars((string)($h['fecha'] ?? '')) ?></td>
                    <td><span class="badge bg-info text-dark"><?= htmlspecialchars((string)($h['tipo_label'] ?? $h['tipo'] ?? '')) ?></span></td>
                    <td class="small"><?= htmlspecialchars(mb_substr((string)($h['motivo'] ?? ''), 0, 60)) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($h['depo_desde_label'] ?? $h['depo_desde'] ?? '—')) ?> → <?= htmlspecialchars((string)($h['depo_hasta_label'] ?? $h['depo_hasta'] ?? '—')) ?></td>
                    <td class="text-center"><?= (int)($h['items'] ?? 0) ?></td>
                    <td class="text-center"><?= (int)($h['unidades'] ?? 0) ?></td>
                    <td class="text-end text-nowrap">
                        <a class="btn btn-sm btn-outline-primary py-0 px-1" title="Reimprimir grupo" href="/admin/stock/ajuste/imprimir?ids=<?= htmlspecialchars((string)($h['ids'] ?? $h['id'])) ?>" target="_blank"><i class="bi bi-printer"></i></a>
                        <form method="post" action="/admin/stock/ajuste/anular" style="display:inline" onsubmit="return confirm('Anular el grupo #<?= (int)$h['id'] ?> generando el movimiento inverso?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="idcabstock" value="<?= (int)$h['id'] ?>" />
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" title="Anular grupo (movimiento inverso)"><i class="bi bi-arrow-counterclockwise"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($histPages > 1): ?>
        <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="small text-muted"><?= $histTotal ?> grupos · Página <?= $histPage ?> de <?= $histPages ?></span>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $histPage <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($histPage <= 1 ? '#' : $histPageUrl($histPage - 1)) ?>">&laquo;</a>
                </li>
                <?php for ($p = $histWinStart; $p <= $histWinEnd; $p++): ?>
                    <li class="page-item <?= $p === $histPage ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($histPageUrl($p)) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
                <li class="page-item <?= $histPage >= $histPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($histPage >= $histPages ? '#' : $histPageUrl($histPage + 1)) ?>">&raquo;</a>
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<script>
const initialItems = <?= json_encode($initialAjusteItems, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

const searchInput = document.getElementById('productoSearch');
const resultsDiv = document.getElementById('productoResults');
const clearBtn = document.getElementById('clearProductoSearch');
const itemsBody = document.getElementById('itemsBody');
const ajusteForm = document.getElementById('ajusteForm');

function buildVariantOptions(variants) {
    let html = '<option value="0">Todas (producto base)</option>';
    (variants || []).forEach(v => {
        const nombre = escHtml(v.nomgusto || '');
        const barra = v.codscan ? ' · BAR: ' + escHtml(v.codscan) : '';
        html += '<option value="' + (v.idcodgusto || 0) + '">' + nombre + barra + '</option>';
    });
    return html;
}

function addItemRow(product) {
    const tr = document.createElement('tr');
    tr.dataset.productId = String(product.idprodu || 0);

    const code = (product.codprodu || '').trim();
    const price = parseFloat(product.precio || 0);
    const variants = Array.isArray(product.variants) ? product.variants : [];

    tr.innerHTML =
        '<td>' +
            '<input type="hidden" name="idprodu[]" value="' + (product.idprodu || 0) + '" />' +
            '<div class="fw-semibold">' + escHtml(product.produ || '') + '</div>' +
            '<div class="small text-muted">' + escHtml(code) + ' · Stock: ' + (product.stocact ?? 0) + ' · $' + (isNaN(price) ? '0,00' : price.toLocaleString('es-AR', {minimumFractionDigits:2})) + '</div>' +
        '</td>' +
        '<td>' +
            '<select class="form-select form-select-sm" name="idcodgusto[]">' + buildVariantOptions(variants) + '</select>' +
        '</td>' +
        '<td class="text-center">' +
            '<span class="badge bg-secondary stock-destino" data-stock="0">—</span>' +
        '</td>' +
        '<td>' +
            '<input class="form-control form-control-sm" type="number" name="cantidad[]" min="1" step="1" value="1" required />' +
        '</td>' +
        '<td class="text-end">' +
            '<button class="btn btn-sm btn-outline-danger" type="button"><i class="bi bi-trash"></i></button>' +
        '</td>';

    tr.querySelector('button').addEventListener('click', function() {
        tr.remove();
    });
    itemsBody.appendChild(tr);

    // Auto-select variant
    const variantSelect = tr.querySelector('select[name="idcodgusto[]"]');
    if (variantSelect) {
        const matchedId = product.matched_variant_id;
        if (matchedId && matchedId > 0) {
            variantSelect.value = matchedId;
        } else {
            const firstVariant = Array.from(variantSelect.options).find(o => o.value > 0);
            if (firstVariant) {
                variantSelect.value = firstVariant.value;
            }
        }
    }

    // Update stock when variant changes
    variantSelect?.addEventListener('change', () => updateRowStock(tr));

    return tr;
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

let searchTimeout = null;
searchInput.addEventListener('input', function() {
    clearTimeout(searchTimeout);
    const q = this.value.trim();
    if (q.length < 2) { resultsDiv.style.display = 'none'; return; }

    searchTimeout = setTimeout(() => {
        fetch('/admin/stock/ajuste/buscar-productos?q=' + encodeURIComponent(q))
            .then(r => r.json())
            .then(data => {
                if (!data.length) {
                    resultsDiv.innerHTML = '<a class="list-group-item list-group-item-action text-muted">Sin resultados</a>';
                } else {
                    resultsDiv.innerHTML = '';
                    data.forEach(p => {
                        const item = document.createElement('a');
                        item.className = 'list-group-item list-group-item-action';
                        item.href = '#';
                        let html = '<div class="d-flex justify-content-between"><strong>' + escHtml(p.produ) + '</strong> <span class="text-muted small">' + escHtml(p.codprodu || '') + '</span></div>';
                        html += '<div class="small text-muted">Stock: ' + (p.stocact ?? 0) + ' | $' + (parseFloat(p.precio) || 0).toLocaleString('es-AR', {minimumFractionDigits:2}) + '</div>';
                        if (p.variants && p.variants.length) {
                            html += '<div class="small text-info">' + p.variants.length + ' variante(s)</div>';
                        }
                        item.innerHTML = html;
item.addEventListener('click', function(e) {
                    e.preventDefault();
                                    addItemRow(p);
                                    // Auto-select the variant: matched_variant_id (coincidencia exacta codscan) -> first_variant_id (primera variante del producto) -> primera variante > 0
                                    const newRow = itemsBody.lastElementChild;
                                    const variantSelect = newRow ? newRow.querySelector('select[name="idcodgusto[]"]') : null;
                                    if (variantSelect) {
                                        const matchedId = p.matched_variant_id;
                                        const firstVariantId = p.first_variant_id;
                                        if (matchedId && matchedId > 0) {
                                            variantSelect.value = matchedId;
                                        } else if (firstVariantId && firstVariantId > 0) {
                                            variantSelect.value = firstVariantId;
                                        } else {
                                            // Seleccionar la primera variante con value > 0
                                            const firstVariant = Array.from(variantSelect.options).find(o => o.value > 0);
                                            if (firstVariant) {
                                                variantSelect.value = firstVariant.value;
                                            }
                                        }
                                    }
                                    // Focus cantidad y ciclo de escaneo
                                    const qtyInput = newRow.querySelector('input[name="cantidad[]"]');
                                    if (qtyInput) {
                                        qtyInput.focus();
                                        qtyInput.select();
                                        qtyInput.addEventListener('keydown', function onQtyKeydown(e) {
                                            if (e.key === 'Enter') {
                                                e.preventDefault();
                                                qtyInput.removeEventListener('keydown', onQtyKeydown);
                                                searchInput.focus();
                                                searchInput.select();
                                            }
                                        });
                                    }
                                    searchInput.value = '';
                                    resultsDiv.style.display = 'none';
                                });
                        resultsDiv.appendChild(item);
                    });
                }
                resultsDiv.style.display = 'block';
            });
    }, 250);
});

document.addEventListener('click', function(e) {
    if (!searchInput.contains(e.target) && !resultsDiv.contains(e.target)) {
        resultsDiv.style.display = 'none';
    }
});

clearBtn.addEventListener('click', function() {
    searchInput.value = '';
    resultsDiv.style.display = 'none';
    searchInput.focus();
});

ajusteForm.addEventListener('submit', function(e) {
    if (!itemsBody.querySelector('tr')) {
        e.preventDefault();
        alert('Agregá al menos un producto al movimiento.');
    }
});

if (Array.isArray(initialItems) && initialItems.length) {
    initialItems.forEach(addItemRow);
    // Pequeña pausa para que el DOM se actualice
    setTimeout(updateAllRowsStock, 100);
}

// ✦✦✦ Auto-actualización de solicitudes (no toca el formulario) ✦✦✦
function actualizarSolicitudes() {
    fetch('/admin/stock/ajuste')
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const pend = doc.getElementById('solicitudesPendientes');
            const mis = doc.getElementById('misSolicitudes');
            if (pend) {
                document.getElementById('solicitudesPendientes').innerHTML = pend.innerHTML;
            }
            if (mis) {
                document.getElementById('misSolicitudes').innerHTML = mis.innerHTML;
            }
        })
        .catch(function() {});
}

document.getElementById('btnActualizarSolicitudes').addEventListener('click', actualizarSolicitudes);
setInterval(actualizarSolicitudes, 20000);

// Event listeners para actualizar stock al cambiar depósito destino
document.getElementById('iddepohasta').addEventListener('change', updateAllRowsStock);
document.getElementById('iddepodesde').addEventListener('change', updateAllRowsStock);

// Función para actualizar el stock en el depósito destino de una fila
function updateRowStock(tr) {
    const idprodu = tr.dataset.productId;
    const variantSelect = tr.querySelector('select[name="idcodgusto[]"]');
    const idcodgusto = variantSelect ? parseInt(variantSelect.value) : 0;
    const iddepo = document.getElementById('iddepohasta').value;
    const stockBadge = tr.querySelector('.stock-destino');

    if (!idprodu || !iddepo) {
        if (stockBadge) {
            stockBadge.textContent = '—';
            stockBadge.className = 'badge bg-secondary stock-destino';
        }
        return;
    }

    // Mostrar estado de carga
    if (stockBadge) {
        stockBadge.textContent = '⟳';
        stockBadge.className = 'badge bg-info stock-destino';
    }

    const params = new URLSearchParams({
        idprodu: idprodu,
        iddepo: iddepo,
        idcodgusto: variantSelect ? parseInt(variantSelect.value) : 0
    });

    fetch('/admin/stock/ajuste/stock-deposito?' + params.toString())
        .then(r => r.json())
        .then(data => {
            const stock = parseInt(data.stock) || 0;
            if (stockBadge) {
                stockBadge.textContent = stock.toLocaleString('es-AR');
                if (stock <= 0) {
                    stockBadge.className = 'badge bg-danger stock-destino';
                } else if (stock <= 5) {
                    stockBadge.className = 'badge bg-warning text-dark stock-destino';
                } else {
                    stockBadge.className = 'badge bg-success stock-destino';
                }
            }
        })
        .catch(function() {
            if (stockBadge) {
                stockBadge.textContent = '?';
                stockBadge.className = 'badge bg-secondary stock-destino';
            }
        });
}

// Actualizar stock de todas las filas cuando cambia el depósito destino
function updateAllRowsStock() {
    const rows = itemsBody.querySelectorAll('tr');
    rows.forEach(updateRowStock);
}

// ✦✦✦ Auto-actualización de solicitudes (no toca el formulario) ✦✦✦
function actualizarSolicitudes() {
    fetch('/admin/stock/ajuste')
        .then(r => r.text())
        .then(html => {
            const doc = new DOMParser().parseFromString(html, 'text/html');
            const pend = doc.getElementById('solicitudesPendientes');
            const mis = doc.getElementById('misSolicitudes');
            if (pend) {
                document.getElementById('solicitudesPendientes').innerHTML = pend.innerHTML;
            }
            if (mis) {
                document.getElementById('misSolicitudes').innerHTML = mis.innerHTML;
            }
        })
        .catch(function() {});
}

document.getElementById('btnActualizarSolicitudes').addEventListener('click', actualizarSolicitudes);
setInterval(actualizarSolicitudes, 20000);

// Event listeners para actualizar stock al cambiar depósito destino
document.getElementById('iddepohasta').addEventListener('change', updateAllRowsStock);
document.getElementById('iddepodesde').addEventListener('change', updateAllRowsStock);

// Actualizar stock inicial para items pre-cargados
if (Array.isArray(initialItems) && initialItems.length) {
    initialItems.forEach(addItemRow);
    // Pequeña pausa para que el DOM se actualice
    setTimeout(updateAllRowsStock, 100);
}
</script>
