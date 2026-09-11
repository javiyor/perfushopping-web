<?php
$campaign = $campaign ?? null;
$id = $campaign ? (int)$campaign['id'] : 0;
?>
<div class="page-title">
  <h2><?= $campaign ? 'Editar campaña' : 'Nueva campaña' ?></h2>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="/admin/marketing/campanas/guardar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />
      <div class="row g-3 compact-form">
        <div class="col-md-4"><label class="form-label small">Nombre</label><input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)($campaign['name'] ?? '')) ?>" required /></div>
        <div class="col-md-4"><label class="form-label small">Slug</label><input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($campaign['slug'] ?? '')) ?>" /></div>
        <div class="col-md-4"><label class="form-label small">Título público</label><input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)($campaign['title'] ?? '')) ?>" required /></div>
        <div class="col-md-4"><label class="form-label small">Visible desde</label><input type="datetime-local" class="form-control form-control-sm" name="start_at" value="<?= htmlspecialchars((string)($campaign['start_at'] ?? '')) ?>" /></div>
        <div class="col-md-4"><label class="form-label small">Visible hasta</label><input type="datetime-local" class="form-control form-control-sm" name="end_at" value="<?= htmlspecialchars((string)($campaign['end_at'] ?? '')) ?>" /></div>
        <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="active" id="active" <?= (($campaign['active'] ?? 1) == 1) ? 'checked' : '' ?> /><label class="form-check-label" for="active">Activo</label></div></div>
        <div class="col-12"><label class="form-label small">Descripción</label><textarea class="form-control form-control-sm" name="description" rows="4"><?= htmlspecialchars((string)($campaign['description'] ?? '')) ?></textarea></div>
      </div>
      <hr />
      <h6 class="fw-semibold">SEO</h6>
      <div class="row g-3 compact-form">
        <div class="col-md-4"><input class="form-control form-control-sm" name="seo_title" value="<?= htmlspecialchars((string)($campaign['seo_title'] ?? '')) ?>" placeholder="SEO title" /></div>
        <div class="col-md-4"><input class="form-control form-control-sm" name="seo_description" value="<?= htmlspecialchars((string)($campaign['seo_description'] ?? '')) ?>" placeholder="Meta description" /></div>
        <div class="col-md-4"><input class="form-control form-control-sm" name="og_image" value="<?= htmlspecialchars((string)($campaign['og_image'] ?? '')) ?>" placeholder="OG image" /></div>
      </div>
      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/marketing/campanas">Cancelar</a>
      </div>
    </form>
  </div>
</div>
