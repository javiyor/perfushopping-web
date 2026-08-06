<?php
$remitoId = (int)($remitoId ?? 0);
$remitoItems = $remitoItems ?? [];
$presupuestoId = (int)($presupuestoId ?? 0);
$presupuestoItems = $presupuestoItems ?? [];
$csrfToken = $csrf ?? '';
$bancos = $bancos ?? [];
$plazos = $plazos ?? [];
?>
<style>
.pos-layout { display:flex; gap:20px; align-items:flex-start; }
.pos-left { flex:1; min-width:0; }
.pos-right { width:400px; flex-shrink:0; position:sticky; top:80px; }

.pos-search-box { position:relative; margin-bottom:16px; }
.pos-search-box input {
    width:100%; padding:8px 12px; font-size:15px; border:2px solid #dee2e6; border-radius:8px;
    outline:none; transition:border-color .15s;
}
.pos-search-box input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(216,178,90,.15); }
.pos-search-box input::placeholder { color:#adb5bd; }

.pos-results {
    position:absolute; z-index:1060; top:100%; left:0; right:0; background:#fff;
    border:1px solid #dee2e6; border-radius:0 0 10px 10px; max-height:320px; overflow-y:auto;
    box-shadow:0 8px 24px rgba(0,0,0,.12);
}
.pos-result-item {
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 14px; cursor:pointer; border-bottom:1px solid #f0f0f0; transition:background .08s;
}
.pos-result-item:hover { background:var(--accent); color:#1a1d23; }
.pos-result-item:last-child { border-bottom:none; }
.pos-result-item .prod-name { font-weight:600; font-size:14px; }
.pos-result-item .prod-code { font-size:12px; color:#6c757d; }
.pos-result-item .prod-price { font-weight:700; font-size:15px; }

.pos-cart { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; }
.pos-cart-header {
    padding:14px 16px; background:#f8f9fa; font-weight:700; font-size:15px;
    border-bottom:1px solid #e9ecef; display:flex; justify-content:space-between;
}
.pos-cart-items { max-height:400px; overflow-y:auto; }
.pos-cart-item {
    display:flex; align-items:center; gap:10px; padding:10px 14px;
    border-bottom:1px solid #f0f0f0; font-size:14px; transition:background .1s;
}
.pos-cart-item:hover { background:#fcfcfc; }
.pos-cart-item .ci-name { flex:1; min-width:0; }
.pos-cart-item .ci-name .ci-prod { font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pos-cart-item .ci-name .ci-var { font-size:12px; color:#6c757d; }
.pos-cart-item .ci-qty { width:60px; }
.pos-cart-item .ci-qty input { width:100%; text-align:center; padding:4px; border:1px solid #dee2e6; border-radius:6px; font-weight:600; }
.pos-cart-item .ci-price { width:100px; text-align:right; font-weight:600; }
.pos-cart-item .ci-total { width:100px; text-align:right; font-weight:700; }
.pos-cart-item .ci-del { width:30px; text-align:center; color:#dc3545; cursor:pointer; font-size:18px; opacity:.5; }
.pos-cart-item .ci-del:hover { opacity:1; }

.pos-totals { padding:14px 16px; border-top:2px solid #e9ecef; }
.pos-totals .pt-row { display:flex; justify-content:space-between; padding:2px 0; font-size:14px; }
.pos-totals .pt-row.pt-total { font-size:22px; font-weight:800; border-top:2px solid #1a1d23; margin-top:6px; padding-top:8px; }

.pos-toolbar { display:flex; gap:10px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.pos-toolbar select, .pos-toolbar .form-control-sm { font-size:14px; }
.pos-cliente { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.pos-cliente input { min-width:180px; }

@media (max-width: 768px) {
    .pos-layout { flex-direction:column; }
    .pos-right { width:100%; position:static; }
    .pos-search-box input { font-size:16px; padding:12px; }
    .pos-cart-item { gap:6px; padding:10px 10px; font-size:13px; }
    .pos-cart-item .ci-price { width:70px; }
    .pos-cart-item .ci-total { width:70px; }
    .pos-cart-item .ci-qty { width:50px; }
    .pos-totals .pt-row.pt-total { font-size:18px; }
    .pos-toolbar { flex-direction:column; align-items:stretch; }
    .pos-toolbar select { width:100% !important; }
    .pos-cliente { flex-direction:column; align-items:stretch; }
    .pos-cliente input { width:100% !important; min-width:0; }
    .pos-cliente .btn { width:100%; }
    #clienteSection { margin-bottom:8px !important; }
    #clienteSection input { width:100% !important; }
}
@media (max-width: 480px) {
    .pos-cart-item { flex-wrap:wrap; gap:4px; }
    .pos-cart-item .ci-name { width:100%; }
    .pos-cart-item .ci-qty { width:40px; }
    .pos-cart-item .ci-price { width:auto; }
    .pos-cart-item .ci-total { width:auto; }
}
</style>

<div class="d-flex justify-content-between align-items-start mb-2">
    <div>
        <h4 class="fw-bold mb-0">Nueva factura</h4>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/facturas/comprobantes"><i class="bi bi-list-ul"></i> Comprobantes emitidos</a>
</div>

<div class="pos-toolbar" style="margin-bottom:8px">
    <select class="form-select form-select-sm" id="tipoComprobante" style="width:auto">
        <option value="FACT-B">Factura B</option>
        <option value="FACT-A">Factura A</option>
        <option value="FACT-C">Factura C</option>
        <option value="NC">Nota de Crédito</option>
        <option value="ND">Nota de Débito</option>
    </select>

    <?php $vendedores = $vendedores ?? []; if ($vendedores): ?>
    <div class="pos-cliente">
        <span class="text-muted small">Vendedor:</span>
        <select class="form-select form-select-sm" id="vendedorId" style="width:auto">
            <option value="0">— Seleccionar —</option>
            <?php foreach ($vendedores as $v): ?>
            <option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['nombre'] ?? $v['username'] ?? '') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if ($remitoId > 0): ?>
    <span class="badge bg-info fs-6">Remito cargado</span>
    <input type="hidden" id="remitoId" value="<?= $remitoId ?>" />
    <?php else: ?>
    <input type="hidden" id="remitoId" value="0" />
    <button class="btn btn-sm btn-outline-primary" type="button" onclick="document.getElementById('remitoSearchWrap').style.display='block'">
        <i class="bi bi-link"></i> Desde remito
    </button>
    <div id="remitoSearchWrap" style="display:none;position:relative">
        <input class="form-control form-control-sm" id="remitoSearch" placeholder="Buscar remito completado..." autocomplete="off" style="width:250px" />
        <div id="remitoSuggestions" style="position:absolute;z-index:1050;width:100%"></div>
    </div>
    <?php endif; ?>

    <?php if ($presupuestoId > 0): ?>
    <span class="badge bg-warning fs-6">Presupuesto cargado</span>
    <input type="hidden" id="presupuestoId" value="<?= $presupuestoId ?>" />
    <?php else: ?>
    <input type="hidden" id="presupuestoId" value="0" />
    <button class="btn btn-sm btn-outline-warning" type="button" onclick="document.getElementById('presupuestoSearchWrap').style.display='block'">
        <i class="bi bi-file-text"></i> Desde presupuesto
    </button>
    <div id="presupuestoSearchWrap" style="display:none;position:relative">
        <input class="form-control form-control-sm" id="presupuestoSearch" placeholder="Buscar presupuesto aprobado..." autocomplete="off" style="width:250px" />
        <div id="presupuestoSuggestions" style="position:absolute;z-index:1050;width:100%"></div>
    </div>
    <?php endif; ?>
</div>

<div class="pos-cliente mb-2" id="clienteSection">
    <span class="text-muted small">Cliente:</span>
    <input class="form-control form-control-sm" id="clienteSearch" placeholder="Buscar o CF" autocomplete="off" style="width:200px" />
    <input type="hidden" id="clienteId" value="0" />
    <input type="hidden" id="clienteErpId" value="0" />
    <span id="clienteNombre" class="fw-semibold small">Consumidor Final</span>
    <span id="clienteCuit" class="text-muted small"></span>
    <input type="hidden" id="clienteCondIva" value="consumidor_final" />
    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="clearCliente()" title="Consumidor Final"><i class="bi bi-person-x"></i></button>
</div>
<div id="clienteSuggestions" style="position:relative"></div>

<div class="pos-layout">
    <div class="pos-left">
        <div class="pos-search-box">
            <div class="d-flex gap-1">
                <input id="productSearch" placeholder="🔍 Buscar producto por nombre o código..." autofocus class="flex-fill" style="flex:1" />
                <button class="btn btn-sm btn-outline-secondary" type="button" id="btnScanCam" title="Escanear código de barras con la cámara"><i class="bi bi-camera"></i></button>
            </div>
            <div id="scanReader" style="display:none;max-width:300px;margin-top:4px"></div>
            <div class="pos-results" id="productResults" style="display:none"></div>
        </div>

        <div class="pos-cart">
            <div class="pos-cart-header">
                <span>Carrito</span>
                <span id="cartCount">0 items</span>
            </div>
            <div class="pos-cart-items" id="cartItems">
                <div class="text-muted text-center py-4 small">Buscá productos para agregar al carrito</div>
            </div>
            <div class="pos-totals">
                <div class="pt-row" id="posSubtotalRow"><span>Subtotal</span><span id="posSubtotal">$0</span></div>
                <div class="pt-row" id="posIvaRow"><span>IVA</span><span id="posIva">$0</span></div>
                <div class="pt-row">
                    <span>Dto. %</span>
                    <span><input type="number" id="posDescuento" value="0" min="0" max="100" style="width:60px;text-align:right;font-size:14px;border:1px solid #ccc;border-radius:4px;padding:2px 4px" onchange="recalcTotals()" />%</span>
                </div>
                <div class="pt-row pt-total"><span>TOTAL</span><span id="posTotal">$0</span></div>
            </div>
        </div>
    </div>

    <div class="pos-right">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cash-stack"></i> Formas de pago</span>
                <button class="btn btn-sm btn-outline-primary" type="button" onclick="addPagoLine()"><i class="bi bi-plus-lg"></i> Agregar</button>
            </div>
            <div class="card-body">
                <div id="pagosContainer"></div>
                <div class="d-flex justify-content-between align-items-center small mt-2 pt-2 border-top">
                    <span class="text-muted">Pagado:</span>
                    <strong id="pagosTotal">$0,00</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-muted">Vuelto:</span>
                    <strong id="vueltoDisplay">$0,00</strong>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Notas</div>
            <div class="card-body">
                <textarea class="form-control form-control-sm" id="facturaNotas" rows="2" placeholder="Observaciones..."></textarea>
            </div>
        </div>

        <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken) ?>" />

        <button class="btn btn-accent w-100 py-3 fw-bold fs-5" id="btnFacturar" onclick="submitFactura()">
            <i class="bi bi-receipt"></i> FACTURAR
        </button>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// ── State ──
let cart = [];
let productSearchTimer;
let html5Scanner = null;
let isScanning = false;

// ── Barcode scanner via camera ──
document.getElementById('btnScanCam').addEventListener('click', function() {
    const reader = document.getElementById('scanReader');
    if (isScanning) {
        if (html5Scanner) { html5Scanner.stop().catch(()=>{}); html5Scanner.clear(); }
        reader.style.display = 'none';
        isScanning = false;
        return;
    }
    reader.style.display = 'block';
    if (!html5Scanner) {
        html5Scanner = new Html5Qrcode('scanReader');
    }
    html5Scanner.start(
        { facingMode: 'environment' },
        { fps: 15, qrbox: { width: 250, height: 100 } },
        function(decodedText) {
            html5Scanner.stop().catch(()=>{});
            reader.style.display = 'none';
            isScanning = false;
            document.getElementById('productSearch').value = decodedText;
            searchProd(decodedText);
        },
        function() {}
    ).then(() => {
        isScanning = true;
    }).catch(err => {
        alert('Error al acceder a la cámara: ' + err);
        reader.style.display = 'none';
    });
});

function fmtPrice(cents) {
    return (cents / 100).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function getCondIva() {
    return document.getElementById('clienteCondIva').value || 'consumidor_final';
}

function displayPriceIsGross() {
    return getCondIva() !== 'responsable_inscripto';
}

function getDisplayPrice(netCents, ivaRate) {
    if (displayPriceIsGross()) {
        return netCents + Math.round(netCents * ivaRate / 100);
    }
    return netCents;
}

// ── Load remito items if present ──
<?php if ($remitoItems): ?>
<?php foreach ($remitoItems as $ri): ?>
addToCart({
    idprodu: <?= (int)($ri['idprodu'] ?? 0) ?>,
    idcodgusto: <?= (int)($ri['idcodgusto'] ?? 0) ?>,
    producto: '<?= htmlspecialchars($ri['producto'] ?? '', ENT_QUOTES) ?>',
    variedad: '<?= htmlspecialchars($ri['variedad'] ?? '', ENT_QUOTES) ?>',
    qty: <?= (int)($ri['qty'] ?? 1) ?>,
    unit_price_cents: <?= (int)round((float)($ri['precio'] ?? 0) * 100) ?>,
    iva_rate: <?= (float)($ri['tiva'] ?? 21) ?>,
});
<?php endforeach; ?>
<?php endif; ?>

<?php if ($presupuestoItems): ?>
<?php foreach ($presupuestoItems as $pi): ?>
addToCart({
    idprodu: <?= (int)($pi['idprodu'] ?? 0) ?>,
    idcodgusto: <?= (int)($pi['idcodgusto'] ?? 0) ?>,
    producto: '<?= htmlspecialchars($pi['producto'] ?? '', ENT_QUOTES) ?>',
    variedad: '<?= htmlspecialchars($pi['variedad'] ?? '', ENT_QUOTES) ?>',
    qty: <?= (int)($pi['qty'] ?? 1) ?>,
    unit_price_cents: <?= (int)round((float)($pi['precio'] ?? 0) * 100) ?>,
    iva_rate: <?= (float)($pi['tiva'] ?? 21) ?>,
});
<?php endforeach; ?>
<?php endif; ?>

// ── Product search ──
const prodInput = document.getElementById('productSearch');
const prodResults = document.getElementById('productResults');

prodInput.addEventListener('input', function() {
    clearTimeout(productSearchTimer);
    const val = this.value.trim();
    if (val.length < 2) { prodResults.style.display = 'none'; return; }
    productSearchTimer = setTimeout(() => searchProd(val), 200);
});

prodInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { prodResults.style.display = 'none'; this.blur(); }
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('.pos-search-box')) prodResults.style.display = 'none';
});

function searchProd(q) {
    fetch('/admin/facturas/buscar-productos?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            prodResults.innerHTML = '';
            if (!data || data.length === 0) {
                prodResults.innerHTML = '<div class="pos-result-item text-muted" style="justify-content:center">Sin resultados</div>';
                prodResults.style.display = 'block';
                return;
            }
            data.forEach(p => {
                const priceCents = p.precio ? Math.round(parseFloat(p.precio) * 100) : 0;
                const ivaRate = p.tiva || 21;
                const displayPrice = getDisplayPrice(priceCents, ivaRate);
                const stockDep = p.stock_deposito ?? 0;
                const stockTot = p.stock_total ?? 0;

                // Barcode scan auto-add
                if (p.matched_variant) {
                    const mv = p.matched_variant;
                    addToCart({
                        idprodu: p.idprodu,
                        idcodgusto: mv.idcodgusto,
                        producto: p.produ,
                        variedad: mv.nomgusto,
                        qty: 1,
                        unit_price_cents: priceCents,
                        iva_rate: ivaRate,
                    });
                    prodResults.style.display = 'none';
                    prodInput.value = '';
                    prodInput.focus();
                    return;
                }

                const div = document.createElement('div');
                div.className = 'pos-result-item';
                div.style.flexWrap = 'wrap';
                div.innerHTML = `
                    <div style="flex:1;min-width:0">
                        <div class="prod-name">${esc(p.produ)}</div>
                        <div class="prod-code">${esc(p.codprodu)} ${p.codprodup ? '| ' + esc(p.codprodup) : ''}</div>
                        <div style="font-size:11px;color:#6c757d;margin-top:2px">
                            <span class="text-success fw-semibold">Dep: ${stockDep}</span>
                            <span class="ms-2 text-muted">Total: ${stockTot}</span>
                        </div>
                    </div>
                    <div class="prod-price">$${fmtPrice(displayPrice)}</div>
                `;
                div.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    if (p.variants && p.variants.length > 0) {
                        showVariantPicker(p, priceCents, ivaRate);
                    } else {
                        addToCart({
                            idprodu: p.idprodu,
                            idcodgusto: 0,
                            producto: p.produ,
                            variedad: '',
                            qty: 1,
                            unit_price_cents: priceCents,
                            iva_rate: ivaRate,
                        });
                        prodResults.style.display = 'none';
                        prodInput.value = '';
                        prodInput.focus();
                    }
                });
                prodResults.appendChild(div);
            });
            prodResults.style.display = 'block';
        });
}

function showVariantPicker(p, priceCents, ivaRate) {
    let html = '<div class="pos-result-item" style="flex-direction:column;align-items:stretch;cursor:default">';
    html += '<div class="fw-bold mb-2">' + esc(p.produ) + ' — elegí variedad:</div>';
    p.variants.forEach(v => {
        const cs = v.codscan ? ' (' + v.codscan + ')' : '';
        const vStockDep = v.stock_deposito ?? 0;
        const vStockTot = v.stock_total ?? 0;
        html += '<div class="suggestion-item" data-id="' + v.idcodgusto + '" data-nom="' + esc(v.nomgusto) + '">'
            + esc(v.nomgusto) + cs
            + ' <span class="text-success" style="font-size:11px">Dep: ' + vStockDep + '</span>'
            + ' <span class="text-muted" style="font-size:11px">Total: ' + vStockTot + '</span>'
            + '</div>';
    });
    html += '</div>';
    prodResults.innerHTML = html;
    prodResults.querySelectorAll('.suggestion-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            const nom = this.dataset.nom || '';
            const gustoId = this.dataset.id || 0;
            addToCart({
                idprodu: p.idprodu,
                idcodgusto: parseInt(gustoId),
                producto: p.produ,
                variedad: nom,
                qty: 1,
                unit_price_cents: priceCents,
                iva_rate: ivaRate,
            });
            prodResults.style.display = 'none';
            prodInput.value = '';
            prodInput.focus();
        });
    });
}

// ── Cart ──
function addToCart(item) {
    const netPrice = item.unit_price_cents || 0;
    const key = item.idprodu + '-' + item.idcodgusto;
    const existing = cart.find(c => (c.idprodu + '-' + c.idcodgusto) === key);
    if (existing) {
        existing.qty += item.qty || 1;
    } else {
        cart.push({
            idprodu: item.idprodu,
            idcodgusto: item.idcodgusto || 0,
            producto: item.producto,
            variedad: item.variedad || '',
            qty: item.qty || 1,
            net_price_cents: netPrice,
            iva_rate: item.iva_rate || 21,
        });
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const count = document.getElementById('cartCount');
    if (cart.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-4 small">Carrito vacío</div>';
        count.textContent = '0 items';
        recalcTotals();
        return;
    }

    count.textContent = cart.length + ' item(s)';
    container.innerHTML = cart.map((item, idx) => {
        const displayPrice = getDisplayPrice(item.net_price_cents, item.iva_rate);
        const total = item.qty * displayPrice;
        return `
            <div class="pos-cart-item" data-idx="${idx}">
                <div class="ci-name">
                    <div class="ci-prod">${esc(item.producto)}</div>
                    <div class="ci-var">${item.variedad ? esc(item.variedad) : '—'}</div>
                </div>
                <div class="ci-qty"><input type="number" value="${item.qty}" min="1" onchange="updateQty(${idx}, this.value)" /></div>
                <div class="ci-price">$${fmtPrice(displayPrice)}</div>
                <div class="ci-total">$${fmtPrice(total)}</div>
                <div class="ci-del" onclick="removeItem(${idx})">&times;</div>
            </div>
        `;
    }).join('');
    recalcTotals();
}

function updateQty(idx, val) {
    cart[idx].qty = Math.max(1, parseInt(val) || 1);
    renderCart();
}

function removeItem(idx) {
    cart.splice(idx, 1);
    renderCart();
}

function recalcTotals() {
    let subtotal = 0, iva = 0, total = 0;
    cart.forEach(item => {
        const netLine = item.qty * item.net_price_cents;
        const lineIva = item.iva_rate > 0 ? Math.round(netLine * item.iva_rate / 100) : 0;
        subtotal += netLine;
        iva += lineIva;
        total += netLine + lineIva;
    });
    const descPct = parseInt(document.getElementById('posDescuento').value) || 0;
    const descuento = descPct > 0 ? Math.round(total * descPct / 100) : 0;

    const isRI = getCondIva() === 'responsable_inscripto';
    document.getElementById('posIvaRow').style.display = isRI ? 'flex' : 'none';
    document.getElementById('posSubtotal').textContent = '$' + fmtPrice(isRI ? subtotal : total);
    document.getElementById('posIva').textContent = '$' + fmtPrice(iva);
    const totalConDto = total - descuento;
    document.getElementById('posTotal').textContent = '$' + fmtPrice(totalConDto);

    let pagado = 0;
    document.querySelectorAll('#pagosContainer .fp-monto').forEach(inp => {
        pagado += parseInt(parseFloat(inp.value) * 100) || 0;
    });
    const vuelto = pagado > totalConDto ? pagado - totalConDto : 0;
    document.getElementById('pagosTotal').textContent = '$' + fmtPrice(pagado);
    document.getElementById('vueltoDisplay').textContent = '$' + fmtPrice(vuelto);
}

// ── Client search ──
const cliInput = document.getElementById('clienteSearch');
const cliSuggestions = document.getElementById('clienteSuggestions');
let cliTimer;

cliInput.addEventListener('input', function() {
    clearTimeout(cliTimer);
    const val = this.value.trim();
    if (val.length < 2) { cliSuggestions.innerHTML = ''; return; }
    cliTimer = setTimeout(() => {
        fetch('/admin/facturas/buscar-clientes?q=' + encodeURIComponent(val))
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
                    const condIvaLabel = c.condicion_iva === 'responsable_inscripto' ? ' (RI)' : c.condicion_iva === 'monotributista' ? ' (Mono)' : c.condicion_iva === 'exento' ? ' (EX)' : '';
                    div.innerHTML = '<strong>' + esc(c.name) + '</strong> ' + esc(c.cuit || '') + condIvaLabel + ' <span class="text-muted">' + esc(c.email || '') + '</span>';
                    div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
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
    document.getElementById('clienteErpId').value = c.idclien || 0;
    document.getElementById('clienteNombre').textContent = c.name || 'Consumidor Final';
    document.getElementById('clienteCuit').textContent = c.cuit || '';
    document.getElementById('clienteCondIva').value = c.condicion_iva || 'consumidor_final';
    const displayName = (c.name || 'Consumidor Final') + (c.cuit ? ' - ' + c.cuit : '');
    document.getElementById('clienteNombre').textContent = displayName;
    cliInput.value = c.name || '';
    cliSuggestions.innerHTML = '';

    const iva = c.condicion_iva || 'consumidor_final';
    const tipoMap = {
        'responsable_inscripto': 'FACT-A',
        'consumidor_final': 'FACT-B',
        'monotributista': 'FACT-C',
        'exento': 'FACT-C',
    };
    document.getElementById('tipoComprobante').value = tipoMap[iva] || 'FACT-B';
    renderCart();
}

function clearCliente() {
    document.getElementById('clienteId').value = 0;
    document.getElementById('clienteErpId').value = 0;
    document.getElementById('clienteNombre').textContent = 'Consumidor Final';
    document.getElementById('clienteCuit').textContent = '';
    document.getElementById('clienteCondIva').value = 'consumidor_final';
    cliInput.value = '';
    cliSuggestions.innerHTML = '';
    renderCart();
}

// ── Remito search ──
const remInput = document.getElementById('remitoSearch');
const remSuggestions = document.getElementById('remitoSuggestions');
if (remInput) {
    let remTimer;
    remInput.addEventListener('input', function() {
        clearTimeout(remTimer);
        const val = this.value.trim();
        if (val.length < 2) { remSuggestions.innerHTML = ''; return; }
        remTimer = setTimeout(() => {
            fetch('/admin/facturas/buscar-remitos?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    remSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        remSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(r => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.innerHTML = '<strong>' + esc(r.codigo) + '</strong> <span class="text-muted">' + esc(r.cliente_nombre || '') + '</span>';
                        div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            window.location.href = '/admin/facturas/nueva?remito_id=' + r.id;
                        });
                        remSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    remInput.addEventListener('blur', function() {
        setTimeout(() => remSuggestions.innerHTML = '', 300);
    });
}

// ── Presupuesto search ──
const presInput = document.getElementById('presupuestoSearch');
const presSuggestions = document.getElementById('presupuestoSuggestions');
if (presInput) {
    let presTimer;
    presInput.addEventListener('input', function() {
        clearTimeout(presTimer);
        const val = this.value.trim();
        if (val.length < 2) { presSuggestions.innerHTML = ''; return; }
        presTimer = setTimeout(() => {
            fetch('/admin/facturas/buscar-presupuestos?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    presSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        presSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.innerHTML = '<strong>' + esc(p.codigo) + '</strong> <span class="text-muted">' + esc(p.cliente_nombre || '') + '</span>';
                        div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            window.location.href = '/admin/facturas/nueva?presupuesto_id=' + p.id;
                        });
                        presSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    presInput.addEventListener('blur', function() {
        setTimeout(() => presSuggestions.innerHTML = '', 300);
    });
}

// ── Payment lines (multi-pago) ──
const BANCOS = <?= json_encode($bancos, JSON_UNESCAPED_UNICODE) ?>;
const PLAZOS = <?= json_encode($plazos, JSON_UNESCAPED_UNICODE) ?>;
const FORMAS_PAGO = [
    ['efectivo', 'Efectivo'],
    ['transferencia', 'Transferencia bancaria'],
    ['tarjeta_credito', 'Tarjeta de crédito'],
    ['tarjeta_debito', 'Tarjeta de débito'],
    ['mercadopago', 'Mercado Pago'],
    ['cuenta_corriente', 'Cuenta corriente'],
    ['cheque', 'Cheque de terceros'],
];

function addPagoLine(forma) {
    const container = document.getElementById('pagosContainer');
    const div = document.createElement('div');
    div.className = 'pago-line border rounded p-2 mb-2 bg-light';
    div.innerHTML = `
        <div class="row g-1 align-items-center">
            <div class="col-7">
                <select class="form-select form-select-sm fp-forma" onchange="onPagoFormaChange(this)">
                    ${FORMAS_PAGO.map(f => `<option value="${f[0]}"${forma == f[0] ? ' selected' : ''}>${f[1]}</option>`).join('')}
                </select>
            </div>
            <div class="col-4">
                <div class="input-group input-group-sm"><span class="input-group-text">$</span><input class="form-control fp-monto" type="number" min="0" step="0.01" value="0" oninput="recalcTotals()" /></div>
            </div>
            <div class="col-1 text-end">
                <button class="btn btn-sm btn-outline-danger fp-del" type="button" onclick="removePagoLine(this)"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="fp-extra mt-2"></div>`;
    container.appendChild(div);
    onPagoFormaChange(div.querySelector('.fp-forma'));
    recalcTotals();
}

function removePagoLine(btn) {
    btn.closest('.pago-line').remove();
    recalcTotals();
}

function onPagoFormaChange(sel) {
    const line = sel.closest('.pago-line');
    const extra = line.querySelector('.fp-extra');
    const forma = sel.value;
    if (forma === 'tarjeta_credito') {
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-12"><label class="small text-muted">N° de cupón</label><input class="form-control form-control-sm fp-cupon" placeholder="N° de cupón" /></div>
            </div>`;
    } else if (forma === 'cuenta_corriente') {
        let opts = '<option value="">— Sin plazo —</option>';
        PLAZOS.forEach(p => { opts += `<option value="${p.idplazo}">${esc(p.descripcion)}</option>`; });
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-12"><label class="small text-muted">Plazo / cuotas</label>
                    <select class="form-select form-select-sm fp-plazo">${opts}</select>
                </div>
            </div>`;
    } else if (forma === 'cheque') {
        let bopts = '<option value="">— Seleccionar banco —</option>';
        BANCOS.forEach(b => { bopts += `<option value="${b.idban}">${esc(b.nombanc)}</option>`; });
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-6"><label class="small text-muted">Banco</label><select class="form-select form-select-sm fp-banco">${bopts}</select></div>
                <div class="col-6"><label class="small text-muted">N° de cheque</label><input class="form-control form-control-sm fp-chequenum" /></div>
                <div class="col-6"><label class="small text-muted">Titular</label><input class="form-control form-control-sm fp-chequetitular" /></div>
                <div class="col-6"><label class="small text-muted">CUIT</label><input class="form-control form-control-sm fp-chequecuit" /></div>
                <div class="col-6"><label class="small text-muted">Vencimiento</label><input class="form-control form-control-sm fp-chequevenc" type="date" /></div>
            </div>`;
    } else {
        extra.innerHTML = '';
    }
    recalcTotals();
}

addPagoLine('efectivo');

// ── Submit ──
function submitFactura() {
    if (cart.length === 0) { alert('Agregá productos al carrito.'); return; }
    const btn = document.getElementById('btnFacturar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Facturando...';

    const tipo = document.getElementById('tipoComprobante').value;
    const clienteId = parseInt(document.getElementById('clienteId').value) || 0;
    const remitoId = parseInt(document.getElementById('remitoId').value) || 0;
    const clienteNombre = clienteId ? (document.getElementById('clienteNombre').textContent || 'Consumidor Final') : 'Consumidor Final';
    const clienteCuit = document.getElementById('clienteCuit').textContent || '';
    const clienteCondIva = document.getElementById('clienteCondIva').value || 'consumidor_final';
    const clienteErpId = parseInt(document.getElementById('clienteErpId').value) || 0;
    const notas = document.getElementById('facturaNotas').value;

    const descPct = parseInt(document.getElementById('posDescuento').value) || 0;
    const totalBruto = cart.reduce((sum, item) => {
        const netLine = item.qty * item.net_price_cents;
        const lineIva = item.iva_rate > 0 ? Math.round(netLine * item.iva_rate / 100) : 0;
        return sum + netLine + lineIva;
    }, 0);
    const descuentoCents = descPct > 0 ? Math.round(totalBruto * descPct / 100) : 0;

    const pagos = [];
    document.querySelectorAll('#pagosContainer .pago-line').forEach(line => {
        const forma = line.querySelector('.fp-forma').value;
        const monto = parseInt(parseFloat(line.querySelector('.fp-monto').value) * 100) || 0;
        if (monto <= 0) return;
        const entry = { forma_pago: forma, monto_cents: monto };
        if (forma === 'tarjeta_credito') {
            entry.cupon_numero = (line.querySelector('.fp-cupon').value || '').trim();
            entry.cupon_monto_cents = monto || null;
        } else if (forma === 'cuenta_corriente') {
            entry.idplazo = parseInt(line.querySelector('.fp-plazo').value) || null;
        } else if (forma === 'cheque') {
            const bancoSel = line.querySelector('.fp-banco');
            entry.cheque = {
                banco_id: parseInt(bancoSel.value) || null,
                banco: bancoSel.selectedIndex >= 0 ? bancoSel.options[bancoSel.selectedIndex].text : '',
                numero: line.querySelector('.fp-chequenum').value,
                titular: line.querySelector('.fp-chequetitular').value,
                cuit: line.querySelector('.fp-chequecuit').value,
                vencimiento: line.querySelector('.fp-chequevenc').value,
            };
        }
        pagos.push(entry);
    });
    if (pagos.length === 0) {
        alert('Agregá al menos una forma de pago (con monto mayor a cero).');
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-receipt"></i> FACTURAR';
        return;
    }
    const totalFactura = totalBruto - descuentoCents;
    const totalPagado = pagos.reduce((s, p) => s + p.monto_cents, 0);
    if (totalPagado < totalFactura) {
        const ok = confirm('El total pagado ($' + fmtPrice(totalPagado) + ') es menor que el total de la factura ($' + fmtPrice(totalFactura) + '). ¿Continuar igual?');
        if (!ok) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-receipt"></i> FACTURAR';
            return;
        }
    }

    const presupuestoId = parseInt(document.getElementById('presupuestoId').value) || 0;
    const vendedorEl = document.getElementById('vendedorId');
    const payload = {
        _csrf: document.getElementById('csrfToken').value,
        tipo_comprobante: tipo,
        forma_pago: pagos[0].forma_pago,
        remito_id: remitoId,
        presupuesto_id: presupuestoId,
        vendedor_id: vendedorEl ? parseInt(vendedorEl.value) || null : null,
        notas: notas,
        fecha: new Date().toISOString().slice(0,10),
        descuento_cents: descuentoCents,
        cliente: {
            id: clienteId || null,
            idclien: clienteErpId || null,
            nombre: clienteNombre.replace(/ -.*$/, ''), // strip CUIT from display
            cuit: clienteCuit,
            condicion_iva: clienteCondIva,
        },
        items: cart.map(item => ({
            idprodu: item.idprodu,
            idcodgusto: item.idcodgusto,
            producto: item.producto,
            variedad: item.variedad,
            qty: item.qty,
            unit_price_cents: item.net_price_cents,
            iva_rate: item.iva_rate,
        })),
        pagos: pagos,
    };

    fetch('/admin/facturas/guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.text().then(text => {
        try {
            const res = JSON.parse(text);
            if (res.ok) {
                if (res.arca && res.arca.cae) {
                    var fmt = localStorage.getItem('perfushopping_print_format') || '80mm';
                    window.location.href = '/admin/facturas/imprimir/' + res.id + '?auto=1&formato=' + fmt;
                } else {
                    window.location.href = '/admin/facturas/' + res.id;
                }
            } else {
                alert(res.error || 'Error al facturar');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-receipt"></i> FACTURAR';
            }
        } catch (e) {
            alert('Error del servidor: ' + text.substring(0, 300));
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-receipt"></i> FACTURAR';
        }
    }))
    .catch(err => {
        alert('Error de conexión: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-receipt"></i> FACTURAR';
    });
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
