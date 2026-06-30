<?php
$remito = $remito ?? null;
$tipo = (string)($tipo ?? 'salida');
$items = $items ?? [];
$presupuesto = $presupuesto ?? null;
$csrfToken = $csrf ?? '';
$isEntrada = $tipo === 'entrada';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= $isEntrada ? 'Nuevo remito de entrada' : 'Nuevo remito de salida' ?></h4>
        <p class="text-muted small"><?= $isEntrada ? 'Ingreso de mercadería de proveedor' : 'Salida de mercadería a cliente' ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/remitos">Volver</a>
</div>

<?php if ($presupuesto): ?>
<div class="alert alert-info small py-2 mb-3">
    <i class="bi bi-info-circle"></i> Creando remito desde presupuesto <strong><?= htmlspecialchars($presupuesto['codigo'] ?? '') ?></strong>
    — <?= htmlspecialchars($presupuesto['cliente_nombre'] ?? '') ?>
</div>
<?php endif; ?>

<form method="post" action="/admin/remitos/guardar" id="remitoForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>" />
    <input type="hidden" name="presupuesto_id" value="<?= $presupuesto ? (int)($presupuesto['id'] ?? 0) : 0 ?>" />

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
                                <th style="width:80px">Cantidad</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody">
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <?php if ($isEntrada): ?>
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
            <?php else: ?>
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Cliente</div>
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
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Presupuesto relacionado</span>
                    <?php if (!$presupuesto): ?>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="document.getElementById('presupuestoSearchWrap').style.display='block'"><i class="bi bi-link"></i></button>
                    <?php endif; ?>
                </div>
                <div class="card-body" id="presupuestoSearchWrap" style="<?= $presupuesto ? 'display:block' : 'display:none' ?>">
                    <?php if ($presupuesto): ?>
                    <p class="small mb-0">
                        <strong><?= htmlspecialchars($presupuesto['codigo'] ?? '') ?></strong><br />
                        <span class="text-muted"><?= htmlspecialchars($presupuesto['cliente_nombre'] ?? '') ?></span>
                    </p>
                    <?php else: ?>
                    <input class="form-control form-control-sm" id="presupuestoSearch" placeholder="Buscar presupuesto aprobado..." autocomplete="off" />
                    <div id="presupuestoSuggestions" style="position:relative"></div>
                    <input type="hidden" name="presupuesto_id" id="presupuestoId" value="0" />
                    <?php endif; ?>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Fecha</div>
                <div class="card-body">
                    <input class="form-control form-control-sm" name="fecha" type="date" value="<?= date('Y-m-d') ?>" />
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Notas</div>
                <div class="card-body">
                    <textarea class="form-control form-control-sm" name="notas" rows="3" placeholder="Observaciones..."></textarea>
                </div>
            </div>

            <button class="btn btn-accent w-100" type="submit"><i class="bi bi-check-lg"></i> Crear remito</button>
        </div>
    </div>
</form>

<style>
.prod-suggestions, #clienteSuggestions, #proveedorSuggestions, #presupuestoSuggestions { position:absolute; z-index:1050; width:100%; }
.suggestion-item { padding:6px 10px; cursor:pointer; font-size:13px; border-bottom:1px solid #eee; background:#fff; }
.suggestion-item:hover { background:#f0f0f0; }
.suggestion-item:last-child { border-radius:0 0 6px 6px; }
</style>

<script>
let itemCounter = 1;

document.addEventListener('DOMContentLoaded', function() {
    <?php if ($presupuesto && isset($presupuesto['items'])): ?>
    <?php foreach ($presupuesto['items'] as $it): ?>
    addProductRow({
        producto: '<?= htmlspecialchars($it['producto'] ?? '', ENT_QUOTES) ?>',
        idprodu: '<?= (int)($it['idprodu'] ?? 0) ?>',
        idcodgusto: '<?= (int)($it['idcodgusto'] ?? 0) ?>',
        variedad: '<?= htmlspecialchars($it['variedad'] ?? '', ENT_QUOTES) ?>',
        qty: <?= (int)($it['qty'] ?? 1) ?>
    });
    <?php endforeach; ?>
    <?php else: ?>
    addProductRow();
    <?php endif; ?>
});

function addProductRow(data) {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    row.innerHTML = `
        <td>
            <input class="form-control form-control-sm prod-input" name="producto[]" placeholder="Buscar producto..." autocomplete="off" value="${data ? esc(data.producto || '') : ''}" />
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
        <td><button class="btn btn-sm btn-outline-danger" type="button" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(row);
    setupRow(row);
    itemCounter++;
}

function setupRow(row) {
    const input = row.querySelector('.prod-input');
    const suggestions = row.querySelector('.prod-suggestions');
    const idprodu = row.querySelector('.idprodu');
    const variedadSelect = row.querySelector('.variedad-select');
    const idcodgusto = row.querySelector('.idcodgusto');

    if (idcodgusto.value) {
        const vals = variedadSelect.querySelectorAll('option');
        for (let i = 0; i < vals.length; i++) {
            if (vals[i].dataset.gusto === idcodgusto.value) {
                vals[i].selected = true;
                break;
            }
        }
    }

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
        }
    });
}

function searchProducts(q, suggestionsContainer, row) {
    fetch('/admin/remitos/buscar-productos?q=' + encodeURIComponent(q))
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
                div.innerHTML = '<strong>' + esc(p.produ) + '</strong> <span class="text-muted">(' + esc(p.codprodu) + ')</span>';
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

    const variedadSelect = row.querySelector('.variedad-select');
    variedadSelect.innerHTML = '<option value="">—</option>';
    row.querySelector('.idcodgusto').value = '';

    if (p.variants && p.variants.length > 0) {
        variedadSelect.disabled = false;
        p.variants.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.nomgusto || '';
            opt.textContent = (v.nomgusto || '') + (v.codscan ? ' (' + v.codscan + ')' : '');
            opt.dataset.gusto = v.idcodgusto || '';
            variedadSelect.appendChild(opt);
        });
        if (p.variants.length === 1) {
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
}

// Client search (salida)
const clienteInput = document.getElementById('clienteSearch');
const clienteSuggestions = document.getElementById('clienteSuggestions');
if (clienteInput) {
    let clienteTimer;
    clienteInput.addEventListener('input', function() {
        clearTimeout(clienteTimer);
        const val = this.value.trim();
        if (val.length < 2) { clienteSuggestions.innerHTML = ''; return; }
        clienteTimer = setTimeout(() => {
            fetch('/admin/remitos/buscar-clientes?q=' + encodeURIComponent(val))
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
}

// Proveedor search (entrada)
const proveedorInput = document.getElementById('proveedorSearch');
const proveedorSuggestions = document.getElementById('proveedorSuggestions');
if (proveedorInput) {
    let provTimer;
    proveedorInput.addEventListener('input', function() {
        clearTimeout(provTimer);
        const val = this.value.trim();
        if (val.length < 2) { proveedorSuggestions.innerHTML = ''; return; }
        provTimer = setTimeout(() => {
            fetch('/admin/remitos/buscar-proveedores?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    proveedorSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        proveedorSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
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
                            proveedorInput.value = p.razon || '';
                            proveedorSuggestions.innerHTML = '';
                        });
                        proveedorSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    proveedorInput.addEventListener('blur', function() {
        setTimeout(() => proveedorSuggestions.innerHTML = '', 300);
    });
}

// Presupuesto search
const presupuestoInput = document.getElementById('presupuestoSearch');
const presupuestoSuggestions = document.getElementById('presupuestoSuggestions');
if (presupuestoInput) {
    let presTimer;
    presupuestoInput.addEventListener('input', function() {
        clearTimeout(presTimer);
        const val = this.value.trim();
        if (val.length < 2) { presupuestoSuggestions.innerHTML = ''; return; }
        presTimer = setTimeout(() => {
            fetch('/admin/remitos/buscar-presupuestos?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    presupuestoSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        presupuestoSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.innerHTML = '<strong>' + esc(p.codigo) + '</strong> <span class="text-muted">' + esc(p.cliente_nombre || '') + ' - $' + (p.total_cents ? Math.round(p.total_cents).toLocaleString('es-AR') : '0') + '</span>';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            window.location.href = '/admin/remitos/nuevo?tipo=salida&presupuesto_id=' + p.id;
                        });
                        presupuestoSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    presupuestoInput.addEventListener('blur', function() {
        setTimeout(() => presupuestoSuggestions.innerHTML = '', 300);
    });
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
