<?php
$depositos = $depositos ?? [];
$producto = $producto ?? null;
$variantes = $variantes ?? [];
$initialAjusteItems = $initialAjusteItems ?? [];
$solicitudesPendientes = $solicitudesPendientes ?? [];
$misSolicitudes = $misSolicitudes ?? [];
$esSuperadmin = $esSuperadmin ?? false;
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
                            <input class="form-control form-control-sm" id="productoSearch" placeholder="Buscar por nombre o código y tocar para agregar" autocomplete="off" />
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
                            <select class="form-select form-select-sm" name="iddepodesde">
                                <option value="">Ninguno (solo ingreso)</option>
                                <?php foreach ($depositos as $d): ?>
                                    <option value="<?= (int)$d['iddepo'] ?>"><?= htmlspecialchars($d['nomdepo'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Depósito hasta <span class="text-muted">(suma)</span></label>
                            <select class="form-select form-select-sm" name="iddepohasta">
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
        <div class="card shadow-sm mb-3">
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

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Mis solicitudes recientes</div>
            <div class="card-body small p-0">
                <?php if (!$misSolicitudes): ?>
                    <div class="p-3 text-muted">Todavía no tenés solicitudes.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($misSolicitudes as $s): ?>
                            <?php
                                $status = (string)($s['status'] ?? 'pendiente');
                                $statusClass = $status === 'aprobada' ? 'success' : ($status === 'rechazada' ? 'danger' : ($status === 'procesando' ? 'warning' : 'secondary'));
                                $depoDesdeNombre = mb_strtolower(trim((string)($s['depo_desde_nombre'] ?? '')));
                                $depoDesdeNombre = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $depoDesdeNombre);
                                $esDepositoPolitica = in_array($depoDesdeNombre, ['irigoyen', 'alvear', '9 de julio 1610', '9 de julio'], true);
                                $warningPolitica = $status === 'pendiente'
                                    && (int)($s['depo_desde_marca'] ?? 0) === 2
                                    && (int)($s['depo_hasta_marca'] ?? 0) !== 2
                                    && $esDepositoPolitica;
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
                    <li>Si el egreso sale de <strong>Irigoyen, Alvear o 9 de Julio 1610</strong> hacia un depósito con marca distinta de 2 y no sos superadmin, se enviará a autorización</li>
                    <li>Si el producto tiene variantes, podés ajustar una específica o dejar "Todas" para ajustar el producto base</li>
                    <li>El motivo es obligatorio para mantener trazabilidad</li>
                </ul>
                <hr />
                <p class="text-muted mb-0">Los ajustes quedan registrados en <code>stockcab</code>/<code>stockdet</code> y se actualizan las tablas <code>stock</code>, <code>producto.stocact</code> y <code>gustos.stockact</code> automáticamente.</p>
            </div>
        </div>
    </div>
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
        html += '<option value="' + (v.idcodgusto || 0) + '">' + escHtml(v.nomgusto || '') + '</option>';
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
}
</script>
