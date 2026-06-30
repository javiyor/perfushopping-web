<?php
$csrfToken = $csrf ?? '';
$formaPagoLabels = [
    'efectivo'=>'Efectivo', 'transferencia'=>'Transferencia bancaria', 'tarjeta_credito'=>'Tarjeta de crédito',
    'tarjeta_debito'=>'Tarjeta de débito', 'mercadopago'=>'Mercado Pago', 'cuenta_corriente'=>'Cuenta corriente',
];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Nuevo recibo</h4>
        <p class="text-muted small">Registrar pago de cliente</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/recibos">Volver</a>
</div>

<form method="post" action="/admin/recibos/guardar" id="reciboForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="cliente_id" id="clienteId" value="0" />
    <input type="hidden" name="cliente_cuit" id="clienteCuit" value="" />
    <input type="hidden" name="cliente_direc" id="clienteDirec" value="" />
    <input type="hidden" name="cliente_condicion_iva" id="clienteCondIva" value="consumidor_final" />

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Cliente</span>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Buscar cliente</label>
                        <input class="form-control form-control-sm" id="clienteSearch" placeholder="Nombre, email o CUIT..." autocomplete="off" />
                        <div id="clienteSuggestions" style="position:relative"></div>
                    </div>
                    <hr class="my-2" />
                    <div class="row g-2">
                        <div class="col-12">
                            <label class="form-label small">Nombre / Razón social <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" name="cliente_nombre" id="clienteNombre" required />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Facturas a cancelar</span>
                    <span id="pendientesCount" class="badge bg-warning text-dark">0</span>
                </div>
                <div class="card-body p-0" id="facturasContainer">
                    <div class="text-muted text-center py-4 small">Seleccioná un cliente para ver sus facturas pendientes</div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Pago</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Forma de pago</label>
                        <select class="form-select form-select-sm" name="forma_pago" id="formaPago">
                            <?php foreach ($formaPagoLabels as $v => $l): ?>
                                <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Monto total <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_cents" id="montoCents" type="number" value="0" min="1" required />
                        </div>
                    </div>
                    <div>
                        <label class="form-label small">Concepto</label>
                        <input class="form-control form-control-sm" name="concepto" id="concepto" placeholder="Ej: Pago facturas" />
                    </div>
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
                    <textarea class="form-control form-control-sm" name="notas" rows="2" placeholder="Observaciones..."></textarea>
                </div>
            </div>

            <button class="btn btn-accent w-100 py-2 fw-bold" type="submit"><i class="bi bi-receipt"></i> Emitir recibo</button>
        </div>
    </div>
</form>

<style>
#clienteSuggestions { position:absolute; z-index:1050; width:100%; }
.suggestion-item { padding:6px 10px; cursor:pointer; font-size:13px; border-bottom:1px solid #eee; background:#fff; }
.suggestion-item:hover { background:#f0f0f0; }
.suggestion-item:last-child { border-radius:0 0 6px 6px; }
.factura-pago-row { display:flex; align-items:center; gap:8px; padding:8px 14px; border-bottom:1px solid #f0f0f0; font-size:13px; }
.factura-pago-row:hover { background:#fafafa; }
.factura-pago-row input[type="checkbox"] { width:16px; height:16px; cursor:pointer; }
.factura-pago-row .fp-codigo { font-weight:600; min-width:140px; }
.factura-pago-row .fp-total { margin-left:auto; font-weight:600; }
.factura-pago-row .fp-saldo { margin-left:12px; color:#6c757d; }
.factura-pago-row .fp-pago { width:120px; }
.factura-pago-row .fp-pago input { width:100%; text-align:right; padding:4px 6px; border:1px solid #dee2e6; border-radius:6px; }
</style>

<script>
let facturasCache = [];

// ── Client search ──
const cliInput = document.getElementById('clienteSearch');
const cliSuggestions = document.getElementById('clienteSuggestions');
let cliTimer;

cliInput.addEventListener('input', function() {
    clearTimeout(cliTimer);
    const val = this.value.trim();
    if (val.length < 2) { cliSuggestions.innerHTML = ''; return; }
    cliTimer = setTimeout(() => {
        fetch('/admin/recibos/buscar-clientes?q=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                cliSuggestions.innerHTML = '';
                if (!data || data.length === 0) {
                    cliSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                    return;
                }
                data.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    div.innerHTML = '<strong>' + esc(c.name) + '</strong> ' + esc(c.cuit || '') + ' <span class="text-muted">' + esc(c.email || '') + '</span>';
                    div.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectCliente(c);
                    });
                    cliSuggestions.appendChild(div);
                });
            });
    }, 250);
});

cliInput.addEventListener('blur', function() {
    setTimeout(() => cliSuggestions.innerHTML = '', 300);
});

function selectCliente(c) {
    document.getElementById('clienteId').value = c.id || 0;
    document.getElementById('clienteNombre').value = c.name || '';
    document.getElementById('clienteCuit').value = c.cuit || '';
    document.getElementById('clienteDirec').value = c.direc || c.address || '';
    document.getElementById('clienteCondIva').value = c.condicion_iva || 'consumidor_final';
    cliInput.value = c.name || '';
    cliSuggestions.innerHTML = '';
    loadFacturasPendientes(c.id || 0);
}

function loadFacturasPendientes(clienteId) {
    const container = document.getElementById('facturasContainer');
    const count = document.getElementById('pendientesCount');

    if (clienteId <= 0) {
        container.innerHTML = '<div class="text-muted text-center py-4 small">Seleccioná un cliente para ver sus facturas pendientes</div>';
        count.textContent = '0';
        return;
    }

    fetch('/admin/recibos/buscar-facturas?cliente_id=' + clienteId)
        .then(r => r.json())
        .then(data => {
            facturasCache = data || [];
            count.textContent = facturasCache.length;

            if (facturasCache.length === 0) {
                container.innerHTML = '<div class="text-muted text-center py-4 small">Sin facturas pendientes</div>';
                return;
            }

            let html = facturasCache.map((f, idx) => {
                const saldo = f.total_cents - (f.pagado_cents || 0);
                const tipoLabel = {'FACT-A':'FA','FACT-B':'FB','FACT-C':'FC','NC':'NC','ND':'ND'}[f.tipo_comprobante] || f.tipo_comprobante;
                return `
                    <div class="factura-pago-row">
                        <input type="checkbox" class="fp-check" data-idx="${idx}" onchange="toggleFactura(${idx})" />
                        <input type="hidden" name="factura_id[]" class="fp-fid" value="${f.id}" disabled />
                        <input type="hidden" name="pago_monto[]" class="fp-monto" value="0" disabled />
                        <div class="fp-codigo"><span class="badge bg-secondary me-1">${tipoLabel}</span>${esc(f.codigo)}</div>
                        <div class="fp-total">$${(saldo).toLocaleString('es-AR')}</div>
                        <div class="fp-pago">
                            <input type="number" class="fp-monto-input" value="${saldo}" min="0" max="${saldo}" disabled onchange="updateMontoFactura(${idx}, this.value)" />
                        </div>
                    </div>
                `;
            }).join('');
            container.innerHTML = html;
            recalcTotal();
        });
}

function toggleFactura(idx) {
    const row = document.querySelectorAll('.factura-pago-row')[idx];
    const checked = row.querySelector('.fp-check').checked;
    row.querySelector('.fp-fid').disabled = !checked;
    row.querySelector('.fp-monto').disabled = !checked;
    row.querySelector('.fp-monto-input').disabled = !checked;
    if (checked) recalcTotal();
    else { recalcTotal(); }
}

function updateMontoFactura(idx, val) {
    const row = document.querySelectorAll('.factura-pago-row')[idx];
    const monto = Math.max(0, parseInt(val) || 0);
    row.querySelector('.fp-monto').value = monto;
    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    let conceptoParts = [];
    document.querySelectorAll('.factura-pago-row').forEach((row, idx) => {
        const checked = row.querySelector('.fp-check').checked;
        if (checked) {
            const monto = parseInt(row.querySelector('.fp-monto-input').value) || 0;
            total += monto;
            const codigo = facturasCache[idx]?.codigo || '';
            conceptoParts.push(codigo);
        }
    });
    document.getElementById('montoCents').value = total;
    if (conceptoParts.length > 0) {
        document.getElementById('concepto').value = 'Pago: ' + conceptoParts.join(', ');
    }
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
