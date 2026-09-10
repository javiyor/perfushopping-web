<?php
$articles = $articles ?? [];
$csrf = $csrf ?? '';
?>
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item">Contenido y Marketing</li>
    <li class="breadcrumb-item active">Artículos</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Artículos</h4>
  <a href="/admin/marketing/articulos/nuevo" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Nuevo artículo</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-admin table-hover mb-0">
      <thead>
        <tr>
          <th>Título</th>
          <th>Slug</th>
          <th>Estado</th>
          <th>Publicación</th>
          <th>Activo</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($articles as $a): ?>
        <tr>
          <td><?= htmlspecialchars((string)$a['title']) ?></td>
          <td><code><?= htmlspecialchars((string)$a['slug']) ?></code></td>
          <td><span class="badge bg-<?= ($a['status'] ?? '') === 'published' ? 'success' : 'warning' ?>"><?= htmlspecialchars((string)($a['status'] ?? 'draft')) ?></span></td>
          <td><?= htmlspecialchars((string)($a['published_at'] ?? '-')) ?></td>
          <td><?= (int)$a['active'] ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
          <td class="text-end">
            <a href="/admin/marketing/articulos/editar/<?= (int)$a['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
            <form method="post" action="/admin/marketing/articulos/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>" />
              <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$articles): ?>
        <tr><td colspan="6" class="text-muted text-center py-4">No hay artículos cargados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
