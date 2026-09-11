<?php
$product = $product ?? [];
$content = $content ?? [];
$relations = $relations ?? [];
$tags = $tags ?? [];
$videos = $videos ?? [];
$faqs = $faqs ?? [];
$taxonomyTerms = $taxonomyTerms ?? [];
$score = (int)($score ?? 0);
$csrf = $csrf ?? '';
$relationTypes = $relationTypes ?? [];
?>
<style>
.entity-chip{display:inline-flex;align-items:center;gap:6px;background:#f6f4ef;border:1px solid #e0dcd3;border-radius:20px;padding:4px 10px;margin:2px;font-size:13px}
.entity-chip button{border:none;background:transparent;color:#999;cursor:pointer;line-height:1}
.ai-pending{background:#fff8e1;border:1px dashed #f0ad4e}
</style>

<div class="card shadow-sm mb-3">
  <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
    <span>Score comercial</span>
    <span class="small text-muted"><?= $score ?>/100</span>
  </div>
  <div class="card-body">
    <div class="progress" style="height:18px">
      <div class="progress-bar bg-<?= $score >= 70 ? 'success' : ($score >= 40 ? 'warning' : 'danger') ?>" role="progressbar" style="width:<?= $score ?>%"><?= $score ?>%</div>
    </div>
  </div>
</div>

<div class="row g-2 compact-form">
  <div class="col-12">
    <div class="d-flex gap-2 align-items-center mb-2 flex-wrap">
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-ai-commercial" data-idprodu="<?= (int)($product['idprodu'] ?? 0) ?>" data-csrf="<?= htmlspecialchars($csrf) ?>">
        <i class="bi bi-stars"></i> Sugerir contenido con IA
      </button>
      <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-ai-tags" data-idprodu="<?= (int)($product['idprodu'] ?? 0) ?>" data-csrf="<?= htmlspecialchars($csrf) ?>">
        <i class="bi bi-tags"></i> Etiquetar con IA
      </button>
      <span class="small text-muted" id="ai-commercial-status"></span>
    </div>
  </div>

  <div class="col-md-6">
    <label class="form-label small">Beneficio principal</label>
    <textarea class="form-control form-control-sm" name="commercial[benefit]" rows="2"><?= htmlspecialchars((string)($content['benefit'] ?? '')) ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label small">Ideal para</label>
    <textarea class="form-control form-control-sm" name="commercial[ideal_for]" rows="2"><?= htmlspecialchars((string)($content['ideal_for'] ?? '')) ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label small">Problema que resuelve</label>
    <textarea class="form-control form-control-sm" name="commercial[problem]" rows="2"><?= htmlspecialchars((string)($content['problem'] ?? '')) ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label small">Resultados esperados</label>
    <textarea class="form-control form-control-sm" name="commercial[results]" rows="2"><?= htmlspecialchars((string)($content['results'] ?? '')) ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label small">Modo de uso</label>
    <textarea class="form-control form-control-sm" name="commercial[usage]" rows="2"><?= htmlspecialchars((string)($content['usage'] ?? '')) ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label small">Consejo Perfushopping</label>
    <textarea class="form-control form-control-sm" name="commercial[advice]" rows="2"><?= htmlspecialchars((string)($content['advice'] ?? '')) ?></textarea>
  </div>
  <div class="col-md-6">
    <label class="form-label small">CTA (llamado a la acción)</label>
    <input class="form-control form-control-sm" name="commercial[cta]" value="<?= htmlspecialchars((string)($content['cta'] ?? '')) ?>" />
  </div>
</div>

<hr class="my-3" />

<h6 class="fw-semibold mb-2">Tags comerciales</h6>
<div class="row g-2">
  <?php foreach ($taxonomyTerms as $key => $group): ?>
  <div class="col-md-4">
    <label class="form-label small text-capitalize"><?= htmlspecialchars((string)($group['taxonomy_label'] ?? $key)) ?></label>
    <select class="form-select form-select-sm" name="commercial[tags][<?= htmlspecialchars($key) ?>][]" multiple size="4">
      <?php foreach ($group['terms'] as $term): ?>
        <?php $selected = in_array($term['value'], $tags[$key] ?? [], true); ?>
        <option value="<?= htmlspecialchars((string)$term['value']) ?>" <?= $selected ? 'selected' : '' ?>><?= htmlspecialchars((string)$term['label']) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <?php endforeach; ?>
</div>

<hr class="my-3" />

<h6 class="fw-semibold mb-2">Productos relacionados</h6>
<div class="row g-2 mb-2">
  <div class="col-md-6">
    <input class="form-control form-control-sm" id="related-search" placeholder="Buscar producto..." autocomplete="off" />
    <div class="list-group small mt-1" id="related-results" style="max-height:160px;overflow:auto"></div>
  </div>
  <div class="col-md-6">
    <div id="related-selected" class="d-flex flex-wrap gap-1" data-next-index="<?= count($relations) ?>">
      <?php foreach ($relations as $idx => $rel): ?>
      <div class="entity-chip" data-id="<?= (int)$rel['related_id'] ?>">
        <?= htmlspecialchars((string)($rel['related_name'] ?? 'Producto')) ?>
        <select class="form-select form-select-sm border-0 bg-transparent py-0 px-1" style="width:auto;height:auto;font-size:12px" name="commercial[relations][<?= $idx ?>][type]">
          <?php foreach ($relationTypes as $k => $label): ?>
          <option value="<?= $k ?>" <?= ($rel['type'] ?? '') === $k ? 'selected' : '' ?>><?= $label ?></option>
          <?php endforeach; ?>
        </select>
        <input type="hidden" name="commercial[relations][<?= $idx ?>][related_id]" value="<?= (int)$rel['related_id'] ?>" />
        <button type="button" onclick="this.closest('.entity-chip').remove()">&times;</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<hr class="my-3" />

<h6 class="fw-semibold mb-2">Videos asociados</h6>
<div class="row g-2 mb-2">
  <div class="col-md-6">
    <input class="form-control form-control-sm" id="video-search" placeholder="Buscar video..." autocomplete="off" />
    <div class="list-group small mt-1" id="video-results" style="max-height:160px;overflow:auto"></div>
  </div>
  <div class="col-md-6">
    <div id="video-selected" class="d-flex flex-wrap gap-1">
      <?php foreach ($videos as $v): ?>
      <div class="entity-chip" data-id="<?= (int)$v['id'] ?>">
        <?= htmlspecialchars((string)($v['title'] ?? 'Video')) ?>
        <input type="hidden" name="commercial[videos][]" value="<?= (int)$v['id'] ?>" />
        <button type="button" onclick="this.closest('.entity-chip').remove()">&times;</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<hr class="my-3" />

<h6 class="fw-semibold mb-2">FAQs asociadas</h6>
<div class="row g-2 mb-2">
  <div class="col-md-6">
    <input class="form-control form-control-sm" id="faq-search" placeholder="Buscar FAQ..." autocomplete="off" />
    <div class="list-group small mt-1" id="faq-results" style="max-height:160px;overflow:auto"></div>
  </div>
  <div class="col-md-6">
    <div id="faq-selected" class="d-flex flex-wrap gap-1">
      <?php foreach ($faqs as $f): ?>
      <div class="entity-chip" data-id="<?= (int)$f['id'] ?>">
        <?= htmlspecialchars((string)($f['question'] ?? 'FAQ')) ?>
        <input type="hidden" name="commercial[faqs][]" value="<?= (int)$f['id'] ?>" />
        <button type="button" onclick="this.closest('.entity-chip').remove()">&times;</button>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<script>
(function(){
  var productId = <?= (int)($product['idprodu'] ?? 0) ?>;
  function setupSearch(inputId, resultsId, selectedId, type, render, hiddenName, single){
    var input = document.getElementById(inputId);
    var results = document.getElementById(resultsId);
    var selected = document.getElementById(selectedId);
    if (!input || !results || !selected) return;
    var timeout;
    input.addEventListener('input', function(){
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }
      timeout = setTimeout(function(){
        fetch('/admin/api/entity-search?type=' + type + '&q=' + encodeURIComponent(q) + '&exclude=' + productId)
          .then(r => r.json()).then(function(data){
            results.innerHTML = '';
            (data.results || []).forEach(function(item){
              var btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'list-group-item list-group-item-action py-1';
              btn.innerHTML = render(item);
              btn.addEventListener('click', function(){
                addChip(item);
                input.value = ''; results.innerHTML = '';
              });
              results.appendChild(btn);
            });
          });
      }, 250);
    });
    function addChip(item){
      if (single && selected.querySelector('[data-id="' + item.id + '"]')) return;
      var chip = document.createElement('div');
      chip.className = 'entity-chip';
      chip.setAttribute('data-id', item.id);
      chip.innerHTML = render(item) + '<input type="hidden" name="' + hiddenName + '" value="' + item.id + '" /><button type="button">&times;</button>';
      chip.querySelector('button').addEventListener('click', function(){ chip.remove(); });
      selected.appendChild(chip);
    }
  }

  var relationTypesHtml = <?= json_encode($relationTypes, JSON_UNESCAPED_UNICODE) ?>;
  // Productos relacionados con tipo de relación
  (function(){
    var input = document.getElementById('related-search');
    var results = document.getElementById('related-results');
    var selected = document.getElementById('related-selected');
    if (!input || !results || !selected) return;
    var timeout;
    input.addEventListener('input', function(){
      clearTimeout(timeout);
      var q = input.value.trim();
      if (q.length < 2) { results.innerHTML = ''; return; }
      timeout = setTimeout(function(){
        fetch('/admin/api/entity-search?type=product&q=' + encodeURIComponent(q) + '&exclude=' + productId)
          .then(r => r.json()).then(function(data){
            results.innerHTML = '';
            (data.results || []).forEach(function(item){
              var btn = document.createElement('button');
              btn.type = 'button';
              btn.className = 'list-group-item list-group-item-action py-1';
              btn.innerHTML = (item.imagen ? '<img src="/imagenes/' + item.imagen + '" style="width:24px;height:24px;object-fit:cover;border-radius:4px;margin-right:6px">' : '') + item.produ;
              btn.addEventListener('click', function(){
                if (selected.querySelector('[data-id="' + item.id + '"]')) return;
                var idx = parseInt(selected.dataset.nextIndex || '0');
                selected.dataset.nextIndex = idx + 1;
                var select = '<select class="form-select form-select-sm border-0 bg-transparent py-0 px-1" style="width:auto;height:auto;font-size:12px" name="commercial[relations][' + idx + '][type]">';
                for (var k in relationTypesHtml) select += '<option value="' + k + '">' + relationTypesHtml[k] + '</option>';
                select += '</select>';
                var chip = document.createElement('div');
                chip.className = 'entity-chip';
                chip.setAttribute('data-id', item.id);
                chip.innerHTML = item.produ + select + '<input type="hidden" name="commercial[relations][' + idx + '][related_id]" value="' + item.id + '" /><button type="button">&times;</button>';
                chip.querySelector('button').addEventListener('click', function(){ chip.remove(); });
                selected.appendChild(chip);
                input.value = ''; results.innerHTML = '';
              });
              results.appendChild(btn);
            });
          });
      }, 250);
    });
  })();

  setupSearch('video-search','video-results','video-selected','video', function(i){
    return (i.thumbnail ? '<img src="' + i.thumbnail + '" style="width:32px;height:18px;object-fit:cover;border-radius:2px;margin-right:6px">' : '') + i.title;
  }, 'commercial[videos][]');

  setupSearch('faq-search','faq-results','faq-selected','faq', function(i){
    return i.question;
  }, 'commercial[faqs][]');

  // IA comercial
  var aiBtn = document.getElementById('btn-ai-commercial');
  var aiTagsBtn = document.getElementById('btn-ai-tags');
  var aiStatus = document.getElementById('ai-commercial-status');
  if (aiBtn) {
    aiBtn.addEventListener('click', function(){
      aiStatus.textContent = 'Generando sugerencias...';
      fetch('/admin/productos/describe-commercial', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf=' + encodeURIComponent(aiBtn.dataset.csrf) + '&idprodu=' + encodeURIComponent(aiBtn.dataset.idprodu)
      }).then(r => r.json()).then(function(data){
        if (!data.ok) { aiStatus.textContent = data.error || 'Error'; return; }
        var c = data.content || {};
        ['benefit','ideal_for','problem','results','usage','advice','cta'].forEach(function(k){
          var el = document.querySelector('[name="commercial[' + k + ']"]');
          if (el && c[k]) el.value = c[k];
        });
        aiStatus.textContent = 'Sugerencias aplicadas. Revisá y guardá.';
      }).catch(function(e){ aiStatus.textContent = 'Error: ' + e.message; });
    });
  }
  if (aiTagsBtn) {
    aiTagsBtn.addEventListener('click', function(){
      aiStatus.textContent = 'Etiquetando con IA...';
      fetch('/admin/productos/ai-tags', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: '_csrf=' + encodeURIComponent(aiTagsBtn.dataset.csrf) + '&idprodu=' + encodeURIComponent(aiTagsBtn.dataset.idprodu)
      }).then(r => r.json()).then(function(data){
        if (!data.ok) { aiStatus.textContent = data.error || 'Error'; return; }
        aiStatus.textContent = (data.tags || []).length + ' etiquetas sugeridas. Recargá para verlas.';
      }).catch(function(e){ aiStatus.textContent = 'Error: ' + e.message; });
    });
  }
})();
</script>
