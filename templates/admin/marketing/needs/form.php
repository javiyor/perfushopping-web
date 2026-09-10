<?php
$need = $need ?? null;
$seo = $seo ?? null;
$products = $products ?? [];
$videos = $videos ?? [];
$articles = $articles ?? [];
$faqs = $faqs ?? [];
$topics = $topics ?? [];
$allTopics = $allTopics ?? [];
$id = $need ? (int)$need['id'] : 0;
?>
<style>
.entity-chip{display:inline-flex;align-items:center;gap:6px;background:#f6f4ef;border:1px solid #e0dcd3;border-radius:20px;padding:4px 10px;margin:2px;font-size:13px}
.entity-chip button{border:none;background:transparent;color:#999;cursor:pointer;line-height:1}
</style>

<div class="page-title">
  <h2><?= $need ? 'Editar necesidad' : 'Nueva necesidad' ?></h2>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="/admin/marketing/necesidades/guardar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />

      <div class="row g-3 compact-form">
        <div class="col-md-6">
          <label class="form-label small">Nombre</label>
          <input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)($need['name'] ?? '')) ?>" required />
        </div>
        <div class="col-md-6">
          <label class="form-label small">Slug</label>
          <input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($need['slug'] ?? '')) ?>" placeholder="se-genera-automaticamente" />
        </div>
        <div class="col-md-4">
          <label class="form-label small">Icono (clase Bootstrap)</label>
          <input class="form-control form-control-sm" name="icon" value="<?= htmlspecialchars((string)($need['icon'] ?? '')) ?>" placeholder="bi-heart" />
        </div>
        <div class="col-md-4">
          <label class="form-label small">Imagen</label>
          <input class="form-control form-control-sm" name="image" value="<?= htmlspecialchars((string)($need['image'] ?? '')) ?>" placeholder="/uploads/necesidad.jpg" />
        </div>
        <div class="col-md-4">
          <label class="form-label small">Orden</label>
          <input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)($need['sort_order'] ?? 0) ?>" />
        </div>
        <div class="col-md-6">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="active" id="active" <?= (($need['active'] ?? 1) == 1) ? 'checked' : '' ?> />
            <label class="form-check-label" for="active">Activo</label>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label small">Descripción corta</label>
          <textarea class="form-control form-control-sm" name="description_short" rows="2"><?= htmlspecialchars((string)($need['description_short'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label small">Descripción larga</label>
          <textarea class="form-control form-control-sm" name="description_long" rows="4"><?= htmlspecialchars((string)($need['description_long'] ?? '')) ?></textarea>
        </div>
      </div>

      <hr />
      <h6 class="fw-semibold">SEO</h6>
      <div class="row g-3 compact-form">
        <div class="col-md-6">
          <label class="form-label small">SEO title</label>
          <input class="form-control form-control-sm" name="seo_title" value="<?= htmlspecialchars((string)($need['seo_title'] ?? '')) ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label small">Meta description</label>
          <input class="form-control form-control-sm" name="seo_description" value="<?= htmlspecialchars((string)($need['seo_description'] ?? '')) ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label small">OG image</label>
          <input class="form-control form-control-sm" name="og_image" value="<?= htmlspecialchars((string)($need['og_image'] ?? '')) ?>" />
        </div>
      </div>

      <hr />
      <h6 class="fw-semibold">Relaciones</h6>
      <?php
      $relationTypes = [
        ['key'=>'products','label'=>'Productos','items'=>$products,'nameKey'=>'produ','type'=>'product'],
        ['key'=>'videos','label'=>'Videos','items'=>$videos,'nameKey'=>'title','type'=>'video'],
        ['key'=>'articles','label'=>'Artículos','items'=>$articles,'nameKey'=>'title','type'=>'article'],
        ['key'=>'faqs','label'=>'FAQs','items'=>$faqs,'nameKey'=>'question','type'=>'faq'],
      ];
      foreach ($relationTypes as $rt):
      ?>
      <div class="mb-3">
        <label class="form-label small"><?= $rt['label'] ?></label>
        <input class="form-control form-control-sm entity-search" data-type="<?= $rt['type'] ?>" data-target="<?= $rt['key'] ?>-selected" placeholder="Buscar <?= strtolower($rt['label']) ?>..." autocomplete="off" />
        <div class="list-group small mt-1 entity-results" style="max-height:160px;overflow:auto;display:none"></div>
        <div id="<?= $rt['key'] ?>-selected" class="d-flex flex-wrap gap-1 mt-2">
          <?php foreach ($rt['items'] as $item): ?>
          <div class="entity-chip" data-id="<?= (int)$item['id'] ?>">
            <?= htmlspecialchars((string)($item[$rt['nameKey']] ?? 'Item')) ?>
            <input type="hidden" name="<?= $rt['key'] ?>[]" value="<?= (int)$item['id'] ?>" />
            <button type="button">&times;</button>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="mb-3">
        <label class="form-label small">Temas relacionados</label>
        <select class="form-select form-select-sm" name="topics[]" multiple size="5">
          <?php foreach ($allTopics as $t): ?>
          <?php $selected = false;
          foreach ($topics as $sel) { if ((int)$sel['id'] === (int)$t['id']) { $selected = true; break; } }
          ?>
          <option value="<?= (int)$t['id'] ?>" <?= $selected ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/marketing/necesidades">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  document.querySelectorAll('.entity-search').forEach(function(input){
    var type = input.dataset.type;
    var selectedId = input.dataset.target;
    var selected = document.getElementById(selectedId);
    var results = input.nextElementSibling;
    var nameKey = {product:'produ',video:'title',article:'title',faq:'question'}[type];
    var timeout;
    input.addEventListener('input', function(){
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { results.style.display='none'; results.innerHTML=''; return; }
      timeout = setTimeout(function(){
        fetch('/admin/api/entity-search?type=' + type + '&q=' + encodeURIComponent(q))
          .then(r => r.json()).then(function(data){
            results.innerHTML = '';
            results.style.display = (data.results && data.results.length) ? 'block' : 'none';
            (data.results || []).forEach(function(item){
              var btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'list-group-item list-group-item-action py-1';
              btn.textContent = item[nameKey] || ('#' + item.id);
              btn.addEventListener('click', function(){
                if (selected.querySelector('[data-id="' + item.id + '"]')) return;
                var chip = document.createElement('div');
                chip.className = 'entity-chip';
                chip.setAttribute('data-id', item.id);
                chip.innerHTML = (item[nameKey] || ('#' + item.id)) + '<input type="hidden" name="' + selectedId.replace('-selected','') + '[]" value="' + item.id + '" /><button type="button">&times;</button>';
                chip.querySelector('button').addEventListener('click', function(){ chip.remove(); });
                selected.appendChild(chip);
                input.value = ''; results.style.display='none'; results.innerHTML='';
              });
              results.appendChild(btn);
            });
          });
      }, 250);
    });
  });
  document.querySelectorAll('.entity-chip button').forEach(function(btn){
    btn.addEventListener('click', function(){ btn.closest('.entity-chip').remove(); });
  });
})();
</script>
