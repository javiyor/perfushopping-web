<?php
use Perfushopping\Web\Support\Format;

$q = (string)($q ?? '');
$codsub = (int)($codsub ?? 0);
$codrub = (int)($codrub ?? 0);
$brands = $brands ?? [];
$categories = $categories ?? [];
$products = $products ?? [];
$selected = $selected ?? null;
$variants = $variants ?? [];
$csrf = (string)($csrf ?? '');

$formatGross = static function (float $net, float $ivaRate): string {
    return number_format($net * (1 + ($ivaRate / 100)), 2, '.', '');
};

$formatDate = static function (string $date): string {
    $date = trim($date);
    if ($date === '' || $date === '0000-00-00') {
        return '-';
    }
    return $date;
};
?>

<div class="page admin-products-header">
  <div>
    <h2 style="margin:0 0 8px">Productos / imagenes</h2>
    <p style="margin:0;color:rgba(246,244,239,0.72)">Busca, edita precios con IVA incluido, cambia visibilidad, carga imagen principal, gestiona galeria por variedad y genera descripciones con IA.</p>
  </div>
  <a class="btn secondary" href="/admin">Volver al admin</a>
</div>

<div class="page" style="margin-top:14px">
  <form class="admin-products-search" method="get" action="/admin/productos">
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por id, nombre, codigo, variedad o codscan" />
    <select name="codsub">
      <option value="0">Todas las marcas</option>
      <?php foreach ($brands as $brand): ?>
        <option value="<?= (int)($brand['codsub'] ?? 0) ?>" <?= $codsub === (int)($brand['codsub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($brand['nomsub'] ?? '')) ?></option>
      <?php endforeach; ?>
    </select>
    <select name="codrub">
      <option value="0">Todas las categorias</option>
      <?php foreach ($categories as $category): ?>
        <option value="<?= (int)($category['codrub'] ?? 0) ?>" <?= $codrub === (int)($category['codrub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($category['nomrub'] ?? '')) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Buscar</button>
    <?php if ($q !== '' || $codsub > 0 || $codrub > 0): ?>
      <a class="btn secondary" href="/admin/productos">Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<div class="admin-products-layout">
  <div class="page admin-products-list">
    <div class="admin-products-list-head">
      <h3 style="margin:0">Resultados</h3>
      <span><?= count($products) ?> item(s)</span>
    </div>

    <?php if (!$products): ?>
      <div class="notice" style="margin-top:12px">No se encontraron productos.</div>
    <?php else: ?>
      <div class="admin-product-cards">
        <?php foreach ($products as $item): ?>
          <?php
            $itemId = (int)($item['idprodu'] ?? 0);
            $itemIva = (float)($item['tiva'] ?? 0);
            $itemGross = (float)($item['precio'] ?? 0) * (1 + ($itemIva / 100));
            $itemSelected = $selected && (int)($selected['idprodu'] ?? 0) === $itemId;
          ?>
          <?php
            $query = [];
            if ($q !== '') {
                $query['q'] = $q;
            }
            if ($codsub > 0) {
                $query['codsub'] = (string)$codsub;
            }
            if ($codrub > 0) {
                $query['codrub'] = (string)$codrub;
            }
            $href = '/admin/productos/' . $itemId . ($query ? '?' . http_build_query($query) : '');
          ?>
          <a class="admin-product-card<?= $itemSelected ? ' selected' : '' ?>" href="<?= htmlspecialchars($href) ?>">
            <div class="admin-product-card-top">
              <strong>#<?= $itemId ?></strong>
              <span class="chip<?= ((int)($item['enweb'] ?? 0) === 1) ? ' gold' : '' ?>"><?= ((int)($item['enweb'] ?? 0) === 1) ? 'En web' : 'Oculto' ?></span>
            </div>
            <div class="admin-product-card-title"><?= htmlspecialchars((string)($item['produ'] ?? '')) ?></div>
            <div class="admin-product-card-meta">
              <span><?= htmlspecialchars((string)($item['nomsub'] ?? '-')) ?></span>
              <span><?= htmlspecialchars((string)($item['nomrub'] ?? '-')) ?></span>
            </div>
            <div class="admin-product-card-meta">
              <span>Precio: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($itemGross * 100))) ?></span>
              <span>Var.: <?= (int)($item['variants_count'] ?? 0) ?></span>
            </div>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="admin-product-detail">
    <?php if (!$selected): ?>
      <div class="page">
        <h3 style="margin:0 0 10px">Selecciona un producto</h3>
        <p style="margin:0;color:rgba(246,244,239,0.72)">Elegilo desde la lista para ver sus datos, editar precios, actualizar la descripcion y cargar imagenes.</p>
      </div>
    <?php else: ?>
      <?php
        $selectedId = (int)$selected['idprodu'];
        $selectedIva = (float)($selected['tiva'] ?? 0);
        $priceGross = $formatGross((float)($selected['precio'] ?? 0), $selectedIva);
        $price1Gross = $formatGross((float)($selected['precio1'] ?? 0), $selectedIva);
        $mainImg = Format::uploadUrl((string)($selected['imagen'] ?? ''));
      ?>

      <div class="page">
        <div class="admin-product-titlebar">
          <div>
            <h3 style="margin:0 0 6px"><?= htmlspecialchars((string)($selected['produ'] ?? '')) ?></h3>
            <div class="admin-inline-meta">
              <span>ID: <strong><?= $selectedId ?></strong></span>
              <span>Codigo: <strong><?= htmlspecialchars((string)($selected['codprodu'] ?? '-')) ?></strong></span>
              <span>Marca: <strong><?= htmlspecialchars((string)($selected['nomsub'] ?? '-')) ?></strong></span>
              <span>Categoria: <strong><?= htmlspecialchars((string)($selected['nomrub'] ?? '-')) ?></strong></span>
              <span>Fecompra: <strong><?= htmlspecialchars($formatDate((string)($selected['fecompra'] ?? ''))) ?></strong></span>
              <span>IVA: <strong><?= htmlspecialchars((string)$selectedIva) ?>%</strong></span>
            </div>
          </div>
          <?php if ((int)($selected['enweb'] ?? 0) === 1): ?>
            <a class="btn secondary" href="/p/<?= $selectedId ?>" target="_blank" rel="noopener">Ver producto publico</a>
          <?php endif; ?>
        </div>
      </div>

      <div class="page" style="margin-top:14px">
        <form method="post" action="/admin/productos/save" class="admin-product-form">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
          <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />

          <div class="admin-form-grid">
            <div>
              <label>ID producto</label>
              <input value="<?= $selectedId ?>" disabled />
            </div>
            <div>
              <label>Marca</label>
              <input value="<?= htmlspecialchars((string)($selected['nomsub'] ?? '')) ?>" disabled />
            </div>
            <div>
              <label>Categoria</label>
              <input value="<?= htmlspecialchars((string)($selected['nomrub'] ?? '')) ?>" disabled />
            </div>
            <div>
              <label>Fecompra</label>
              <input value="<?= htmlspecialchars($formatDate((string)($selected['fecompra'] ?? ''))) ?>" disabled />
            </div>
            <div>
              <label>Precio minorista (IVA incluido)</label>
              <input name="precio_gross" value="<?= htmlspecialchars($priceGross) ?>" inputmode="decimal" required />
            </div>
            <div>
              <label>Precio mayorista (IVA incluido)</label>
              <input name="precio1_gross" value="<?= htmlspecialchars($price1Gross) ?>" inputmode="decimal" required />
            </div>
            <div class="admin-inline-checkbox">
              <label>Visible en web</label>
              <label class="toggle-row"><input type="checkbox" name="enweb" <?= ((int)($selected['enweb'] ?? 0) === 1) ? 'checked' : '' ?> /> Mostrar producto</label>
            </div>
            <div>
              <label>Neto calculado</label>
              <input value="Minorista <?= htmlspecialchars(number_format((float)($selected['precio'] ?? 0), 2, '.', '')) ?> | Mayorista <?= htmlspecialchars(number_format((float)($selected['precio1'] ?? 0), 2, '.', '')) ?>" disabled />
            </div>
          </div>

          <label style="display:block;margin:14px 0 6px">Descripcion</label>
          <textarea id="ai-description-field" name="observ" rows="7" placeholder="Descripcion del producto"><?= htmlspecialchars((string)($selected['observ'] ?? '')) ?></textarea>

          <div class="admin-actions-row" style="margin-top:14px">
            <button class="btn" type="submit">Guardar cambios</button>
            <button
              class="btn secondary"
              type="button"
              data-ai-generate
              data-endpoint="/admin/productos/describe"
              data-csrf="<?= htmlspecialchars($csrf) ?>"
              data-idprodu="<?= $selectedId ?>"
              data-target="#ai-description-field"
            >Generar descripcion IA</button>
            <span class="admin-ai-status" data-ai-status></span>
          </div>
        </form>
      </div>

      <div class="admin-media-grid">
        <div class="page">
          <div class="admin-products-list-head">
            <h3 style="margin:0">Imagen principal</h3>
            <span><?= htmlspecialchars((string)($selected['imagen'] ?? 'Sin imagen')) ?></span>
          </div>

          <div class="admin-main-image-box">
            <?php if ($mainImg !== ''): ?>
              <img src="<?= htmlspecialchars($mainImg) ?>" alt="<?= htmlspecialchars((string)($selected['produ'] ?? '')) ?>" />
            <?php else: ?>
              <div class="admin-empty-image">Sin imagen principal</div>
            <?php endif; ?>
          </div>

          <form method="post" action="/admin/productos/main-image" enctype="multipart/form-data" class="admin-upload-form" style="margin-top:14px">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
            <label>Seleccionar una o varias</label>
            <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp" data-preview-container="#main-preview" />
            <div id="main-preview" class="admin-upload-preview"></div>
            <div class="admin-actions-row" style="margin-top:12px">
              <button class="btn" type="submit">Subir principal</button>
            </div>
          </form>

          <form method="post" action="/admin/productos/main-image/clear" style="margin-top:10px">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
            <button class="btn danger" type="submit">Quitar imagen principal</button>
          </form>
        </div>

        <div class="page">
          <div class="admin-products-list-head">
            <h3 style="margin:0">Variedades</h3>
            <span><?= count($variants) ?> item(s)</span>
          </div>

          <?php if (!$variants): ?>
            <div class="notice" style="margin-top:12px">Este producto no tiene variedades.</div>
          <?php else: ?>
            <div class="admin-variants-stack">
              <?php
                $logisticsSeed = ['weight_g' => '', 'height_cm' => '', 'width_cm' => '', 'depth_cm' => '', 'product_category' => ''];
                foreach ($variants as $seedVariant) {
                    if ((int)($seedVariant['weight_g'] ?? 0) > 0 || (int)($seedVariant['height_cm'] ?? 0) > 0 || (int)($seedVariant['width_cm'] ?? 0) > 0 || (int)($seedVariant['depth_cm'] ?? 0) > 0 || trim((string)($seedVariant['product_category'] ?? '')) !== '') {
                        $logisticsSeed = [
                            'weight_g' => (string)($seedVariant['weight_g'] ?? ''),
                            'height_cm' => (string)($seedVariant['height_cm'] ?? ''),
                            'width_cm' => (string)($seedVariant['width_cm'] ?? ''),
                            'depth_cm' => (string)($seedVariant['depth_cm'] ?? ''),
                            'product_category' => (string)($seedVariant['product_category'] ?? ''),
                        ];
                        break;
                    }
                }
              ?>
              <?php foreach ($variants as $variant): ?>
                <?php $variantId = (int)($variant['idcodgusto'] ?? 0); ?>
                <?php
                  $variantWeight = (int)($variant['weight_g'] ?? 0) > 0 ? (string)$variant['weight_g'] : $logisticsSeed['weight_g'];
                  $variantHeight = (int)($variant['height_cm'] ?? 0) > 0 ? (string)$variant['height_cm'] : $logisticsSeed['height_cm'];
                  $variantWidth = (int)($variant['width_cm'] ?? 0) > 0 ? (string)$variant['width_cm'] : $logisticsSeed['width_cm'];
                  $variantDepth = (int)($variant['depth_cm'] ?? 0) > 0 ? (string)$variant['depth_cm'] : $logisticsSeed['depth_cm'];
                  $variantCategory = trim((string)($variant['product_category'] ?? '')) !== '' ? (string)$variant['product_category'] : $logisticsSeed['product_category'];
                ?>
                <div class="variant admin-variant-card">
                  <div class="admin-variant-head">
                    <div>
                      <h4><?= htmlspecialchars((string)($variant['nomgusto'] ?? '')) ?></h4>
                      <div class="meta">ID gusto: <?= $variantId ?> &middot; Codscan: <?= htmlspecialchars((string)($variant['codscan'] ?? '-')) ?> &middot; Stock: <?= htmlspecialchars((string)($variant['stockact'] ?? '0')) ?><?= ((int)($variant['discont'] ?? 0) === 1) ? ' &middot; Discontinuado' : '' ?></div>
                    </div>
                    <span class="chip"><?= is_array($variant['images'] ?? null) ? count($variant['images']) : 0 ?>/6</span>
                  </div>

                  <div class="admin-variant-gallery">
                    <?php if (!empty($variant['images']) && is_array($variant['images'])): ?>
                      <?php foreach ($variant['images'] as $image): ?>
                        <div class="admin-variant-image-item">
                          <img src="<?= htmlspecialchars(Format::uploadUrl((string)($image['rutaimg'] ?? ''))) ?>" alt="" />
                          <div class="admin-variant-image-name"><?= htmlspecialchars((string)($image['rutaimg'] ?? '')) ?></div>
                          <form method="post" action="/admin/productos/variant-images/delete">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                            <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                            <input type="hidden" name="idimagen" value="<?= (int)($image['idimagen'] ?? 0) ?>" />
                            <button class="btn danger" type="submit">Quitar</button>
                          </form>
                        </div>
                      <?php endforeach; ?>
                    <?php else: ?>
                      <div class="admin-empty-image">Sin galeria cargada</div>
                    <?php endif; ?>
                  </div>

                  <form method="post" action="/admin/productos/variant-logistics" class="admin-upload-form" style="margin-top:12px">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                    <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                    <div class="admin-form-grid" style="margin-bottom:12px">
                      <div>
                        <label>Peso (gramos)</label>
                        <input name="weight_g" value="<?= htmlspecialchars($variantWeight) ?>" inputmode="numeric" />
                      </div>
                      <div>
                        <label>Alto (cm)</label>
                        <input name="height_cm" value="<?= htmlspecialchars($variantHeight) ?>" inputmode="numeric" />
                      </div>
                      <div>
                        <label>Ancho (cm)</label>
                        <input name="width_cm" value="<?= htmlspecialchars($variantWidth) ?>" inputmode="numeric" />
                      </div>
                      <div>
                        <label>Largo (cm)</label>
                        <input name="depth_cm" value="<?= htmlspecialchars($variantDepth) ?>" inputmode="numeric" />
                      </div>
                      <div>
                        <label>Categoria Correo</label>
                        <input name="product_category" value="<?= htmlspecialchars($variantCategory) ?>" placeholder="Perfumeria y cosmetica" />
                      </div>
                    </div>
                    <div class="admin-actions-row" style="margin-top:12px">
                      <button class="btn secondary" type="submit">Guardar logistica</button>
                    </div>
                  </form>

                  <form method="post" action="/admin/productos/variant-images" enctype="multipart/form-data" class="admin-upload-form" style="margin-top:12px">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                    <input type="hidden" name="idprodu" value="<?= $selectedId ?>" />
                    <input type="hidden" name="idcodgusto" value="<?= $variantId ?>" />
                    <label>Seleccionar varias imagenes</label>
                    <input type="file" name="images[]" multiple accept=".jpg,.jpeg,.png,.webp" data-preview-container="#variant-preview-<?= $variantId ?>" />
                    <div id="variant-preview-<?= $variantId ?>" class="admin-upload-preview"></div>
                    <div class="admin-actions-row" style="margin-top:12px">
                      <button class="btn secondary" type="submit">Subir a variedad</button>
                    </div>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
