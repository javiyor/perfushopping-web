<?php
$page = $page ?? null;
$brands = $brands ?? [];
$id = $page ? (int)$page['id'] : 0;
?>
<div class="page-title">
  <h2><?= $page ? 'Editar página de marca' : 'Nueva página de marca' ?></h2>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body small">
    <h6 class="fw-semibold">¿Cómo se usa?</h6>
    <p class="mb-1">Acá se crea una landing editorial para una marca. Seleccionás la marca, le ponés un título y descripción, y después desde el listado podés agregar bloques (hero, productos, videos, etc.).</p>
    <p class="mb-0 text-muted">La URL pública será <code>/marcas/{slug}</code>. El slug se genera automático desde el nombre de la marca si lo dejás vacío.</p>
  </div>
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
      <h6 class="fw-semibold">SEO y redes sociales</h6>
      <div class="row g-3 compact-form">
        <div class="col-md-4">
          <label class="form-label small">SEO title</label>
          <input class="form-control form-control-sm" name="seo_title" value="<?= htmlspecialchars((string)($page['seo_title'] ?? '')) ?>" placeholder="Título para Google" />
          <div class="form-text tiny text-muted">Aparece en la pestaña del navegador y en los resultados de búsqueda.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label small">Meta description</label>
          <input class="form-control form-control-sm" name="seo_description" value="<?= htmlspecialchars((string)($page['seo_description'] ?? '')) ?>" placeholder="Descripción corta" />
          <div class="form-text tiny text-muted">Resumen que muestra Google debajo del título. Máx. recomendado 160 caracteres.</div>
        </div>
        <div class="col-md-4">
          <label class="form-label small">OG image</label>
          <input class="form-control form-control-sm" name="og_image" value="<?= htmlspecialchars((string)($page['og_image'] ?? '')) ?>" placeholder="URL de imagen" />
          <div class="form-text tiny text-muted">Imagen que se muestra al compartir el link en WhatsApp, Facebook, Instagram, etc.</div>
        </div>
      </div>
      <div class="mt-3">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/marketing/marcas">Cancelar</a>
      </div>
    </form>
  </div>
</div>
