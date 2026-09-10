<?php
$article = $article ?? null;
$seo = $seo ?? null;
$csrf = $csrf ?? '';
$id = $article ? (int)$article['id'] : 0;
?>
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item"><a href="/admin/marketing/articulos">Artículos</a></li>
    <li class="breadcrumb-item active"><?= $id ? 'Editar' : 'Nuevo' ?></li>
  </ol>
</nav>

<div class="card shadow-sm">
  <div class="card-header bg-white fw-semibold"><?= $id ? 'Editar artículo' : 'Nuevo artículo' ?></div>
  <div class="card-body">
    <form method="post" action="/admin/marketing/articulos/guardar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />

      <div class="row g-2">
        <div class="col-md-8">
          <label class="form-label small">Título</label>
          <input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)($article['title'] ?? '')) ?>" required />
        </div>
        <div class="col-md-4">
          <label class="form-label small">Slug (opcional)</label>
          <input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($article['slug'] ?? '')) ?>" />
        </div>
        <div class="col-md-4">
          <label class="form-label small">Estado</label>
          <select class="form-select form-select-sm" name="status">
            <option value="draft" <?= (($article['status'] ?? '') === 'draft') ? 'selected' : '' ?>>Borrador</option>
            <option value="published" <?= (($article['status'] ?? '') === 'published') ? 'selected' : '' ?>>Publicado</option>
            <option value="scheduled" <?= (($article['status'] ?? '') === 'scheduled') ? 'selected' : '' ?>>Programado</option>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Fecha publicación</label>
          <input class="form-control form-control-sm" type="datetime-local" name="published_at" value="<?= htmlspecialchars((string)($article['published_at'] ?? '')) ?>" />
        </div>
        <div class="col-md-4 d-flex align-items-end pb-1">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="active" id="a_active" <?= ($article === null || (int)($article['active'] ?? 1)) ? 'checked' : '' ?> />
            <label class="form-check-label small" for="a_active">Activo</label>
          </div>
        </div>
        <div class="col-12">
          <label class="form-label small">Extracto</label>
          <textarea class="form-control form-control-sm" name="excerpt" rows="2"><?= htmlspecialchars((string)($article['excerpt'] ?? '')) ?></textarea>
        </div>
        <div class="col-12">
          <label class="form-label small">Contenido HTML</label>
          <textarea class="form-control form-control-sm" name="content" rows="10"><?= htmlspecialchars((string)($article['content'] ?? '')) ?></textarea>
        </div>
        <div class="col-md-6">
          <label class="form-label small">SEO Title</label>
          <input class="form-control form-control-sm" name="seo_title" value="<?= htmlspecialchars((string)($seo['title'] ?? ($article['seo_title'] ?? ''))) ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label small">SEO Meta Description</label>
          <input class="form-control form-control-sm" name="seo_description" value="<?= htmlspecialchars((string)($seo['meta_description'] ?? ($article['seo_description'] ?? ''))) ?>" />
        </div>
        <div class="col-md-6">
          <label class="form-label small">OG Image URL</label>
          <input class="form-control form-control-sm" name="og_image" value="<?= htmlspecialchars((string)($seo['og_image'] ?? ($article['og_image'] ?? ''))) ?>" />
        </div>
      </div>

      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a href="/admin/marketing/articulos" class="btn btn-outline-secondary btn-sm">Cancelar</a>
      </div>
    </form>
  </div>
</div>
