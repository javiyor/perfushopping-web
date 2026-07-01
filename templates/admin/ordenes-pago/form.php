<?php
$orden = $orden ?? null;
$pagos = $pagos ?? [];
$bancos = $bancos ?? [];
$csrfToken = $csrf ?? '';
$formasPago = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque_propio' => 'Cheque propio'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Nueva orden de pago</h4>
        <p class="text-muted small">Pago a proveedor</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/ordenes-pago">Volver</a>
</div>

<form method="post" action="/admin/ordenes-pago/guardar" id="opForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Medios de pago</span>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="addPago()"><i class="bi bi-plus-lg"></i> Agregar</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="pagosTable">
                        <thead>
                            <tr>
                                <th>Forma de pago</th>
                                <th>Monto ($)</th>
                                <th style="width:250px">Datos</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="pagosBody"></tbody>
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
                <div class="card-header bg-white fw-semibold">Detalles</div>
                <div class="card-body">
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Fecha</label>
                            <input class="form-control form-control-sm" name="fecha" type="date" value="<?= date('Y-m-d') ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Venc. cheque</label>
                            <input class="form-control form-control-sm" name="fecha_vencimiento" type="date" />
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Concepto</label>
                        <textarea class="form-control form-control-sm" name="concepto" rows="2" placeholder="Motivo del pago"></textarea>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Resumen</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <span>Total:</span>
                        <span class="fw-bold" id="resumenTotal">$0,00</span>
                    </div>
                </div>
            </div>

            <button class="btn btn-accent w-100" type="submit"><i class="bi bi-check-lg"></i> Crear orden de pago</button>
        </div>
    </div>
</form>

<style>
.suggestion-item { padding:6px 10px; cursor:pointer; font-size:13px; border-bottom:1px solid #eee; background:#fff; }
.suggestion-item:hover { background:#f0f0f0; }
.suggestion-item:last-child { border-radius:0 0 6px 6px; }
</style>

<script>
let rowCounter = 1;

document.addEventListener('DOMContentLoaded', function() {
    addPago();
});

function addPago() {
    const tbody = document.getElementById('pagosBody');
    const row = document.createElement('tr');
    row.className = 'pago-row';
    row.innerHTML = `
        <td>
            <select class="form-select form-select-sm forma-pago" name="forma_pago[]" onchange="toggleChequeData(this)">
                <?php foreach ($formasPago as $v => $l): ?>
                <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($l) ?></option>
                <?php endforeach; ?>
            </select>
        </td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input class="form-control form-control-sm monto-input" name="pago_monto_cents[]" type="number" min="1" step="1" oninput="recalcular()" />
            </div>
        </td>
        <td class="cheque-data" style="display:none">
            <select class="form-select form-select-sm mb-1" name="pago_banco_cuenta_id[]">
                <option value="">Banco</option>
                <?php foreach ($bancos as $b): ?>
                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars((string)($b['banco'] ?? '')) ?></option>
                <?php endforeach; ?>
            </select>
            <input class="form-control form-control-sm mb-1" name="pago_numero_cheque[]" placeholder="N° cheque" />
            <input class="form-control form-control-sm" name="pago_banco_emisor[]" placeholder="Banco emisor" />
        </td>
        <td><button class="btn btn-sm btn-outline-danger" type="button" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(row);
    rowCounter++;
    recalcular();
}

function toggleChequeData(sel) {
    const td = sel.closest('tr').querySelector('.cheque-data');
    td.style.display = sel.value === 'cheque_propio' ? '' : 'none';
    recalcular();
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.pago-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalcular();
}

function recalcular() {
    let total = 0;
    document.querySelectorAll('.monto-input').forEach(inp => {
        total += parseInt(inp.value) || 0;
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
            fetch('/admin/ordenes-pago/buscar-proveedores?q=' + encodeURIComponent(val))
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
