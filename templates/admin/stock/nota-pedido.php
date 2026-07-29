<?php
$csrf = $csrf ?? '';
?>
<style>
.np-layout { display:flex; gap:20px; align-items:flex-start; }
.np-left { flex:1; min-width:0; }
.np-right { width:380px; flex-shrink:0; }
.np-section { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.06); padding:16px; margin-bottom:16px; }
.np-section h6 { font-weight:700; margin:0 0 12px; font-size:14px; border-bottom:1px solid #eee; padding-bottom:8px; }
@media (max-width:768px) { .np-layout { flex-direction:column; } .np-right { width:100%; } }
</style>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Nueva nota de pedido</h4>
        <p class="text-muted small">Completá los datos y confirmá los productos a pedir</p>
    </div>
</div>

<form method="post" action="/admin/nota-pedido/guardar" id="npForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
    <input type="hidden" name="productos" id="npProductosJson" value="" />

    <div class="np-layout">
        <div class="np-left">
            <div class="np-section">
                <h6>Productos a pedir</h6>
                <div class="table-responsive">
                    <table class="table table-sm table-admin mb-0" id="npItemsTable">
                        <thead>
                            <tr>
                                <th style="width:30px"></th>
                                <th>Producto</th>
                                <th>Variedad</th>
                                <th>Cód. barra</th>
                                <th>Cód. proveedor</th>
                                <th style="width:80px" class="text-center">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="npItemsBody">
                        </tbody>
                    </table>
                </div>
                <div id="npEmpty" class="text-center text-muted py-4 small">No hay productos cargados.</div>
            </div>
        </div>

        <div class="np-right">
            <div class="np-section">
                <h6>Proveedor</h6>
                <div class="mb-2" style="position:relative">
                    <input class="form-control form-control-sm" id="proveedorSearch" placeholder="Buscar proveedor..." autocomplete="off" />
                    <input type="hidden" name="proveedor_id" id="proveedorId" value="0" />
                    <input type="hidden" name="proveedor_nombre" id="proveedorNombre" value="" />
                    <div id="proveedorSuggestions" style="position:absolute;z-index:1050;width:100%"></div>
                </div>
                <div id="proveedorInfo" class="small text-muted" style="display:none"></div>
            </div>

            <div class="np-section">
                <h6>Transporte</h6>
                <input class="form-control form-control-sm mb-2" name="transporte" placeholder="Empresa / chofer / vehículo" />
                <input class="form-control form-control-sm" name="notas" placeholder="Notas del transporte" />
            </div>

            <div class="np-section">
                <h6>Datos del envío</h6>
                <input class="form-control form-control-sm mb-2" name="envio_direccion" placeholder="Dirección" />
                <div class="row g-1 mb-2">
                    <div class="col"><input class="form-control form-control-sm" name="envio_ciudad" placeholder="Ciudad" /></div>
                    <div class="col"><input class="form-control form-control-sm" name="envio_telefono" placeholder="Teléfono" /></div>
                </div>
            </div>

            <button class="btn btn-accent w-100 py-2 fw-bold" type="submit">
                <i class="bi bi-file-text"></i> Generar nota de pedido
            </button>
            <a class="btn btn-outline-secondary w-100 mt-1" href="/admin/stock">Cancelar</a>
        </div>
    </div>
</form>

<script>
const urlParams = new URLSearchParams(window.location.search);
let pendingItems = [];

try {
    const stored = sessionStorage.getItem('np_items');
    if (stored) pendingItems = JSON.parse(stored);
} catch(e) {}

function renderItems() {
    const tbody = document.getElementById('npItemsBody');
    const empty = document.getElementById('npEmpty');
    tbody.innerHTML = '';
    if (pendingItems.length === 0) {
        empty.style.display = 'block';
        return;
    }
    empty.style.display = 'none';
    pendingItems.forEach((item, idx) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="removeItem(${idx})">&times;</button></td>
            <td class="small">${esc(item.producto || '')}</td>
            <td class="small text-muted">${esc(item.variedad || '—')}</td>
            <td class="small text-muted">${esc(item.codscan || '')}</td>
            <td class="small text-muted">${esc(item.codprodup || '')}</td>
            <td><input type="number" class="form-control form-control-sm text-center" value="${item.qty || 1}" min="1" onchange="pendingItems[${idx}].qty = Math.max(1, parseInt(this.value) || 1)" /></td>
        `;
        tbody.appendChild(tr);
    });
}
renderItems();

function removeItem(idx) {
    pendingItems.splice(idx, 1);
    renderItems();
}

document.getElementById('npForm').addEventListener('submit', function(e) {
    const validItems = pendingItems.filter(it => (it.qty || 0) > 0);
    if (validItems.length === 0) {
        e.preventDefault();
        alert('Agregá al menos un producto con cantidad > 0.');
        return;
    }
    document.getElementById('npProductosJson').value = JSON.stringify(validItems);
    sessionStorage.removeItem('np_items');
});

// ── Supplier search ──
const provInput = document.getElementById('proveedorSearch');
const provSuggestions = document.getElementById('proveedorSuggestions');
let provTimer;

provInput.addEventListener('input', function() {
    clearTimeout(provTimer);
    const val = this.value.trim();
    if (val.length < 2) { provSuggestions.innerHTML = ''; return; }
    provTimer = setTimeout(() => {
        fetch('/admin/nota-pedido/buscar-proveedores?q=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                provSuggestions.innerHTML = '';
                if (!data || data.length === 0) {
                    provSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                    return;
                }
                data.forEach(pv => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = '<strong>' + esc(pv.razon) + '</strong> <span class="text-muted">' + esc(pv.cuit || '') + '</span>';
                    div.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        document.getElementById('proveedorId').value = pv.codprove || 0;
                        document.getElementById('proveedorNombre').value = pv.razon || '';
                        provInput.value = pv.razon || '';
                        const info = document.getElementById('proveedorInfo');
                        info.style.display = 'block';
                        info.innerHTML = 'CUIT: ' + esc(pv.cuit || '—') + ' | Tel: ' + esc(pv.telefono || '—');
                        provSuggestions.innerHTML = '';
                    });
                    provSuggestions.appendChild(div);
                });
            });
    }, 250);
});
provInput.addEventListener('blur', function() {
    setTimeout(() => provSuggestions.innerHTML = '', 300);
});

function esc(s) {
    if (!s) return '';
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>
