<?php
$orden = $orden ?? null;
$items = $items ?? [];
$csrfToken = $csrf ?? '';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Nueva orden de compra</h4>
        <p class="text-muted small">Pedido a proveedor</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/ordenes-compra">Volver</a>
</div>

<form method="post" action="/admin/ordenes-compra/guardar" id="ocForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Productos</span>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="addRow()"><i class="bi bi-plus-lg"></i> Agregar</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="min-width:140px">Producto</th>
                                <th>Variedad</th>
                                <th style="width:70px">Cant.</th>
                                <th style="width:100px">Precio unit.</th>
                                <th style="width:100px">Subtotal</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Proveedor</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Buscar proveedor</label>
                        <input class="form-control form-control-sm" id="proveedorSearch" placeholder="Nombre, código o CUIT..." autocomplete="off" />
                        <div id="proveedorSuggestions" style="position:relative"></div>
                    </div>
                    <hr class="my-2" />
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small">Razón social <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" name="proveedor_nombre" id="proveedorNombre" required />
                            <input type="hidden" name="proveedor_id" id="proveedorId" value="0" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Fechas</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Fecha pedido</label>
                            <input class="form-control form-control-sm" name="fecha" type="date" value="<?= date('Y-m-d') ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Fecha estimada recepción</label>
                            <input class="form-control form-control-sm" name="fecha_estimada" type="date" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Notas</div>
                <div class="card-body">
                    <textarea class="form-control form-control-sm" name="notas" rows="3" placeholder="Condiciones de pago, observaciones..."></textarea>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Resumen</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span>Total estimado:</span>
                        <span class="fw-bold" id="resumenTotal">$0,00</span>
                    </div>
                </div>
            </div>

            <button class="btn btn-accent w-100" type="submit"><i class="bi bi-check-lg"></i> Crear orden</button>
        </div>
    </div>
</form>

<style>
.prod-suggestions { position:absolute; z-index:1050; width:100%; }
.suggestion-item { padding:6px 10px; cursor:pointer; font-size:13px; border-bottom:1px solid #eee; background:#fff; }
.suggestion-item:hover { background:#f0f0f0; }
.suggestion-item:last-child { border-radius:0 0 6px 6px; }
</style>

<script>
let rowCounter = 1;

document.addEventListener('DOMContentLoaded', function() {
    addRow();
});

function addRow(data) {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    const price = data ? (data.precompCents || 0) : 0;
    row.innerHTML = `
        <td>
            <input class="form-control form-control-sm prod-input" name="producto[]" placeholder="Buscar..." autocomplete="off" value="${data ? esc(data.producto || '') : ''}" />
            <input type="hidden" name="idprodu[]" class="idprodu" value="${data ? (data.idprodu || '') : ''}" />
            <div class="prod-suggestions"></div>
        </td>
        <td>
            <select class="form-select form-select-sm variedad-select" name="variedad[]">
                <option value="">—</option>
            </select>
            <input type="hidden" name="idcodgusto[]" class="idcodgusto" value="${data ? (data.idcodgusto || '') : ''}" />
        </td>
        <td><input class="form-control form-control-sm qty-input" name="cantidad[]" type="number" value="${data ? (data.qty || 1) : 1}" min="1" /></td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input class="form-control form-control-sm price-input" name="precio_cents[]" type="number" value="${price}" min="0" step="1" />
            </div>
        </td>
        <td class="text-end line-total pt-3 small">$0,00</td>
        <td><button class="btn btn-sm btn-outline-danger" type="button" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(row);
    setupRow(row);
    rowCounter++;
    recalcular();
}

function setupRow(row) {
    const input = row.querySelector('.prod-input');
    const suggestions = row.querySelector('.prod-suggestions');
    const idprodu = row.querySelector('.idprodu');
    const variedadSelect = row.querySelector('.variedad-select');
    const idcodgusto = row.querySelector('.idcodgusto');
    const qtyInput = row.querySelector('.qty-input');
    const priceInput = row.querySelector('.price-input');

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
        idcodgusto.value = opt && opt.dataset.gusto ? opt.dataset.gusto : '';
    });

    qtyInput.addEventListener('input', recalcular);
    priceInput.addEventListener('input', recalcular);
}

function searchProducts(q, suggestionsContainer, row) {
    fetch('/admin/ordenes-compra/buscar-productos?q=' + encodeURIComponent(q))
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
                const costStr = p.precomp ? '$' + Number(p.precomp).toLocaleString('es-AR', {minimumFractionDigits:2}) : '';
                div.innerHTML = '<strong>' + esc(p.produ) + '</strong> <span class="text-muted">(' + esc(p.codprodu) + ') ' + costStr + '</span>';
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

    // Set cost price (precomp is decimal, convert to cents)
    const precomp = parseFloat(p.precomp || 0);
    row.querySelector('.price-input').value = Math.round(precomp * 100);
    recalcular();

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
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalcular();
}

function recalcular() {
    let total = 0;
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseInt(row.querySelector('.qty-input').value) || 0;
        const price = parseInt(row.querySelector('.price-input').value) || 0;
        const line = qty * price;
        total += line;
        row.querySelector('.line-total').textContent = '$' + (line / 100).toLocaleString('es-AR', {minimumFractionDigits:2});
    });
    document.getElementById('resumenTotal').textContent = '$' + (total / 100).toLocaleString('es-AR', {minimumFractionDigits:2});
}

// Proveedor search
const provInput = document.getElementById('proveedorSearch');
const provSuggestions = document.getElementById('proveedorSuggestions');
if (provInput) {
    let provTimer;
    provInput.addEventListener('input', function() {
        clearTimeout(provTimer);
        const val = this.value.trim();
        if (val.length < 2) { provSuggestions.innerHTML = ''; return; }
        provTimer = setTimeout(() => {
            fetch('/admin/ordenes-compra/buscar-proveedores?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    provSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        provSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.innerHTML = '<strong>' + esc(p.razon) + '</strong> <span class="text-muted">(' + esc(p.codprove || '') + ') ' + esc(p.cuit || '') + '</span>';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            document.getElementById('proveedorId').value = p.idprovee || 0;
                            document.getElementById('proveedorNombre').value = p.razon || '';
                            provInput.value = p.razon || '';
                            provSuggestions.innerHTML = '';
                        });
                        provSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    provInput.addEventListener('blur', function() {
        setTimeout(() => provSuggestions.innerHTML = '', 300);
    });
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
