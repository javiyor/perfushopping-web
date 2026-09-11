<?php
$block = $block ?? null;
$id = $block ? (int)$block['id'] : 0;
$settings = $block['settings'] ?? [];
?>
<style>
.settings-group{display:none}
.settings-group.active{display:block}
</style>

<div class="page-title">
  <h2><?= $block ? 'Editar bloque' : 'Nuevo bloque' ?></h2>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="/admin/marketing/home/guardar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />

      <div class="row g-3 compact-form">
        <div class="col-md-4">
          <label class="form-label small">Tipo de bloque</label>
          <select class="form-select form-select-sm" name="block_type" id="blockType" required>
            <option value="">Seleccionar...</option>
            <option value="hero" <?= (($block['block_type'] ?? '') === 'hero') ? 'selected' : '' ?>>Hero</option>
            <option value="needs_grid" <?= (($block['block_type'] ?? '') === 'needs_grid') ? 'selected' : '' ?>>Grilla de necesidades</option>
            <option value="videos_grid" <?= (($block['block_type'] ?? '') === 'videos_grid') ? 'selected' : '' ?>>Grilla de videos</option>
            <option value="articles_grid" <?= (($block['block_type'] ?? '') === 'articles_grid') ? 'selected' : '' ?>>Grilla de artículos</option>
            <option value="featured_products" <?= (($block['block_type'] ?? '') === 'featured_products') ? 'selected' : '' ?>>Productos destacados</option>
            <option value="routines_grid" <?= (($block['block_type'] ?? '') === 'routines_grid') ? 'selected' : '' ?>>Grilla de rutinas</option>
            <option value="promo_banner" <?= (($block['block_type'] ?? '') === 'promo_banner') ? 'selected' : '' ?>>Banner promocional</option>
            <option value="categories_grid" <?= (($block['block_type'] ?? '') === 'categories_grid') ? 'selected' : '' ?>>Grilla de categorías</option>
            <option value="custom_html" <?= (($block['block_type'] ?? '') === 'custom_html') ? 'selected' : '' ?>>HTML libre</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Título</label>
          <input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)($block['title'] ?? '')) ?>" />
        </div>
        <div class="col-md-2">
          <label class="form-label small">Orden</label>
          <input type="number" class="form-control form-control-sm" name="position" value="<?= (int)($block['position'] ?? 0) ?>" />
        </div>
        <div class="col-md-2">
          <div class="form-check mt-4">
            <input class="form-check-input" type="checkbox" name="active" id="active" <?= (($block['active'] ?? 1) == 1) ? 'checked' : '' ?> />
            <label class="form-check-label" for="active">Activo</label>
          </div>
        </div>
        <div class="col-md-6">
          <label class="form-label small">Visible desde</label>
          <input type="datetime-local" class="form-control form-control-sm" name="start_at" value="<?= htmlspecialchars((string)($block['start_at'] ?? '')) ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label small">Visible hasta</label>
          <input type="datetime-local" class="form-control form-control-sm" name="end_at" value="<?= htmlspecialchars((string)($block['end_at'] ?? '')) ?>" />
        </div>
      </div>

      <hr />
      <h6 class="fw-semibold">Configuración del bloque</h6>

      <div id="hero-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-6"><label class="form-label small">Heading</label><input class="form-control form-control-sm" name="settings[heading]" value="<?= htmlspecialchars((string)($settings['heading'] ?? '')) ?>" /></div>
        <div class="col-md-6"><label class="form-label small">Subheading</label><input class="form-control form-control-sm" name="settings[subheading]" value="<?= htmlspecialchars((string)($settings['subheading'] ?? '')) ?>" /></div>
        <div class="col-md-4"><label class="form-label small">Texto CTA</label><input class="form-control form-control-sm" name="settings[cta_text]" value="<?= htmlspecialchars((string)($settings['cta_text'] ?? '')) ?>" /></div>
        <div class="col-md-4"><label class="form-label small">URL CTA</label><input class="form-control form-control-sm" name="settings[cta_url]" value="<?= htmlspecialchars((string)($settings['cta_url'] ?? '')) ?>" /></div>
        <div class="col-md-4"><label class="form-label small">Imagen</label><input class="form-control form-control-sm" name="settings[image]" value="<?= htmlspecialchars((string)($settings['image'] ?? '')) ?>" /></div>
      </div>

      <div id="needs_grid-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-4"><label class="form-label small">Cantidad</label><input type="number" class="form-control form-control-sm" name="settings[limit]" value="<?= (int)($settings['limit'] ?? 6) ?>" /></div>
      </div>

      <div id="videos_grid-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-4"><label class="form-label small">Cantidad</label><input type="number" class="form-control form-control-sm" name="settings[limit]" value="<?= (int)($settings['limit'] ?? 4) ?>" /></div>
        <div class="col-md-4"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="settings[featured_only]" id="featured_only" value="1" <?= (!empty($settings['featured_only'])) ? 'checked' : '' ?> /><label class="form-check-label" for="featured_only">Sólo destacados</label></div></div>
      </div>

      <div id="articles_grid-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-4"><label class="form-label small">Cantidad</label><input type="number" class="form-control form-control-sm" name="settings[limit]" value="<?= (int)($settings['limit'] ?? 4) ?>" /></div>
      </div>

      <div id="featured_products-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-4"><label class="form-label small">Cantidad</label><input type="number" class="form-control form-control-sm" name="settings[limit]" value="<?= (int)($settings['limit'] ?? 8) ?>" /></div>
      </div>

      <div id="routines_grid-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-4"><label class="form-label small">Cantidad</label><input type="number" class="form-control form-control-sm" name="settings[limit]" value="<?= (int)($settings['limit'] ?? 4) ?>" /></div>
      </div>

      <div id="promo_banner-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-6"><label class="form-label small">Texto</label><input class="form-control form-control-sm" name="settings[text]" value="<?= htmlspecialchars((string)($settings['text'] ?? '')) ?>" /></div>
        <div class="col-md-6"><label class="form-label small">Link</label><input class="form-control form-control-sm" name="settings[link]" value="<?= htmlspecialchars((string)($settings['link'] ?? '')) ?>" /></div>
        <div class="col-md-6"><label class="form-label small">Imagen</label><input class="form-control form-control-sm" name="settings[image]" value="<?= htmlspecialchars((string)($settings['image'] ?? '')) ?>" /></div>
        <div class="col-md-6"><label class="form-label small">Color fondo</label><input class="form-control form-control-sm" name="settings[bg_color]" value="<?= htmlspecialchars((string)($settings['bg_color'] ?? '')) ?>" /></div>
      </div>

      <div id="categories_grid-settings" class="settings-group row g-3 compact-form">
        <div class="col-md-4"><label class="form-label small">Cantidad</label><input type="number" class="form-control form-control-sm" name="settings[limit]" value="<?= (int)($settings['limit'] ?? 6) ?>" /></div>
      </div>

      <div id="custom_html-settings" class="settings-group">
        <label class="form-label small">HTML libre</label>
        <textarea class="form-control form-control-sm" name="content" rows="6"><?= htmlspecialchars((string)($block['content'] ?? '')) ?></textarea>
      </div>

      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/marketing/home">Cancelar</a>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  var sel = document.getElementById('blockType');
  function update(){
    var type = sel.value;
    document.querySelectorAll('.settings-group').forEach(function(g){ g.classList.remove('active'); });
    var g = document.getElementById(type + '-settings');
    if (g) g.classList.add('active');
  }
  sel.addEventListener('change', update);
  update();
})();
</script>
