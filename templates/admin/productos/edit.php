<?php
use Perfushopping\Web\Support\Format;

$product = $product ?? null;
$variants = $variants ?? [];

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
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/productos">Productos</a></li>
        <li class="breadcrumb-item active">#<?= $selectedId ?> - <?= htmlspecialchars(mb_substr((string)($product['produ'] ?? ''), 0, 40)) ?></li>
    </ol>
</nav>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h5 class="fw-bold mb-1"><?= htmlspecialchars((string)($product['produ'] ?? '')) ?></h5>
                <div class="d-flex flex-wrap gap-3 small text-muted">
                    <span>ID: <strong><?= $selectedId ?></strong></span>
                    <span>Código: <strong><?= htmlspecialchars((string)($product['codprodu'] ?? '-')) ?></strong></span>
                    <span>Marca: <strong><?= htmlspecialchars((string)($product['nomsub'] ?? '-')) ?></strong></span>
                    <span>Categoría: <strong><?= htmlspecialchars((string)($product['nomrub'] ?? '-')) ?></strong></span>
                    <span>F.compra: <strong><?= htmlspecialchars($formatDate((string)($product['fecompra'] ?? ''))) ?></strong></span>
                    <span>IVA: <strong><?= htmlspecialchars((string)$selectedIva) ?>%</strong></span>
                </div>
            </div>
            <?php if ((int)($product['enweb'] ?? 0) === 1): ?>
                <a class="btn btn-outline-secondary btn-sm" href="/p/<?= $selectedId ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver público</a>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Datos del producto</div>
            <div class="card-body">
                <form method="post" action="/admin/productos/save">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Precio minorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio_gross" value="<?= htmlspecialchars($priceGross) ?>" inputmode="decimal" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Precio mayorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio1_gross" value="<?= htmlspecialchars($price1Gross) ?>" inputmode="decimal" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Neto calculado</label>
                        <div class="form-control form-control-sm bg-light text-muted" style="cursor:default" readonly>
                            Minorista <?= number_format((float)($product['precio'] ?? 0), 2, '.', '') ?> | Mayorista <?= number_format((float)($product['precio1'] ?? 0), 2, '.', '') ?>
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="enweb" id="enweb" <?= ((int)($product['enweb'] ?? 0) === 1) ? 'checked' : '' ?> />
                        <label class="form-check-label" for="enweb">Visible en web</label>
                    </div>

                    <div class="mb-3">
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
                </form>
            </div>
        </div>
    </div>

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
                <form method="post" action="/admin/productos/main-image" enctype="multipart/form-data" class="mb-2">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                    <input class="form-control form-control-sm mb-2" type="file" name="images[]" accept=".jpg,.jpeg,.png,.webp" />
                    <button class="btn btn-outline-secondary btn-sm w-100" type="submit"><i class="bi bi-upload"></i> Subir</button>
                </form>
                <form method="post" action="/admin/productos/main-image/clear">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                    <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-trash"></i> Quitar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Variedades</span>
        <span class="badge bg-secondary"><?= count($variants) ?></span>
    </div>
    <div class="card-body">
        <?php if (!$variants): ?>
            <div class="text-muted small">Este producto no tiene variedades.</div>
        <?php else: ?>
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
            ?>
                <div class="border rounded p-3 mb-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <h6 class="fw-bold mb-0"><?= htmlspecialchars((string)($variant['nomgusto'] ?? '')) ?></h6>
                            <small class="text-muted">ID: <?= $variantId ?> · Código: <?= htmlspecialchars((string)($variant['codscan'] ?? '-')) ?> · Stock: <?= htmlspecialchars((string)($variant['stockact'] ?? '0')) ?><?= ((int)($variant['discont'] ?? 0) === 1) ? ' · <span class="text-danger">Discontinuado</span>' : '' ?></small>
                        </div>
                        <span class="badge bg-info"><?= is_array($variant['images'] ?? null) ? count($variant['images']) : 0 ?>/6 img</span>
                    </div>

                    <?php if (!empty($variant['images']) && is_array($variant['images'])): ?>
                        <div class="d-flex flex-wrap gap-2 mb-2">
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

                    <form method="post" action="/admin/productos/variant-logistics" class="row g-2 mb-2">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                        <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                        <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                        <div class="col-3">
                            <label class="small text-muted">Peso (g)</label>
                            <input class="form-control form-control-sm" name="weight_g" value="<?= htmlspecialchars($vw) ?>" />
                        </div>
                        <div class="col-3">
                            <label class="small text-muted">Alto (cm)</label>
                            <input class="form-control form-control-sm" name="height_cm" value="<?= htmlspecialchars($vh) ?>" />
                        </div>
                        <div class="col-3">
                            <label class="small text-muted">Ancho (cm)</label>
                            <input class="form-control form-control-sm" name="width_cm" value="<?= htmlspecialchars($vwi) ?>" />
                        </div>
                        <div class="col-3">
                            <label class="small text-muted">Largo (cm)</label>
                            <input class="form-control form-control-sm" name="depth_cm" value="<?= htmlspecialchars($vd) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="small text-muted">Categoría Correo</label>
                            <input class="form-control form-control-sm" name="product_category" value="<?= htmlspecialchars($vc) ?>" placeholder="Perfumería y cosmética" />
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <button class="btn btn-outline-secondary btn-sm w-100" type="submit"><i class="bi bi-save"></i> Logística</button>
                        </div>
                    </form>

                    <form method="post" action="/admin/productos/variant-images" enctype="multipart/form-data">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                        <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                        <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                        <div class="input-group input-group-sm">
                            <input class="form-control" type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp" />
                            <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-images"></i> Subir</button>
                        </div>
                    </form>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
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
</script>
