<?php
$rubros = $rubros ?? [];
$subrubros = $subrubros ?? [];
$departamentos = $departamentos ?? [];
$proveedores = $proveedores ?? [];
$ivaOptions = $ivaOptions ?? [];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/productos">Productos</a></li>
        <li class="breadcrumb-item active">Nuevo producto</li>
    </ol>
</nav>

<?php include __DIR__ . '/_producto_manual.php'; ?>

<form method="post" action="/admin/productos/crear">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Datos del producto</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del producto <span class="text-danger">*</span></label>
                        <input class="form-control" name="produ" placeholder="Nombre del producto" required />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label small">Costo <span class="text-muted">(sin IVA)</span></label>
                            <input class="form-control form-control-sm calc-trigger" name="precomp" placeholder="0.00" inputmode="decimal" />
                            <div class="d-flex gap-2 align-items-center mt-1">
                                <div class="form-check m-0" title="El costo ingresado tiene IVA incluido">
                                    <input class="form-check-input" type="checkbox" id="ivaIncCosto" />
                                    <label class="form-check-label small" for="ivaIncCosto">IVA incl.</label>
                                </div>
                                <div class="input-group input-group-sm" style="max-width:135px" title="Descuento % aplicado al costo">
                                    <input class="form-control form-control-sm calc-trigger" name="descuento_pct" placeholder="0" inputmode="decimal" />
                                    <span class="input-group-text">Dto%</span>
                                </div>
                            </div>
                            <small class="text-muted" id="costoNetoResumen"></small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Margen minorista <span class="text-muted">(%)</span></label>
                            <input class="form-control form-control-sm calc-trigger" name="ganan1" placeholder="0.00" inputmode="decimal" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Margen mayorista <span class="text-muted">(%)</span></label>
                            <input class="form-control form-control-sm calc-trigger" name="ganan2" placeholder="0.00" inputmode="decimal" />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Precio minorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio_gross" placeholder="0.00" inputmode="decimal" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Precio mayorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio1_gross" placeholder="0.00" inputmode="decimal" required />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
<label class="form-label small">Categoría</label>
                            <div class="input-group">
                                <select class="form-select form-select-sm" name="codrub">
                                    <option value="">— Sin categoría —</option>
                                    <?php foreach ($rubros as $rub): ?>
                                        <option value="<?= (int) ($rub['codrub'] ?? 0) ?>"><?= htmlspecialchars((string) ($rub['nomrub'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm" type="button" title="Nueva categoría" onclick="agregarCatalogo('rubro', 'categoría')"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Marca / Subrubro</label>
                            <div class="input-group">
                                <select class="form-select form-select-sm" name="codsub">
                                    <option value="">— Sin marca —</option>
                                    <?php foreach ($subrubros as $sub): ?>
                                        <option value="<?= (int) ($sub['codsub'] ?? 0) ?>"><?= htmlspecialchars((string) ($sub['nomsub'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm" type="button" title="Nueva marca / subrubro" onclick="agregarCatalogo('subrubro', 'marca')"><i class="bi bi-plus-lg"></i></button>
                            </div>
</div>
                        <div class="col-md-6">
                            <label class="form-label small">Departamento</label>
                            <div class="input-group">
                                <select class="form-select form-select-sm" name="codepar">
                                    <option value="">— Sin departamento —</option>
                                    <?php foreach ($departamentos as $dep): ?>
                                        <option value="<?= (int) ($dep['codepar'] ?? 0) ?>"><?= htmlspecialchars((string) ($dep['nomdepar'] ?? '')) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-outline-secondary btn-sm" type="button" title="Nuevo departamento" onclick="agregarCatalogo('departamento', 'departamento')"><i class="bi bi-plus-lg"></i></button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Proveedor</label>
                            <select class="form-select form-select-sm" name="codprove">
                                <option value="">— Sin proveedor —</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= (int)($prov['idprovee'] ?? 0) ?>"><?= htmlspecialchars((string)($prov['razon'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">IVA</label>
                        <select class="form-select form-select-sm calc-trigger" name="iva">
                            <?php foreach ($ivaOptions as $iva): $pct = (float)($iva['tiva'] ?? 0); ?>
                                <option value="<?= (int)($iva['codivaprodu'] ?? 0) ?>" data-iva-pct="<?= htmlspecialchars((string)$pct) ?>"<?= $pct === 21.0 ? ' selected' : '' ?>><?= htmlspecialchars((string)($iva['tiva'] ?? '')) ?>%</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="enweb" id="enweb" checked />
                        <label class="form-check-label" for="enweb">Visible en web</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Acciones</div>
                <div class="card-body">
                    <p class="small text-muted">Completá los datos básicos. Después podés agregar descripción, imágenes, variedades y datos logísticos desde la edición.</p>
                    <button class="btn btn-accent w-100" type="submit"><i class="bi bi-plus-lg"></i> Crear producto</button>
                    <a class="btn btn-outline-secondary w-100 mt-2" href="/admin/productos">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
function ordenarSelect(sel) {
    const cf = new Intl.Collator('es', {sensitivity: 'base', numeric: true});
    const opts = Array.from(sel.options);
    opts.sort((a, b) => {
        if (a.value === '') return -1;
        if (b.value === '') return 1;
        return cf.compare(a.text, b.text);
    });
    opts.forEach(o => sel.appendChild(o));
}
function agregarCatalogo(tipo, nombreLabel) {
    const csrf = document.querySelector('input[name="_csrf"]').value;
    const nombre = prompt('Nombre de la nueva ' + nombreLabel + ':', '');
    if (!nombre || !nombre.trim()) return;
    const body = new URLSearchParams({_csrf: csrf, type: tipo, nombre: nombre.trim()});
    fetch('/admin/productos/crear-catalogo', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body
    }).then(r => r.json()).then(res => {
        if (res.ok) {
            const map = {rubro: 'codrub', subrubro: 'codsub', departamento: 'codepar'};
            const sel = document.querySelector('select[name="' + map[tipo] + '"]');
            const opt = document.createElement('option');
            opt.value = res.id;
            opt.textContent = res.nombre;
            sel.appendChild(opt);
            ordenarSelect(sel);
            sel.value = String(res.id);
        } else {
            alert(res.error || 'Error al crear');
        }
    }).catch(() => alert('Error de conexión'));
}
document.querySelectorAll('select[name="codrub"], select[name="codsub"], select[name="codepar"]').forEach(ordenarSelect);
document.querySelectorAll('.calc-trigger').forEach(el => {
    el.addEventListener('input', autoCalcPrices);
    el.addEventListener('change', autoCalcPrices);
});
function parseImporte(v) {
    return parseFloat((v || '0').replace(',', '.')) || 0;
}
function ivaActualPct() {
    var ivaSel = document.querySelector('[name="iva"]');
    return parseFloat(ivaSel.options[ivaSel.selectedIndex].getAttribute('data-iva-pct')) || 0;
}
function costoNeto() {
    var c = parseImporte(document.querySelector('[name="precomp"]').value);
    var dtoEl = document.querySelector('[name="descuento_pct"]');
    var dto = dtoEl ? parseImporte(dtoEl.value) : 0;
    if (dto) c = c * (1 - dto / 100);
    var ivaPct = ivaActualPct();
    var ivaInc = document.getElementById('ivaIncCosto');
    if (ivaInc && ivaInc.checked && ivaPct > 0) c = c / (1 + ivaPct / 100);
    return c > 0 ? c : 0;
}
function costoNetoResumen() {
    var el = document.getElementById('costoNetoResumen');
    if (!el) return;
    var c = costoNeto();
    el.textContent = c > 0 ? 'Neto $' + c.toFixed(2) : '';
}
function autoCalcPrices() {
    var costo = costoNeto();
    var g1 = parseImporte(document.querySelector('[name="ganan1"]').value);
    var g2 = parseImporte(document.querySelector('[name="ganan2"]').value);
    var ivaPct = ivaActualPct();
    if (costo > 0 && g1 > 0) {
        document.querySelector('[name="precio_gross"]').value = (costo * (1 + g1 / 100) * (1 + ivaPct / 100)).toFixed(2);
    }
    if (costo > 0 && g2 > 0) {
        document.querySelector('[name="precio1_gross"]').value = (costo * (1 + g2 / 100) * (1 + ivaPct / 100)).toFixed(2);
    }
    costoNetoResumen();
}
function autoCalcMargins() {
    var costo = costoNeto();
    if (costo <= 0) { costoNetoResumen(); return; }
    var ivaPct = ivaActualPct();
    var p1 = parseImporte(document.querySelector('[name="precio_gross"]').value);
    var p2 = parseImporte(document.querySelector('[name="precio1_gross"]').value);
    if (p1 > 0) {
        document.querySelector('[name="ganan1"]').value = (((p1 / (1 + ivaPct / 100)) / costo - 1) * 100).toFixed(2);
    }
    if (p2 > 0) {
        document.querySelector('[name="ganan2"]').value = (((p2 / (1 + ivaPct / 100)) / costo - 1) * 100).toFixed(2);
    }
    costoNetoResumen();
}
document.getElementById('ivaIncCosto').addEventListener('change', autoCalcPrices);
// Al guardar, el costo viaja neto (con descuento e IVA aplicados, como en Precios rápidos).
document.querySelector('form[action="/admin/productos/crear"]').addEventListener('submit', function() {
    var inp = document.querySelector('[name="precomp"]');
    if (inp) inp.value = costoNeto().toFixed(2);
});
[document.querySelector('[name="precio_gross"]'), document.querySelector('[name="precio1_gross"]')].forEach(el => {
    if (!el) return;
    el.addEventListener('input', autoCalcMargins);
    el.addEventListener('change', autoCalcMargins);
});
</script>
