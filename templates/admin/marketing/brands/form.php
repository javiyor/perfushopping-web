<?php
$page = $page ?? null;
$brands = $brands ?? [];
$id = $page ? (int)$page['id'] : 0;
?>
<div class="page-title">
  <h2><?= $page ? 'Editar página de marca' : 'Nueva página de marca' ?></h2>
</div>

<div class="card shadow-sm">
  <div class="card-body">
    <form method="post" action="/admin/marketing/marcas/guardar">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
      <input type="hidden" name="id" value="<?= $id ?>" />
      <div class="row g-3 compact-form">
        <div class="col-md-6">
          <label class="form-label small">Marca</label>
          <select class="form-select form-select-sm" name="brand_id" required>
            <option value="">Seleccionar...</option>
            <?php foreach ($brands as $b): ?>
            <option value="<?= (int)$b['codsub'] ?>" <?= (($page['brand_id'] ?? 0) == (int)$b['codsub']) ? 'selected' : '' ?>><?= htmlspecialchars((string)$b['nomsub']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6"><label class="form-label small">Slug</label><input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($page['slug'] ?? '')) ?>" placeholder="Se genera automáticamente" /></div>
        <div class="col-md-8"><label class="form-label small">Título público</label><input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)($page['title'] ?? '')) ?>" required /></div>
        <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="active" id="active" <?= (($page['active'] ?? 1) == 1) ? 'checked' : '' ?> /><label class="form-check-label" for="active">Activo</label></div></div>
        <div class="col-12"><label class="form-label small">Descripción</label><textarea class="form-control form-control-sm" name="description" rows="4"><?= htmlspecialchars((string)($page['description'] ?? '')) ?></textarea></div>
      </div>
      <hr />
      <h6 class="fw-semibold">SEO</h6>
      <div class="row g-3 compact-form">
        <div class="col-md-4"><input class="form-control form-control-sm" name="seo_title" value="<?= htmlspecialchars((string)($page['seo_title'] ?? '')) ?>" placeholder="SEO title" /></div>
        <div class="col-md-4"><input class="form-control form-control-sm" name="seo_description" value="<?= htmlspecialchars((string)($page['seo_description'] ?? '')) ?>" placeholder="Meta description" /></div>
        <div class="col-md-4"><input class="form-control form-control-sm" name="og_image" value="<?= htmlspecialchars((string)($page['og_image'] ?? '')) ?>" placeholder="OG image" /></div>
      </div>
      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/marketing/marcas">Cancelar</a>
      </div>
    </form>
  </div>
</div>
