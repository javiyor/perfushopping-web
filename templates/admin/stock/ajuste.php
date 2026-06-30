<?php
$depositos = $depositos ?? [];
$producto = $producto ?? null;
$variantes = $variantes ?? [];
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
                        <label class="form-label small fw-semibold">Producto</label>
                        <div class="input-group">
                            <input class="form-control form-control-sm" id="productoSearch" placeholder="Buscar producto por nombre o código..." autocomplete="off" />
                            <input type="hidden" name="idprodu" id="idprodu" value="<?= $producto ? (int)$producto['idprodu'] : '0' ?>" />
                            <button class="btn btn-outline-secondary btn-sm" type="button" id="clearProducto"><i class="bi bi-x-lg"></i></button>
                        </div>
                        <div id="productoResults" class="list-group mt-1" style="display:none;position:absolute;z-index:1050;max-height:300px;overflow-y:auto"></div>
                        <div id="productoSelected" class="mt-1 <?= $producto ? '' : 'd-none' ?>">
                            <span class="badge bg-info fs-6" id="productoLabel"><?= $producto ? htmlspecialchars($producto['produ'] ?? '') : '' ?></span>
                        </div>
                    </div>

                    <div class="mb-3" id="varianteGroup" style="<?= $variantes ? '' : 'display:none' ?>">
                        <label class="form-label small fw-semibold">Variante <span class="text-muted">(opcional)</span></label>
                        <select class="form-select form-select-sm" name="idcodgusto" id="varianteSelect">
                            <option value="0">Todas (producto base)</option>
                            <?php foreach ($variantes as $v): ?>
                                <option value="<?= (int)$v['idcodgusto'] ?>"><?= htmlspecialchars($v['nomgusto'] ?? '') ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Depósito</label>
                            <select class="form-select form-select-sm" name="iddepo" required>
                                <option value="">Seleccionar...</option>
                                <?php foreach ($depositos as $d): ?>
                                    <option value="<?= (int)$d['iddepo'] ?>"><?= htmlspecialchars($d['nomdepo'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Cantidad</label>
                            <input class="form-control form-control-sm" type="number" name="cantidad" required step="1" placeholder="Ej: 10 o -5" />
                            <div class="form-text">Positivo = ingreso, Negativo = egreso</div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">Stock resultante</label>
                            <div class="form-control form-control-sm bg-light" id="stockResultante" style="min-height:31px">-</div>
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
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Instrucciones</div>
            <div class="card-body small">
                <p>Usá este formulario para registrar ajustes manuales de stock:</p>
                <ul class="mb-0">
                    <li><strong>Ingreso:</strong> cantidad positiva (ej: 10)</li>
                    <li><strong>Egreso:</strong> cantidad negativa (ej: -5)</li>
                    <li>Seleccioná el depósito donde se aplica el movimiento</li>
                    <li>Si el producto tiene variantes, podés ajustar una específica o dejar "Todas" para ajustar el producto base</li>
                    <li>El motivo es obligatorio para mantener trazabilidad</li>
                </ul>
                <hr />
                <p class="text-muted mb-0">Los ajustes quedan registrados en <code>stockcab</code>/<code>stockdet</code> con origen NULL (sin origen) y se actualizan las tablas <code>stock</code>, <code>producto.stocact</code> y <code>gustos.stockact</code> automáticamente.</p>
            </div>
        </div>
    </div>
</div>

<script>
let selectedProduct = <?= $producto ? json_encode(['idprodu' => (int)$producto['idprodu'], 'produ' => $producto['produ'], 'stocact' => (int)($producto['stocact'] ?? 0)]) : 'null' ?>;
let currentVariants = <?= json_encode($variantes) ?>;

const searchInput = document.getElementById('productoSearch');
const resultsDiv = document.getElementById('productoResults');
const idproduInput = document.getElementById('idprodu');
const selectedDiv = document.getElementById('productoSelected');
const productoLabel = document.getElementById('productoLabel');
const clearBtn = document.getElementById('clearProducto');
const varianteGroup = document.getElementById('varianteGroup');
const varianteSelect = document.getElementById('varianteSelect');
const cantidadInput = document.querySelector('[name="cantidad"]');
const stockResultante = document.getElementById('stockResultante');

function updateVariants(variants) {
    currentVariants = variants || [];
    varianteSelect.innerHTML = '<option value="0">Todas (producto base)</option>';
    variants.forEach(v => {
        varianteSelect.innerHTML += '<option value="' + v.idcodgusto + '">' + escHtml(v.nomgusto) + '</option>';
    });
    varianteGroup.style.display = variants.length ? '' : 'none';
}

function updateStockResultante() {
    if (!selectedProduct) { stockResultante.textContent = '-'; return; }
    const base = parseInt(selectedProduct.stocact || 0);
    const cant = parseInt(cantidadInput.value) || 0;
    stockResultante.textContent = base + cant;
}

function selectProduct(p) {
    selectedProduct = p;
    idproduInput.value = p.idprodu;
    productoLabel.textContent = p.produ;
    selectedDiv.classList.remove('d-none');
    searchInput.value = '';
    resultsDiv.style.display = 'none';

    // fetch variants
    fetch('/admin/stock/ajuste/variantes?id=' + p.idprodu)
        .then(r => r.json())
        .then(variants => {
            updateVariants(variants);
            updateStockResultante();
        })
        .catch(() => updateVariants([]));
}

function clearProducto() {
    selectedProduct = null;
    idproduInput.value = '0';
    selectedDiv.classList.add('d-none');
    productoLabel.textContent = '';
    updateVariants([]);
    stockResultante.textContent = '-';
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
                            selectProduct(p);
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

clearBtn.addEventListener('click', clearProducto);
cantidadInput.addEventListener('input', updateStockResultante);

if (selectedProduct) {
    searchInput.placeholder = selectedProduct.produ + ' (cambiar)';
}
</script>
