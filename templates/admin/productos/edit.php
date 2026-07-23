<?php
use Perfushopping\Web\Support\Format;

$product = $product ?? null;
$variants = $variants ?? [];
$rubros = $rubros ?? [];
$subrubros = $subrubros ?? [];
$departamentos = $departamentos ?? [];
$proveedores = $proveedores ?? [];

if (!$product):
?>
    <div class="alert alert-warning">Producto no encontrado.</div>
<?php
    return;
endif;

$selectedId = (int)$product['idprodu'];
$selectedIva = (float)($product['tiva'] ?? 0);
$priceGross = number_format((float)($product['precio'] ?? 0) * (1 + ($selectedIva / 100)), 2, '.', '');
$price1Gross = number_format((float)($product['precio1'] ?? 0) * (1 + ($selectedIva / 100)), 2, '.', '');
$mainImg = Format::uploadUrl((string)($product['imagen'] ?? ''));
$formatDate = static fn(string $d): string => (trim($d) === '' || $d === '0000-00-00') ? '-' : $d;

$selectedRubro = (int)($product['codrub'] ?? 0);
$selectedSubrubro = (int)($product['codsub'] ?? 0);
$selectedDepartamento = (int)($product['codepar'] ?? 0);
$selectedProveedor = (int)($product['codprove'] ?? 0);
$selectedRazon = '';
foreach ($proveedores as $prov) {
    if ((int)($prov['codprove'] ?? 0) === $selectedProveedor) {
        $selectedRazon = htmlspecialchars((string)($prov['razon'] ?? ''));
        break;
    }
}
?>
<style>.card-body{ padding:.6rem }.card-header{ padding:.4rem .6rem }.compact-form .form-label{ margin-bottom:.1rem }</style>
<nav aria-label="breadcrumb" class="mb-2">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/productos">Productos</a></li>
        <li class="breadcrumb-item active">#<?= $selectedId ?> - <?= htmlspecialchars(mb_substr((string)($product['produ'] ?? ''), 0, 40)) ?></li>
    </ol>
</nav>

<div class="card shadow-sm mb-2">
    <div class="card-body py-2">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div class="flex-grow-1">
                <div class="mb-1">
                    <input class="form-control fw-bold" name="produ" value="<?= htmlspecialchars((string)($product['produ'] ?? '')) ?>" form="save-form" />
                </div>
                <div class="d-flex flex-wrap gap-2 small text-muted">
                    <span>ID: <strong><?= $selectedId ?></strong></span>
                    <span>Código: <strong><?= htmlspecialchars((string)($product['codprodu'] ?? '-')) ?></strong></span>
                    <span>F.compra: <strong><?= htmlspecialchars($formatDate((string)($product['fecompra'] ?? ''))) ?></strong></span>
                    <span>IVA: <strong><?= htmlspecialchars((string)$selectedIva) ?>%</strong></span>
                </div>
            </div>
            <div class="d-flex gap-2">
                <?php if ((int)($product['enweb'] ?? 0) === 1): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/p/<?= $selectedId ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver público</a>
                <?php endif; ?>
                <form method="post" action="/admin/productos/delete" onsubmit="return confirm('Eliminar permanentemente este producto y todas sus variedades?')">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<form method="post" action="/admin/productos/save" id="save-form">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />

    <div class="row g-2 compact-form">
        <div class="col-lg-6">
            <div class="card shadow-sm mb-2">
                <div class="card-header bg-white fw-semibold">Categorización</div>
                <div class="card-body">
                    <div class="row g-1 mb-1">
                        <div class="col-md-6">
                            <label class="form-label small">Categoría</label>
                            <select class="form-select form-select-sm" name="codrub">
                                <option value="">— Sin categoría —</option>
                                <?php foreach ($rubros as $rub): ?>
                                    <option value="<?= (int)($rub['codrub'] ?? 0) ?>"<?= ((int)($rub['codrub'] ?? 0) === $selectedRubro) ? ' selected' : '' ?>><?= htmlspecialchars((string)($rub['nomrub'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Marca / Subrubro</label>
                            <select class="form-select form-select-sm" name="codsub">
                                <option value="">— Sin marca —</option>
                                <?php foreach ($subrubros as $sub): ?>
                                    <option value="<?= (int)($sub['codsub'] ?? 0) ?>"<?= ((int)($sub['codsub'] ?? 0) === $selectedSubrubro) ? ' selected' : '' ?>><?= htmlspecialchars((string)($sub['nomsub'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row g-1">
                        <div class="col-md-6">
                            <label class="form-label small">Departamento</label>
                            <select class="form-select form-select-sm" name="codepar">
                                <option value="">— Sin departamento —</option>
                                <?php foreach ($departamentos as $dep): ?>
                                    <option value="<?= (int)($dep['codepar'] ?? 0) ?>"<?= ((int)($dep['codepar'] ?? 0) === $selectedDepartamento) ? ' selected' : '' ?>><?= htmlspecialchars((string)($dep['nomdepar'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Proveedor</label>
                            <select class="form-select form-select-sm" name="codprove">
                                <option value="">— Sin proveedor —</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= (int)($prov['codprove'] ?? 0) ?>"<?= ((int)($prov['codprove'] ?? 0) === $selectedProveedor) ? ' selected' : '' ?>><?= htmlspecialchars((string)($prov['razon'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Costos y márgenes</div>
                <div class="card-body">
                    <div class="row g-1 mb-2">
                        <div class="col-md-4">
                            <label class="form-label small">Costo <span class="text-muted">(sin IVA)</span></label>
                            <input class="form-control form-control-sm calc-trigger" name="precomp" value="<?= htmlspecialchars((string)($product['precomp'] ?? '0')) ?>" inputmode="decimal" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Margen minorista <span class="text-muted">(%)</span></label>
                            <input class="form-control form-control-sm calc-trigger" name="ganan1" value="<?= htmlspecialchars((string)($product['ganan1'] ?? '0')) ?>" inputmode="decimal" />
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small">Margen mayorista <span class="text-muted">(%)</span></label>
                            <input class="form-control form-control-sm calc-trigger" name="ganan2" value="<?= htmlspecialchars((string)($product['ganan2'] ?? '0')) ?>" inputmode="decimal" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Precios y visibilidad</div>
                <div class="card-body">
                    <div class="row g-1 mb-1">
                        <div class="col-md-6">
                            <label class="form-label small">Precio minorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio_gross" value="<?= htmlspecialchars($priceGross) ?>" inputmode="decimal" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Precio mayorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio1_gross" value="<?= htmlspecialchars($price1Gross) ?>" inputmode="decimal" required />
                        </div>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small">Neto calculado</label>
                        <div class="form-control form-control-sm bg-light text-muted" style="cursor:default" readonly>
                            Minorista $<?= number_format((float)($product['precio'] ?? 0), 2, ',', '.') ?> | Mayorista $<?= number_format((float)($product['precio1'] ?? 0), 2, ',', '.') ?>
                        </div>
                    </div>

                    <div class="form-check mb-1">
                        <input class="form-check-input" type="checkbox" name="enweb" id="enweb" <?= ((int)($product['enweb'] ?? 0) === 1) ? 'checked' : '' ?> />
                        <label class="form-check-label" for="enweb">Visible en web</label>
                    </div>

                    <div class="mb-1">
                        <label class="form-label small">Descripción</label>
                        <textarea class="form-control form-control-sm" id="ai-description-field" name="observ" rows="5"><?= htmlspecialchars((string)($product['observ'] ?? '')) ?></textarea>
                    </div>

                    <div class="d-flex gap-2">
                        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
                        <button class="btn btn-outline-secondary btn-sm" type="button" data-ai-generate data-endpoint="/admin/productos/describe" data-csrf="<?= htmlspecialchars($csrf ?? '') ?>" data-idprodu="<?= $selectedId ?>" data-target="#ai-description-field">
                            <i class="bi bi-stars"></i> Generar IA
                        </button>
                        <span class="small text-muted align-self-center" data-ai-status></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<div class="row g-2 compact-form mt-2">
<div class="col-lg-6">
    <div class="card shadow-sm">
        <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
            <span>Imagen principal</span>
            <small class="text-muted"><?= htmlspecialchars((string)($product['imagen'] ?? 'Sin imagen')) ?></small>
        </div>
        <div class="card-body">
            <div class="text-center mb-3">
                <?php if ($mainImg !== ''): ?>
                    <img src="<?= htmlspecialchars($mainImg) ?>" alt="<?= htmlspecialchars((string)($product['produ'] ?? '')) ?>" style="max-height:180px;max-width:100%;border-radius:8px" />
                <?php else: ?>
                    <div class="bg-light rounded p-4 text-muted">Sin imagen principal</div>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-2">
                <form method="post" action="/admin/productos/main-image" enctype="multipart/form-data" class="flex-grow-1">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                    <input class="form-control form-control-sm mb-2" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" />
                    <button class="btn btn-outline-secondary btn-sm w-100" type="submit"><i class="bi bi-upload"></i> Subir</button>
                </form>
                <form method="post" action="/admin/productos/main-image/clear">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                    <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Quitar</button>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<div class="card shadow-sm mt-2">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Variedades</span>
        <div class="d-flex gap-1 align-items-center">
            <a class="btn btn-sm btn-outline-success" href="/admin/productos/etiquetas/<?= $selectedId ?>" target="_blank"><i class="bi bi-upc-scan"></i> Imprimir etiquetas</a>
            <span class="badge bg-secondary"><?= count($variants) ?></span>
        </div>
    </div>
    <div class="card-body">
        <?php
        $logisticsSeed = ['weight_g' => '', 'height_cm' => '', 'width_cm' => '', 'depth_cm' => '', 'product_category' => ''];
        foreach ($variants as $sv) {
            if ((int)($sv['weight_g'] ?? 0) > 0 || (int)($sv['height_cm'] ?? 0) > 0 || (int)($sv['width_cm'] ?? 0) > 0 || (int)($sv['depth_cm'] ?? 0) > 0 || trim((string)($sv['product_category'] ?? '')) !== '') {
                $logisticsSeed = ['weight_g' => (string)$sv['weight_g'], 'height_cm' => (string)$sv['height_cm'], 'width_cm' => (string)$sv['width_cm'], 'depth_cm' => (string)$sv['depth_cm'], 'product_category' => (string)$sv['product_category']];
                break;
            }
        }
        ?>
        <?php foreach ($variants as $variant):
            $variantId = (int)($variant['idcodgusto'] ?? 0);
            $vw = (int)($variant['weight_g'] ?? 0) > 0 ? (string)$variant['weight_g'] : $logisticsSeed['weight_g'];
            $vh = (int)($variant['height_cm'] ?? 0) > 0 ? (string)$variant['height_cm'] : $logisticsSeed['height_cm'];
            $vwi = (int)($variant['width_cm'] ?? 0) > 0 ? (string)$variant['width_cm'] : $logisticsSeed['width_cm'];
            $vd = (int)($variant['depth_cm'] ?? 0) > 0 ? (string)$variant['depth_cm'] : $logisticsSeed['depth_cm'];
            $vc = trim((string)($variant['product_category'] ?? '')) !== '' ? (string)$variant['product_category'] : $logisticsSeed['product_category'];
            $ean = \Perfushopping\Web\Support\Barcode::ean13((int)$variantId);
        ?>
            <div class="border rounded p-2 mb-2">
                <div class="d-flex justify-content-between align-items-start mb-1">
                    <div>
                        <h6 class="fw-bold mb-0"><?= htmlspecialchars((string)($variant['nomgusto'] ?? 'Sin nombre')) ?></h6>
                        <small class="text-muted">ID: <?= $variantId ?> · Código: <span class="codscan-val"><?= htmlspecialchars((string)($variant['codscan'] ?? '-')) ?></span> · EAN: <?= $ean ?> · Stock: <?= htmlspecialchars((string)($variant['stockact'] ?? '0')) ?><?= ((int)($variant['discont'] ?? 0) === 1) ? ' · <span class="text-danger">Discontinuado</span>' : '' ?></small>
                    </div>
                    <div class="d-flex gap-2 align-items-center">
                        <span class="badge bg-info"><?= is_array($variant['images'] ?? null) ? count($variant['images']) : 0 ?>/6 img</span>
                        <form method="post" action="/admin/productos/variant/delete" onsubmit="return confirm('Eliminar esta variedad?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                            <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:11px" type="submit"><i class="bi bi-x-lg"></i></button>
                        </form>
                    </div>
                </div>

                <?php if (!empty($variant['images']) && is_array($variant['images'])): ?>
                    <div class="d-flex flex-wrap gap-1 mb-1">
                        <?php foreach ($variant['images'] as $img): ?>
                            <div class="text-center" style="width:80px">
                                <img src="<?= htmlspecialchars(Format::uploadUrl((string)($img['rutaimg'] ?? ''))) ?>" alt="" style="width:70px;height:70px;object-fit:cover;border-radius:6px" />
                                <form method="post" action="/admin/productos/variant-images/delete" class="mt-1">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                                    <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                                    <input type="hidden" name="idimagen" value="<?= (int)($img['idimagen'] ?? 0) ?>" />
                                    <button class="btn btn-sm btn-outline-danger py-0 px-1" style="font-size:11px" type="submit">Quitar</button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="small text-muted mb-2">Sin imágenes</div>
                <?php endif; ?>

                <div class="row g-1 mb-1 align-items-end">
                    <div class="col-4">
                        <label class="small text-muted mb-0">Código de barras (EAN-13)</label>
                        <div class="input-group input-group-sm">
                            <input class="form-control form-control-sm codscan-input" data-variant="<?= $variantId ?>" value="<?= htmlspecialchars((string)($variant['codscan'] ?? '')) ?>" placeholder="EAN-13" />
                            <button class="btn btn-outline-secondary" type="button" onclick="generarEan(this, <?= $variantId ?>)"><i class="bi bi-upc-scan"></i></button>
                            <button class="btn btn-outline-success" type="button" onclick="guardarCodscan(this, <?= $variantId ?>, <?= $selectedId ?>)"><i class="bi bi-check"></i></button>
                        </div>
                    </div>
                    <form method="post" action="/admin/productos/variant-logistics" class="row g-1 col-8">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                        <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                        <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                        <div class="col-2">
                            <input class="form-control form-control-sm" name="weight_g" value="<?= htmlspecialchars($vw) ?>" placeholder="Peso g" />
                        </div>
                        <div class="col-2">
                            <input class="form-control form-control-sm" name="height_cm" value="<?= htmlspecialchars($vh) ?>" placeholder="Alto cm" />
                        </div>
                        <div class="col-2">
                            <input class="form-control form-control-sm" name="width_cm" value="<?= htmlspecialchars($vwi) ?>" placeholder="Ancho cm" />
                        </div>
                        <div class="col-2">
                            <input class="form-control form-control-sm" name="depth_cm" value="<?= htmlspecialchars($vd) ?>" placeholder="Largo cm" />
                        </div>
                        <div class="col-2">
                            <input class="form-control form-control-sm" name="product_category" value="<?= htmlspecialchars($vc) ?>" placeholder="Cat." />
                        </div>
                        <div class="col-2 d-grid">
                            <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-save"></i></button>
                        </div>
                    </form>
                </div>

                <div class="row g-1">
                    <div class="col-6">
                        <form method="post" action="/admin/productos/variant-images" enctype="multipart/form-data" class="d-flex gap-1">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                            <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                            <input class="form-control form-control-sm" type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp" />
                            <button class="btn btn-outline-secondary btn-sm flex-shrink-0" type="submit"><i class="bi bi-images"></i></button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$variants): ?>
            <div class="text-muted small mb-2">Este producto no tiene variedades.</div>
        <?php endif; ?>
    </div>
</div>

<script>
function generarEan(btn, idcodgusto) {
    const input = btn.closest('.input-group').querySelector('.codscan-input');
    const padded = String(idcodgusto).padStart(12, '9');
    let sum = 0;
    for (let i = 0; i < 12; i++) sum += (i % 2 === 0) ? parseInt(padded[i]) : parseInt(padded[i]) * 3;
    const check = (10 - (sum % 10)) % 10;
    input.value = padded + check;
}

function guardarCodscan(btn, idcodgusto, idprodu) {
    const input = btn.closest('.input-group').querySelector('.codscan-input');
    const codscan = input.value.trim();
    if (!codscan || codscan.length < 8) { alert('Código muy corto'); return; }
    const csrf = document.querySelector('input[name="_csrf"]').value;
    fetch('/admin/productos/variant/barcode', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({_csrf: csrf, idprodu: idprodu, idcodgusto: idcodgusto, codscan: codscan})
    }).then(r => r.json()).then(res => {
        if (res.ok) {
            document.querySelector('.codscan-val').textContent = codscan;
            btn.innerHTML = '<i class="bi bi-check-lg"></i>';
            setTimeout(() => btn.innerHTML = '<i class="bi bi-check"></i>', 1500);
        } else {
            alert(res.error || 'Error');
        }
    }).catch(() => alert('Error de conexión'));
}

document.querySelectorAll('[data-ai-generate]').forEach(btn => {
    btn.addEventListener('click', async function() {
        const endpoint = this.dataset.endpoint;
        const csrf = this.dataset.csrf;
        const idprodu = this.dataset.idprodu;
        const target = document.querySelector(this.dataset.target);
        const status = this.closest('div').querySelector('[data-ai-status]');
        if (!target || !status) return;
        status.textContent = 'Generando...';
        try {
            const resp = await fetch(endpoint, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: new URLSearchParams({_csrf: csrf, idprodu: idprodu})
            });
            const data = await resp.json();
            if (data.ok) {
                target.value = data.description;
                status.textContent = 'Listo.';
            } else {
                status.textContent = 'Error: ' + (data.error || 'desconocido');
            }
        } catch(e) {
            status.textContent = 'Error de conexión.';
        }
    });
});

document.querySelectorAll('.calc-trigger').forEach(function(el) {
    el.addEventListener('input', autoCalcPrices);
    el.addEventListener('change', autoCalcPrices);
});
function autoCalcPrices() {
    var costo = parseFloat((document.querySelector('[name="precomp"]').value || '0').replace(',', '.')) || 0;
    var g1 = parseFloat((document.querySelector('[name="ganan1"]').value || '0').replace(',', '.')) || 0;
    var g2 = parseFloat((document.querySelector('[name="ganan2"]').value || '0').replace(',', '.')) || 0;
    var ivaPct = <?= json_encode($selectedIva) ?>;
    if (costo > 0 && g1 > 0) {
        var neto1 = costo * (1 + g1 / 100);
        var gross1 = neto1 * (1 + ivaPct / 100);
        document.querySelector('[name="precio_gross"]').value = gross1.toFixed(2);
    }
    if (costo > 0 && g2 > 0) {
        var neto2 = costo * (1 + g2 / 100);
        var gross2 = neto2 * (1 + ivaPct / 100);
        document.querySelector('[name="precio1_gross"]').value = gross2.toFixed(2);
    }
}
</script>
