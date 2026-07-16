<?php
$codigo = (string)($codigo ?? '');
$items = $items ?? [];
$csrfToken = $csrf ?? '';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Nuevo presupuesto</h4>
        <p class="text-muted small">Código: <strong><?= htmlspecialchars($codigo) ?></strong></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/presupuestos">Volver</a>
</div>

<form method="post" action="/admin/presupuestos/guardar" id="presupuestoForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Productos</span>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="addProductRow()"><i class="bi bi-plus-lg"></i> Agregar</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="min-width:160px">Producto</th>
                                <th>Variedad</th>
                                <th style="width:70px">Cant.</th>
                                <th style="width:110px">Precio unit.</th>
                                <th style="width:70px">IVA%</th>
                                <th style="width:110px">Total</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                            <tr class="item-row">
                                <td>
                                    <input class="form-control form-control-sm prod-input" name="producto[]" placeholder="Buscar producto..." autocomplete="off" />
                                    <input type="hidden" name="idprodu[]" class="idprodu" />
                                    <div class="prod-suggestions" style="position:relative"></div>
                                </td>
                                <td>
                                    <select class="form-select form-select-sm variedad-select" name="variedad[]">
                                        <option value="">—</option>
                                    </select>
                                    <input type="hidden" name="idcodgusto[]" class="idcodgusto" />
                                </td>
                                <td><input class="form-control form-control-sm qty-input" name="cantidad[]" type="number" value="1" min="1" /></td>
                                <td>
                                    <div class="input-group input-group-sm">
                                        <span class="input-group-text">$</span>
                                        <input class="form-control precio-input" name="precio[]" type="number" value="0" min="0" step="1" />
                                    </div>
                                </td>
                                <td><input class="form-control form-control-sm iva-input" name="iva_rate[]" type="number" value="21" step="0.01" readonly style="background:#f8f9fa" /></td>
                                <td class="text-end line-total">$0</td>
                                <td><button class="btn btn-sm btn-outline-danger" type="button" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="card-footer bg-white text-end">
                    <div class="row">
                        <div class="col-md-4 offset-md-8">
                            <div class="d-flex justify-content-between small">
                                <span>Subtotal:</span>
                                <strong id="subtotalDisplay">$0</strong>
                            </div>
                            <div class="d-flex justify-content-between small">
                                <span>IVA:</span>
                                <strong id="ivaDisplay">$0</strong>
                            </div>
                            <div class="d-flex justify-content-between fw-bold">
                                <span>Total:</span>
                                <strong id="totalDisplay">$0</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Datos del cliente</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Buscar cliente web</label>
                        <input class="form-control form-control-sm" id="clienteSearch" placeholder="Nombre, email o teléfono..." autocomplete="off" />
                        <div id="clienteSuggestions" style="position:relative"></div>
                    </div>
                    <hr class="my-2" />
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small">Nombre / Razón social <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" name="cliente_nombre" id="clienteNombre" required />
                            <input type="hidden" name="cliente_id" id="clienteId" value="0" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">CUIT</label>
                            <input class="form-control form-control-sm" name="cliente_cuit" id="clienteCuit" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Teléfono</label>
                            <input class="form-control form-control-sm" name="cliente_tele" id="clienteTele" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Email</label>
                            <input class="form-control form-control-sm" name="cliente_mail" id="clienteMail" type="email" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Dirección</label>
                            <input class="form-control form-control-sm" name="cliente_direc" id="clienteDirec" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Fechas</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Fecha</label>
                            <input class="form-control form-control-sm" name="fecha" type="date" value="<?= date('Y-m-d') ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Válido hasta</label>
                            <input class="form-control form-control-sm" name="valido_hasta" type="date" value="<?= date('Y-m-d', strtotime('+15 days')) ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Notas</div>
                <div class="card-body">
                    <textarea class="form-control form-control-sm" name="notas" rows="3" placeholder="Condiciones, observaciones..."></textarea>
                </div>
            </div>

            <button class="btn btn-accent w-100" type="submit"><i class="bi bi-check-lg"></i> Crear presupuesto</button>
        </div>
    </div>
</form>

<style>
.prod-suggestions, #clienteSuggestions { position:absolute; z-index:1050; width:100%; }
.suggestion-item { padding:6px 10px; cursor:pointer; font-size:13px; border-bottom:1px solid #eee; background:#fff; }
.suggestion-item:hover { background:#f0f0f0; }
.suggestion-item:last-child { border-radius:0 0 6px 6px; }
</style>

<script>
let productCache = {};
let clientesCache = {};
let itemCounter = 1;

document.addEventListener('DOMContentLoaded', function() {
    addProductRow(); // Start with one row
});

function addProductRow(data) {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `
        <td>
            <input class="form-control form-control-sm prod-input" name="producto[]" placeholder="Buscar producto..." autocomplete="off" />
            <input type="hidden" name="idprodu[]" class="idprodu" value="${data ? (data.idprodu || '') : ''}" />
            <div class="prod-suggestions"></div>
        </td>
        <td>
            <select class="form-select form-select-sm variedad-select" name="variedad[]">
                <option value="">—</option>
            </select>
            <input type="hidden" name="idcodgusto[]" class="idcodgusto" value="" />
        </td>
        <td><input class="form-control form-control-sm qty-input" name="cantidad[]" type="number" value="${data ? (data.qty || 1) : 1}" min="1" /></td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input class="form-control precio-input" name="precio[]" type="number" value="${data ? (data.unit_price_cents || 0) : 0}" min="0" step="1" />
            </div>
        </td>
        <td><input class="form-control form-control-sm iva-input" name="iva_rate[]" type="number" value="21" step="0.01" readonly style="background:#f8f9fa" /></td>
        <td class="text-end line-total">$0</td>
        <td><button class="btn btn-sm btn-outline-danger" type="button" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(row);
    setupRow(row);
    recalcTotals();
    itemCounter++;
}

function setupRow(row) {
    const input = row.querySelector('.prod-input');
    const suggestions = row.querySelector('.prod-suggestions');
    const idprodu = row.querySelector('.idprodu');
    const variedadSelect = row.querySelector('.variedad-select');
    const idcodgusto = row.querySelector('.idcodgusto');
    const precio = row.querySelector('.precio-input');
    const qty = row.querySelector('.qty-input');
    const ivaRate = row.querySelector('.iva-input');

    let timer;
    input.addEventListener('input', function() {
        clearTimeout(timer);
        const val = this.value.trim();
        if (val.length < 2) { suggestions.innerHTML = ''; return; }
        timer = setTimeout(() => searchProducts(val, suggestions, row), 250);
    });

    input.addEventListener('blur', function() {
        setTimeout(() => suggestions.innerHTML = '', 300);
    });

    variedadSelect.addEventListener('change', function() {
        const opt = this.selectedOptions[0];
        if (opt && opt.dataset.gusto) {
            idcodgusto.value = opt.dataset.gusto || '';
            if (opt.dataset.precio) {
                precio.value = opt.dataset.precio;
            }
            if (opt.dataset.iva) {
                ivaRate.value = opt.dataset.iva;
            }
            recalcLine(row);
        }
    });

    [precio, qty].forEach(el => el.addEventListener('input', () => recalcLine(row)));
    [precio, qty].forEach(el => el.addEventListener('change', () => recalcLine(row)));
}

function searchProducts(q, suggestionsContainer, row) {
    fetch('/admin/presupuestos/buscar-productos?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            suggestionsContainer.innerHTML = '';
            if (!data || data.length === 0) {
                suggestionsContainer.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                return;
            }
            data.forEach(p => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                const precio = p.precio ? Math.round(p.precio * 100) : 0;
                div.innerHTML = '<strong>' + esc(p.produ) + '</strong> <span class="text-muted">(' + esc(p.codprodu) + ') $' + precio + '</span>';
                div.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    selectProduct(p, row, suggestionsContainer);
                });
                suggestionsContainer.appendChild(div);
            });
        });
}

function selectProduct(p, row, suggestionsContainer) {
    row.querySelector('.prod-input').value = p.produ || '';
    row.querySelector('.idprodu').value = p.idprodu || '';
    suggestionsContainer.innerHTML = '';

    const ivaRate = p.tiva || 0;
    row.querySelector('.iva-input').value = ivaRate;

    const precioCents = p.precio ? Math.round(parseFloat(p.precio) * 100) : 0;
    row.querySelector('.precio-input').value = precioCents;

    const variedadSelect = row.querySelector('.variedad-select');
    variedadSelect.innerHTML = '<option value="">—</option>';
    row.querySelector('.idcodgusto').value = '';

    if (p.variants && p.variants.length > 0) {
        variedadSelect.disabled = false;
        let selectedIdx = -1;
        p.variants.forEach((v, i) => {
            const opt = document.createElement('option');
            opt.value = v.nomgusto || '';
            opt.textContent = (v.nomgusto || '') + (v.codscan ? ' (' + v.codscan + ')' : '');
            opt.dataset.gusto = v.idcodgusto || '';
            opt.dataset.precio = precioCents;
            opt.dataset.iva = ivaRate;
            variedadSelect.appendChild(opt);
            if (p.matched_variant && String(v.idcodgusto) === String(p.matched_variant.idcodgusto)) {
                selectedIdx = i + 1;
            }
        });
        if (selectedIdx > 0) {
            variedadSelect.selectedIndex = selectedIdx;
            variedadSelect.dispatchEvent(new Event('change'));
        } else if (p.variants.length === 1) {
            variedadSelect.selectedIndex = 1;
            variedadSelect.dispatchEvent(new Event('change'));
        }
    } else {
        variedadSelect.disabled = true;
    }

    recalcLine(row);
    recalcTotals();
}

function recalcLine(row) {
    const precio = parseInt(row.querySelector('.precio-input').value) || 0;
    const qty = parseInt(row.querySelector('.qty-input').value) || 1;
    const total = precio * qty;
    row.querySelector('.line-total').textContent = '$' + total.toLocaleString('es-AR');
    recalcTotals();
}

function recalcTotals() {
    let subtotal = 0, iva = 0, total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const precio = parseInt(row.querySelector('.precio-input').value) || 0;
        const qty = parseInt(row.querySelector('.qty-input').value) || 1;
        const tasa = parseFloat(row.querySelector('.iva-input').value) || 0;
        const lineTotal = precio * qty;
        const lineIva = Math.round(lineTotal * tasa / (100 + tasa));
        subtotal += lineTotal - lineIva;
        iva += lineIva;
        total += lineTotal;
    });
    document.getElementById('subtotalDisplay').textContent = '$' + subtotal.toLocaleString('es-AR');
    document.getElementById('ivaDisplay').textContent = '$' + iva.toLocaleString('es-AR');
    document.getElementById('totalDisplay').textContent = '$' + total.toLocaleString('es-AR');
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalcTotals();
}

// Client search
const clienteInput = document.getElementById('clienteSearch');
const clienteSuggestions = document.getElementById('clienteSuggestions');
let clienteTimer;

clienteInput.addEventListener('input', function() {
    clearTimeout(clienteTimer);
    const val = this.value.trim();
    if (val.length < 2) { clienteSuggestions.innerHTML = ''; return; }
    clienteTimer = setTimeout(() => {
        fetch('/admin/presupuestos/buscar-clientes?q=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                clienteSuggestions.innerHTML = '';
                if (!data || data.length === 0) {
                    clienteSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                    return;
                }
                data.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = '<strong>' + esc(c.name) + '</strong> <span class="text-muted">' + esc(c.email || '') + ' ' + esc(c.phone || '') + '</span>';
                    div.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        document.getElementById('clienteId').value = c.id || 0;
                        document.getElementById('clienteNombre').value = c.name || '';
                        document.getElementById('clienteCuit').value = '';
                        document.getElementById('clienteTele').value = c.phone || '';
                        document.getElementById('clienteMail').value = c.email || '';
                        document.getElementById('clienteDirec').value = (c.address || '') + ', ' + (c.city || '');
                        clienteInput.value = c.name || '';
                        clienteSuggestions.innerHTML = '';
                    });
                    clienteSuggestions.appendChild(div);
                });
            });
    }, 300);
});

clienteInput.addEventListener('blur', function() {
    setTimeout(() => clienteSuggestions.innerHTML = '', 300);
});

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
