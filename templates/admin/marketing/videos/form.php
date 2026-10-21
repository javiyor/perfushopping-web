<?php
$video = $video ?? null;
$seo = $seo ?? null;
$csrf = $csrf ?? '';
$id = $video ? (int)$video['id'] : 0;
$selectedProducts = $selectedProducts ?? [];
?>
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/admin/marketing/videos">Videos</a></li>
    <li class="breadcrumb-item active"><?= $id ? 'Editar' : 'Nuevo' ?></li>
  </ol>
</nav>

<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold"><?= $id ? 'Editar video' : 'Nuevo video' ?></div>
  <div class="card-body">
    <form method="post" action="/admin/marketing/videos/guardar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />

      <div class="row g-2">
        <div class="col-md-8">
          <label class="form-label small">Título</label>
          <input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)($video['title'] ?? '')) ?>" required />
        </div>
        <div class="col-md-4">
          <label class="form-label small">Slug (opcional)</label>
          <input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($video['slug'] ?? '')) ?>" />
        </div>
        <div class="col-md-8">
          <label class="form-label small">URL de YouTube</label>
          <input class="form-control form-control-sm" name="url" id="video-url" value="<?= htmlspecialchars((string)($video['url'] ?? '')) ?>" required />
          <div class="form-text">Acepta enlaces normales, Shorts, Live y youtu.be.</div>
        </div>
        <div class="col-md-2">
          <label class="form-label small">Duración (seg)</label>
          <input class="form-control form-control-sm" name="duration" type="number" value="<?= (int)($video['duration'] ?? 0) ?>" />
        </div>
        <div class="col-md-2">
          <label class="form-label small">Instructor</label>
          <input class="form-control form-control-sm" name="instructor" value="<?= htmlspecialchars((string)($video['instructor'] ?? '')) ?>" />
        </div>
        <div class="col-md-8">
          <label class="form-label small">Thumbnail (opcional)</label>
          <input class="form-control form-control-sm" name="thumbnail" id="video-thumb" value="<?= htmlspecialchars((string)($video['thumbnail'] ?? '')) ?>" />
        </div>
        <div class="col-md-4 d-flex align-items-end gap-3 pb-1">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="active" id="v_active" <?= ($video === null || (int)($video['active'] ?? 1)) ? 'checked' : '' ?> />
            <label class="form-check-label small" for="v_active">Activo</label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="featured" id="v_featured" <?= (int)($video['featured'] ?? 0) ? 'checked' : '' ?> />
            <label class="form-check-label small" for="v_featured">Destacado</label>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label small">Descripción corta</label>
          <textarea class="form-control form-control-sm" name="description_short" rows="2"><?= htmlspecialchars((string)($video['description_short'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label small">Descripción larga</label>
          <textarea class="form-control form-control-sm" name="description_long" rows="4"><?= htmlspecialchars((string)($video['description_long'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label small">Productos relacionados (enlace de compra)</label>
          <input type="search" class="form-control form-control-sm" id="product-search" placeholder="Buscar producto por nombre o código" autocomplete="off" />
          <div id="product-search-results" class="list-group mt-1" style="max-width:720px"></div>
          <div id="selected-video-products" class="d-flex flex-wrap gap-2 mt-2">
            <?php foreach ($selectedProducts as $product): ?>
            <span class="badge bg-primary-subtle text-primary-emphasis border d-inline-flex align-items-center gap-2" data-product-id="<?= (int)$product['idprodu'] ?>">
              <input type="hidden" name="product_ids[]" value="<?= (int)$product['idprodu'] ?>" />
              <?= htmlspecialchars((string)$product['produ']) ?>
              <button type="button" class="btn-close" aria-label="Quitar producto" style="font-size:.55rem" onclick="this.closest('[data-product-id]').remove()"></button>
            </span>
            <?php endforeach; ?>
          </div>
          <div class="form-text">En la página del video aparecerá un botón para abrir la ficha y comprar cada producto vinculado.</div>
        </div>
        <div class="col-md-6">
          <label class="form-label small">SEO Title</label>
          <input class="form-control form-control-sm" name="seo_title" value="<?= htmlspecialchars((string)($seo['title'] ?? ($video['seo_title'] ?? ''))) ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label small">SEO Meta Description</label>
          <input class="form-control form-control-sm" name="seo_description" value="<?= htmlspecialchars((string)($seo['meta_description'] ?? ($video['seo_description'] ?? ''))) ?>" />
        </div>
      </div>

      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="/admin/marketing/videos" class="btn btn-outline-secondary btn-sm">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  var url = document.getElementById('video-url');
  var thumb = document.getElementById('video-thumb');
  if (url && thumb) url.addEventListener('blur', function(){
    if (thumb.value !== '') return;
    var m = url.value.match(/(?:youtube(?:-nocookie)?\.com\/(?:watch\?(?:[^#]*&)?v=|embed\/|shorts\/|live\/|v\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i);
    if (m) thumb.value = 'https://img.youtube.com/vi/' + m[1] + '/mqdefault.jpg';
  });

  var search = document.getElementById('product-search');
  var results = document.getElementById('product-search-results');
  var selected = document.getElementById('selected-video-products');
  var timer;
  if (!search || !results || !selected) return;
  search.addEventListener('input', function(){
    clearTimeout(timer);
    var q = search.value.trim();
    if (q.length < 2) { results.replaceChildren(); return; }
    timer = setTimeout(function(){
      fetch('/admin/marketing/videos/buscar-productos?q=' + encodeURIComponent(q), {headers:{'Accept':'application/json'}})
        .then(function(response){ if (!response.ok) throw new Error('No se pudo buscar'); return response.json(); })
        .then(function(data){
          results.replaceChildren();
          (data.items || []).forEach(function(product){
            if (selected.querySelector('[data-product-id="' + product.idprodu + '"]')) return;
            var button = document.createElement('button');
            button.type = 'button'; button.className = 'list-group-item list-group-item-action small';
            button.textContent = product.produ + ' · #' + product.idprodu;
            button.addEventListener('click', function(){
              var badge = document.createElement('span');
              badge.className = 'badge bg-primary-subtle text-primary-emphasis border d-inline-flex align-items-center gap-2';
              badge.dataset.productId = product.idprodu;
              var hidden = document.createElement('input'); hidden.type = 'hidden'; hidden.name = 'product_ids[]'; hidden.value = product.idprodu;
              var label = document.createTextNode(product.produ + ' ');
              var remove = document.createElement('button'); remove.type = 'button'; remove.className = 'btn-close'; remove.setAttribute('aria-label','Quitar producto'); remove.style.fontSize = '.55rem';
              remove.addEventListener('click', function(){ badge.remove(); });
              badge.append(hidden, label, remove); selected.appendChild(badge);
              button.remove();
            });
            results.appendChild(button);
          });
        }).catch(function(){ results.replaceChildren(); });
    }, 250);
  });
})();
</script>
