<?php
$pages = $pages ?? [];
$brands = $brands ?? [];
?>
<div class="page-title">
  <h2>Páginas de marca</h2>
  <p>Landing editoriales para cada marca.</p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/marcas/nuevo"><i class="bi bi-plus-lg"></i> Nueva página</a>

<div class="card shadow-sm">
  <table class="table table-admin mb-0">
    <thead>
      <tr>
        <th>Marca</th>
        <th>Slug</th>
        <th>Título</th>
        <th>Activo</th>
        <th style="width:140px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($pages as $p): ?>
      <tr>
        <td><?= htmlspecialchars((string)$p['brand_name']) ?></td>
        <td><?= htmlspecialchars((string)$p['slug']) ?></td>
        <td><?= htmlspecialchars((string)$p['title']) ?></td>
        <td><?= (int)$p['active'] ? 'Sí' : 'No' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-primary" href="/admin/marketing/marcas/<?= (int)$p['id'] ?>/bloques" title="Bloques"><i class="bi bi-layout-text-window"></i></a>
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/marcas/<?= (int)$p['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/marcas/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar página?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$pages): ?>
      <tr><td colspan="5" class="text-muted text-center">No hay páginas de marca cargadas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
