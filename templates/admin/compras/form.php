<?php
use Perfushopping\Web\Service\ExcelReader;

$compra = $compra ?? [];
$items = $items ?? [];
$cuentas = $cuentas ?? [];
$cuentasGrupo = $cuentasGrupo ?? [];
$depositos = $depositos ?? [];
$depositoDefault = (int)($depositoDefault ?? 0);
$csrfToken = $csrf ?? '';
$isEdit = (int)($compra['id'] ?? 0) > 0;
$mon = static fn ($v) => number_format((float)$v, 2, ',', '.');

$tipos = [
    '' => '— Tipo de comprobante —',
    'Factura A' => 'Factura A', 'Nota de Débito A' => 'Nota de Débito A', 'Nota de Crédito A' => 'Nota de Crédito A', 'Recibo A' => 'Recibo A',
    'Factura B' => 'Factura B', 'Nota de Débito B' => 'Nota de Débito B', 'Nota de Crédito B' => 'Nota de Crédito B', 'Recibo B' => 'Recibo B',
    'Factura C' => 'Factura C', 'Nota de Débito C' => 'Nota de Débito C', 'Nota de Crédito C' => 'Nota de Crédito C', 'Recibo C' => 'Recibo C',
    'Factura M' => 'Factura M', 'Nota de Débito M' => 'Nota de Débito M', 'Nota de Crédito M' => 'Nota de Crédito M',
];
$idcta1Sel = (int)($compra['idcta1'] ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= $isEdit ? 'Editar factura de compra' : 'Nueva factura de compra' ?></h4>
        <p class="text-muted small">Carga manual, por QR o completando una importada. Al guardar con ítems se actualiza stock y precios.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/compras"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if (!$isEdit): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-qr-code"></i> Cargar desde QR de ARCA</div>
    <div class="card-body">
        <form method="post" action="/admin/compras/qr" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
            <div class="col-lg-9">
                <input class="form-control form-control-sm" name="qr" placeholder="Pegá el texto del QR del comprobante (https://www.afip.gob.ar/fe/qr/?p=1&v=...)" />
            </div>
            <div class="col-lg-3">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-magic"></i> Completar datos</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<form method="post" action="/admin/compras/guardar" id="compraForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="id" value="<?= (int)($compra['id'] ?? 0) ?>" />
    <input type="hidden" name="origen" value="<?= htmlspecialchars((string)($compra['origen'] ?? 'manual')) ?>" />

    <div class="row g-3">
        <div class="col-lg-7">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Ítems</span>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="addRow()"><i class="bi bi-plus-lg"></i> Agregar producto</button>
                </div>
                <div class="card-body p-0">
                    <table class="table table-sm mb-0" id="itemsTable">
                        <thead>
                            <tr>
                                <th style="min-width:150px">Producto</th>
                                <th>Variedad</th>
                                <th style="width:70px">Cant.</th>
                                <th style="width:110px">Costo unit.</th>
                                <th style="width:100px">Subtotal</th>
                                <th style="width:36px"></th>
                            </tr>
                        </thead>
                        <tbody id="itemsBody"></tbody>
                    </table>
                    <div class="p-2 text-muted small">
                        <i class="bi bi-info-circle"></i> Al guardar con ítems, se suma stock al depósito y se recalculan los precios con los márgenes del producto.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Comprobante</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Fecha <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" name="fecha" type="date" value="<?= htmlspecialchars((string)($compra['fecha'] ?? date('Y-m-d'))) ?>" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Tipo</label>
                            <select class="form-select form-select-sm" name="tipo">
                                <?php foreach ($tipos as $v => $l): ?>
                                    <option value="<?= htmlspecialchars($v) ?>" <?= (string)($compra['tipo'] ?? '') === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-4">
                            <label class="form-label small">Punto venta</label>
                            <input class="form-control form-control-sm" name="punto_venta" value="<?= htmlspecialchars((string)($compra['punto_venta'] ?? '')) ?>" />
                        </div>
                        <div class="col-4">
                            <label class="form-label small">N° desde</label>
                            <input class="form-control form-control-sm" name="numero_desde" value="<?= htmlspecialchars((string)($compra['numero_desde'] ?? '')) ?>" />
                        </div>
                        <div class="col-4">
                            <label class="form-label small">N° hasta</label>
                            <input class="form-control form-control-sm" name="numero_hasta" value="<?= htmlspecialchars((string)($compra['numero_hasta'] ?? '')) ?>" />
                        </div>
                        <div class="col-12">
                            <label class="form-label small">Cód. autorización (CAE)</label>
                            <input class="form-control form-control-sm" name="cod_autorizacion" value="<?= htmlspecialchars((string)($compra['cod_autorizacion'] ?? '')) ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Proveedor</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">CUIT <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" name="cuit_proveedor" id="cuitProveedor" value="<?= htmlspecialchars((string)($compra['cuit_proveedor'] ?? '')) ?>" placeholder="20-12345678-9" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Razón social <span class="text-danger">*</span></label>
                            <input class="form-control form-control-sm" name="razon_proveedor" id="razonProveedor" value="<?= htmlspecialchars((string)($compra['razon_proveedor'] ?? '')) ?>" required />
                        </div>
                        <div class="col-12 form-text small">Si el CUIT no existe como proveedor, se crea automáticamente al guardar.</div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Importes</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small">Moneda</label>
                            <select class="form-select form-select-sm" name="moneda">
                                <?php foreach (['PES' => 'Peso argentino', 'USD' => 'Dólar', 'EUR' => 'Euro'] as $v => $l): ?>
                                    <option value="<?= $v ?>" <?= (string)($compra['moneda'] ?? 'PES') === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Tipo cambio</label>
                            <input class="form-control form-control-sm" name="tipo_cambio" type="number" step="0.0001" min="0" value="<?= htmlspecialchars((string)($compra['tipo_cambio'] ?? 1)) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Neto gravado</label>
                            <input class="form-control form-control-sm importe-input" name="imp_neto_gravado" value="<?= htmlspecialchars((string)($compra['imp_neto_gravado'] ?? '0')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">No gravado</label>
                            <input class="form-control form-control-sm importe-input" name="imp_no_gravado" value="<?= htmlspecialchars((string)($compra['imp_no_gravado'] ?? '0')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Exento</label>
                            <input class="form-control form-control-sm importe-input" name="imp_exento" value="<?= htmlspecialchars((string)($compra['imp_exento'] ?? '0')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Otros tributos</label>
                            <input class="form-control form-control-sm importe-input" name="otros_tributos" value="<?= htmlspecialchars((string)($compra['otros_tributos'] ?? '0')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">IVA</label>
                            <input class="form-control form-control-sm importe-input" name="imp_iva" value="<?= htmlspecialchars((string)($compra['imp_iva'] ?? '0')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-bold">Total</label>
                            <input class="form-control form-control-sm importe-input fw-bold" name="imp_total" value="<?= htmlspecialchars((string)($compra['imp_total'] ?? '0')) ?>" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Cuenta contable y depósito</div>
                <div class="card-body">
                    <div class="mb-2">
                        <label class="form-label small">Cuenta contable</label>
                        <select class="form-select form-select-sm" name="idcta1" id="idcta1Sel">
                            <option value="0">— Crear subcuenta —</option>
                            <?php foreach ($cuentas as $c): ?>
                                <option value="<?= (int)$c['idcta1'] ?>" <?= $idcta1Sel === (int)$c['idcta1'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$c['nomcta'] . ' › ' . $c['nomcta1']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="nuevaCuentaBox" class="row g-2 <?= $idcta1Sel > 0 ? 'd-none' : '' ?>">
                        <div class="col-6">
                            <label class="form-label small">Grupo</label>
                            <select class="form-select form-select-sm" name="idcta">
                                <?php if (!$cuentasGrupo): ?>
                                    <option value="0">No hay grupos</option>
                                <?php endif; ?>
                                <?php foreach ($cuentasGrupo as $g): ?>
                                    <option value="<?= (int)$g['idcta'] ?>"><?= htmlspecialchars((string)$g['nomcta']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Nombre subcuenta</label>
                            <input class="form-control form-control-sm" name="nueva_subcuenta" placeholder="Ej: Compras mayoristas" />
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Depósito de stock</label>
                        <select class="form-select form-select-sm" name="iddepo">
                            <?php if (!$depositos): ?>
                                <option value="0">No hay depósitos de venta</option>
                            <?php endif; ?>
                            <?php foreach ($depositos as $d): ?>
                                <option value="<?= (int)$d['iddepo'] ?>" <?= (int)($compra['iddepo'] ?? $depositoDefault) === (int)$d['iddepo'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$d['nomdepo']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Observaciones</div>
                <div class="card-body">
                    <textarea class="form-control form-control-sm" name="observaciones" rows="2" placeholder="Notas, condiciones..."><?= htmlspecialchars((string)($compra['observaciones'] ?? '')) ?></textarea>
                </div>
            </div>

            <button class="btn btn-accent w-100" type="submit"><i class="bi bi-check-lg"></i> Guardar factura</button>
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
    <?php if ($items): ?>
        <?php foreach ($items as $it): ?>
            addRow({
                idprodu: <?= (int)($it['idprodu'] ?? 0) ?>,
                idcodgusto: <?= (int)($it['idcodgusto'] ?? 0) ?>,
                product_name: <?= json_encode((string)($it['product_name'] ?? ($it['codprodu'] ?? ''))) ?>,
                nomgusto: <?= json_encode((string)($it['nomgusto'] ?? '')) ?>,
                qty: <?= (float)($it['qty'] ?? 1) ?>,
                unit_cost: <?= (float)($it['unit_cost'] ?? 0) ?>,
                precomp: <?= (float)($it['unit_cost'] ?? 0) ?>,
                variants: []
            });
        <?php endforeach; ?>
    <?php else: ?>
        addRow();
    <?php endif; ?>
    recalcular();
});

function addRow(data) {
    const tbody = document.getElementById('itemsBody');
    const row = document.createElement('tr');
    row.className = 'item-row';
    const name = data ? data.product_name || '' : '';
    const idcodgusto = data ? (data.idcodgusto || '') : '';
    const nomgusto = data ? (data.nomgusto || '') : '';
    const qty = data ? data.qty : 1;
    const cost = data ? (data.unit_cost || data.precomp || 0) : 0;
    row.innerHTML = `
        <td>
            <input class="form-control form-control-sm prod-input" name="item_name[]" placeholder="Buscar producto..." autocomplete="off" value="${esc(name)}" />
            <input type="hidden" name="item_idprodu[]" class="idprodu" value="${data ? data.idprodu || '' : ''}" />
            <div class="prod-suggestions"></div>
        </td>
        <td>
            <select class="form-select form-select-sm variedad-select" name="item_idcodgusto[]">
                <option value="">—</option>
            </select>
        </td>
        <td><input class="form-control form-control-sm qty-input" name="item_qty[]" type="number" value="${qty}" min="0" step="0.01" /></td>
        <td>
            <div class="input-group input-group-sm">
                <span class="input-group-text">$</span>
                <input class="form-control form-control-sm cost-input" name="item_cost[]" type="number" value="${cost}" min="0" step="0.01" />
            </div>
        </td>
        <td class="text-end line-total pt-3 small">$0,00</td>
        <td><button class="btn btn-sm btn-outline-danger" type="button" onclick="removeRow(this)"><i class="bi bi-x"></i></button></td>
    `;
    tbody.appendChild(row);
    setupRow(row);
    if (data && data.idcodgusto && nomgusto) {
        const vs = row.querySelector('.variedad-select');
        vs.innerHTML = '<option value="">—</option><option value="' + data.idcodgusto + '" selected>' + esc(nomgusto) + '</option>';
        vs.disabled = true;
    }
    rowCounter++;
    recalcular();
}

function setupRow(row) {
    const input = row.querySelector('.prod-input');
    const suggestions = row.querySelector('.prod-suggestions');
    const idprodu = row.querySelector('.idprodu');
    const variedadSelect = row.querySelector('.variedad-select');
    const qtyInput = row.querySelector('.qty-input');
    const costInput = row.querySelector('.cost-input');

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
        idprodu.value = opt && opt.dataset.idprodu ? opt.dataset.idprodu : idprodu.value;
        variedadSelect.disabled = !(opt && opt.dataset.gusto);
    });

    qtyInput.addEventListener('input', recalcular);
    costInput.addEventListener('input', recalcular);
}

function searchProducts(q, container, row) {
    fetch('/admin/compras/productos?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(data => {
            container.innerHTML = '';
            if (!data || data.length === 0) {
                container.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                return;
            }
            data.forEach(p => {
                const div = document.createElement('div');
                div.className = 'suggestion-item';
                const costStr = p.precomp ? '$' + Number(p.precomp).toLocaleString('es-AR', {minimumFractionDigits:2}) : '';
                div.innerHTML = '<strong>' + esc(p.produ) + '</strong> <span class="text-muted">(' + esc(p.codprodu) + ') ' + costStr + '</span>';
                div.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    selectProduct(p, row, container);
                });
                container.appendChild(div);
            });
        });
}

function selectProduct(p, row, container) {
    row.querySelector('.prod-input').value = p.produ || '';
    row.querySelector('.idprodu').value = p.idprodu || '';
    container.innerHTML = '';
    row.querySelector('.cost-input').value = p.precomp || 0;
    recalcular();

    const vs = row.querySelector('.variedad-select');
    vs.innerHTML = '<option value="">—</option>';
    vs.disabled = false;
    row.querySelector('.idprodu').value = p.idprodu || '';
    if (p.variants && p.variants.length > 0) {
        p.variants.forEach(v => {
            const opt = document.createElement('option');
            opt.value = v.idcodgusto || '';
            opt.dataset.gusto = v.idcodgusto || '';
            opt.dataset.idprodu = p.idprodu || '';
            opt.textContent = (v.nomgusto || '') + (v.codscan ? ' (' + v.codscan + ')' : '');
            vs.appendChild(opt);
        });
        vs.selectedIndex = 1;
        vs.dispatchEvent(new Event('change'));
    }
}

function removeRow(btn) {
    const rows = document.querySelectorAll('.item-row');
    if (rows.length <= 1) return;
    btn.closest('tr').remove();
    recalcular();
}

function recalcular() {
    document.querySelectorAll('.item-row').forEach(row => {
        const qty = parseFloat(row.querySelector('.qty-input').value) || 0;
        const cost = parseFloat(row.querySelector('.cost-input').value) || 0;
        const line = qty * cost;
        row.querySelector('.line-total').textContent = '$' + line.toLocaleString('es-AR', {minimumFractionDigits:2});
    });
}

document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'idcta1Sel') {
        document.getElementById('nuevaCuentaBox').classList.toggle('d-none', parseInt(e.target.value) > 0);
    }
});

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
