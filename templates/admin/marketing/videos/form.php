<?php
$video = $video ?? null;
$seo = $seo ?? null;
$csrf = $csrf ?? '';
$id = $video ? (int)$video['id'] : 0;
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
  if (!url || !thumb) return;
  url.addEventListener('blur', function(){
    if (thumb.value !== '') return;
    var m = url.value.match(/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/);
    if (m) thumb.value = 'https://img.youtube.com/vi/' + m[1] + '/mqdefault.jpg';
  });
})();
</script>
