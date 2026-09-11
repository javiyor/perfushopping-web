<?php
$routine = $routine ?? null;
$seo = $seo ?? null;
$items = $items ?? [];
$needs = $needs ?? [];
$topics = $topics ?? [];
$videos = $videos ?? [];
$articles = $articles ?? [];
$allNeeds = $allNeeds ?? [];
$allTopics = $allTopics ?? [];
$id = $routine ? (int)$routine['id'] : 0;
?>
<style>
.entity-chip{display:inline-flex;align-items:center;gap:6px;background:#f6f4ef;border:1px solid #e0dcd3;border-radius:20px;padding:4px 10px;margin:2px;font-size:13px}
.entity-chip button{border:none;background:transparent;color:#999;cursor:pointer;line-height:1}
.routine-item{display:flex;gap:10px;align-items:flex-start;background:#f8f9fa;border:1px solid #e0dcd3;border-radius:8px;padding:10px;margin-bottom:8px}
</style>

<div class="page-title">
  <h2><?= $routine ? 'Editar rutina' : 'Nueva rutina' ?></h2>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="/admin/marketing/rutinas/guardar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />

      <div class="row g-3 compact-form">
        <div class="col-md-6">
          <label class="form-label small">Nombre</label>
          <input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)($routine['name'] ?? '')) ?>" required />
        </div>
        <div class="col-md-6">
          <label class="form-label small">Slug</label>
          <input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($routine['slug'] ?? '')) ?>" placeholder="se-genera-automaticamente" />
        </div>
        <div class="col-md-6">
          <label class="form-label small">Imagen</label>
          <input class="form-control form-control-sm" name="image" value="<?= htmlspecialchars((string)($routine['image'] ?? '')) ?>" />
        </div>
        <div class="col-md-2">
          <label class="form-label small">Orden</label>
          <input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)($routine['sort_order'] ?? 0) ?>" />
        </div>
        <div class="col-md-2">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="active" id="active" <?= (($routine['active'] ?? 1) == 1) ? 'checked' : '' ?> />
            <label class="form-check-label" for="active">Activo</label>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label small">Descripción</label>
          <textarea class="form-control form-control-sm" name="description" rows="3"><?= htmlspecialchars((string)($routine['description'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Problema que resuelve</label>
          <textarea class="form-control form-control-sm" name="problem" rows="2"><?= htmlspecialchars((string)($routine['problem'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Resultado esperado</label>
          <textarea class="form-control form-control-sm" name="expected_result" rows="2"><?= htmlspecialchars((string)($routine['expected_result'] ?? '')) ?></textarea>
        </div>
      </div>

      <hr />
      <h6 class="fw-semibold">SEO</h6>
      <div class="row g-3 compact-form">
        <div class="col-md-6"><input class="form-control form-control-sm" name="seo_title" value="<?= htmlspecialchars((string)($routine['seo_title'] ?? '')) ?>" placeholder="SEO title" /></div>
        <div class="col-md-6"><input class="form-control form-control-sm" name="seo_description" value="<?= htmlspecialchars((string)($routine['seo_description'] ?? '')) ?>" placeholder="Meta description" /></div>
        <div class="col-md-6"><input class="form-control form-control-sm" name="og_image" value="<?= htmlspecialchars((string)($routine['og_image'] ?? '')) ?>" placeholder="OG image" /></div>
      </div>

      <hr />
      <h6 class="fw-semibold">Productos de la rutina</h6>
      <div class="mb-2">
        <input class="form-control form-control-sm" id="product-search" placeholder="Buscar producto..." autocomplete="off" />
        <div class="list-group small mt-1" id="product-results" style="max-height:160px;overflow:auto;display:none"></div>
      </div>
      <div id="routine-items">
        <?php foreach ($items as $idx => $it): ?>
        <div class="routine-item" data-id="<?= (int)$it['product_id'] ?>">
          <div style="flex:0 0 60px">
            <label class="form-label small">Paso</label>
            <input type="number" class="form-control form-control-sm" name="items[step_order][]" value="<?= (int)$it['step_order'] ?>" />
          </div>
          <div style="flex:1">
            <label class="form-label small">Producto</label>
            <input type="hidden" name="items[product_id][]" value="<?= (int)$it['product_id'] ?>" />
            <div class="form-control form-control-sm bg-light"><?= htmlspecialchars((string)($it['produ'] ?? '')) ?></div>
          </div>
          <div style="flex:2">
            <label class="form-label small">Instrucciones</label>
            <input class="form-control form-control-sm" name="items[instructions][]" value="<?= htmlspecialchars((string)($it['instructions'] ?? '')) ?>" />
          </div>
          <div style="flex:0 0 80px">
            <div class="form-check mt-4">
              <input class="form-check-input" type="checkbox" name="items[optional][<?= $idx ?>]" value="1" <?= (int)$it['optional'] ? 'checked' : '' ?> />
              <label class="form-check-label small">Opcional</label>
            </div>
          </div>
          <div><button type="button" class="btn btn-sm btn-outline-danger mt-4" onclick="this.closest('.routine-item').remove()">&times;</button></div>
        </div>
        <?php endforeach; ?>
      </div>

      <hr />
      <h6 class="fw-semibold">Relaciones</h6>
      <div class="row g-3 compact-form">
        <div class="col-md-6">
          <label class="form-label small">Necesidades</label>
          <select class="form-select form-select-sm" name="needs[]" multiple size="4">
            <?php foreach ($allNeeds as $n): ?>
            <option value="<?= (int)$n['id'] ?>" <?= in_array((int)$n['id'], array_map(static fn($x)=>(int)$x['id'], $needs)) ? 'selected' : '' ?>><?= htmlspecialchars((string)$n['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Temas</label>
          <select class="form-select form-select-sm" name="topics[]" multiple size="4">
            <?php foreach ($allTopics as $t): ?>
            <option value="<?= (int)$t['id'] ?>" <?= in_array((int)$t['id'], array_map(static fn($x)=>(int)$x['id'], $topics)) ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <?php
      $relationTypes = [
        ['key'=>'videos','label'=>'Videos','items'=>$videos,'nameKey'=>'title','type'=>'video'],
        ['key'=>'articles','label'=>'Artículos','items'=>$articles,'nameKey'=>'title','type'=>'article'],
      ];
      foreach ($relationTypes as $rt):
      ?>
      <div class="mb-3 mt-3">
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

      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/marketing/rutinas">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  // Buscador de productos para items
  var input = document.getElementById('product-search');
  var results = document.getElementById('product-results');
  var container = document.getElementById('routine-items');
  var timeout;
  input.addEventListener('input', function(){
    clearTimeout(timeout);
    var q = input.value.trim();
    if (q.length < 2) { results.style.display='none'; results.innerHTML=''; return; }
    timeout = setTimeout(function(){
      fetch('/admin/api/entity-search?type=product&q=' + encodeURIComponent(q))
        .then(r => r.json()).then(function(data){
          results.innerHTML = '';
          results.style.display = (data.results && data.results.length) ? 'block' : 'none';
          (data.results || []).forEach(function(item){
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'list-group-item list-group-item-action py-1';
            btn.textContent = item.produ;
            btn.addEventListener('click', function(){
              if (container.querySelector('[data-id="' + item.id + '"]')) return;
              var idx = container.children.length;
              var div = document.createElement('div');
              div.className = 'routine-item';
              div.setAttribute('data-id', item.id);
              div.innerHTML = '<div style="flex:0 0 60px"><label class="form-label small">Paso</label><input type="number" class="form-control form-control-sm" name="items[step_order][]" value="' + (idx+1) + '" /></div>' +
                '<div style="flex:1"><label class="form-label small">Producto</label><input type="hidden" name="items[product_id][]" value="' + item.id + '" /><div class="form-control form-control-sm bg-light">' + item.produ + '</div></div>' +
                '<div style="flex:2"><label class="form-label small">Instrucciones</label><input class="form-control form-control-sm" name="items[instructions][]" placeholder="Modo de uso" /></div>' +
                '<div style="flex:0 0 80px"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="items[optional][' + idx + ']" value="1" /><label class="form-check-label small">Opcional</label></div></div>' +
                '<div><button type="button" class="btn btn-sm btn-outline-danger mt-4" onclick="this.closest(\'.routine-item\').remove()">&times;</button></div>';
              container.appendChild(div);
              input.value = ''; results.style.display='none'; results.innerHTML='';
            });
            results.appendChild(btn);
          });
        });
    }, 250);
  });

  // Buscador generico videos/articulos
  document.querySelectorAll('.entity-search').forEach(function(input){
    var type = input.dataset.type;
    var selectedId = input.dataset.target;
    var selected = document.getElementById(selectedId);
    var results = input.nextElementSibling;
    var nameKey = {video:'title',article:'title'}[type];
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
