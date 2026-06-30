<?php
use Perfushopping\Web\Support\Format;

$q = (string)($q ?? '');
$codsub = (int)($codsub ?? 0);
$codrub = (int)($codrub ?? 0);
$brands = $brands ?? [];
$categories = $categories ?? [];
$products = $products ?? [];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Productos</h4>
        <p class="text-muted small">Busca productos, edita precios, visibilidad y más</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/productos/importar"><i class="bi bi-upload"></i> Importar</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/productos" class="row g-2">
            <div class="col-lg-5">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por id, nombre, código, variedad o codscan" />
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="codsub">
                    <option value="0">Todas las marcas</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)($brand['codsub'] ?? 0) ?>" <?= $codsub === (int)($brand['codsub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($brand['nomsub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="codrub">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)($category['codrub'] ?? 0) ?>" <?= $codrub === (int)($category['codrub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($category['nomrub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1 d-flex gap-1">
                <button class="btn btn-accent btn-sm flex-fill" type="submit"><i class="bi bi-search"></i></button>
                <?php if ($q !== '' || $codsub > 0 || $codrub > 0): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!$products): ?>
    <div class="alert alert-info">No se encontraron productos.</div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($products as $item):
            $itemId = (int)($item['idprodu'] ?? 0);
            $itemIva = (float)($item['tiva'] ?? 0);
            $itemGross = (float)($item['precio'] ?? 0) * (1 + ($itemIva / 100));
            $query = [];
            if ($q !== '') $query['q'] = $q;
            if ($codsub > 0) $query['codsub'] = (string)$codsub;
            if ($codrub > 0) $query['codrub'] = (string)$codrub;
            $href = '/admin/productos/' . $itemId . ($query ? '?' . http_build_query($query) : '');
        ?>
            <div class="col-lg-4 col-md-6">
                <a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <strong>#<?= $itemId ?></strong>
                                <span class="badge <?= ((int)($item['enweb'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>"><?= ((int)($item['enweb'] ?? 0) === 1) ? 'En web' : 'Oculto' ?></span>
                            </div>
                            <h6 class="card-title mb-1" style="font-size:14px"><?= htmlspecialchars((string)($item['produ'] ?? '')) ?></h6>
                            <div class="small text-muted">
                                <?= htmlspecialchars((string)($item['nomsub'] ?? '-')) ?> · <?= htmlspecialchars((string)($item['nomrub'] ?? '-')) ?>
                            </div>
                            <div class="d-flex justify-content-between mt-2 small">
                                <span><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($itemGross * 100))) ?></span>
                                <span><?= (int)($item['variants_count'] ?? 0) ?> var.</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
