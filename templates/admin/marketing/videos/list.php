<?php
$videos = $videos ?? [];
$csrf = $csrf ?? '';
?>
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item">Contenido y Marketing</li>
    <li class="breadcrumb-item active">Videos</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Videos</h4>
  <a href="/admin/marketing/videos/nuevo" class="btn btn-accent btn-sm"><i class="bi bi-plus-lg"></i> Nuevo video</a>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-admin table-hover mb-0">
      <thead>
        <tr>
          <th>Preview</th>
          <th>Título</th>
          <th>Slug</th>
          <th>Destacado</th>
          <th>Activo</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($videos as $v): ?>
        <tr>
          <td style="width:120px">
            <?php if ($v['thumbnail']): ?>
              <img src="<?= htmlspecialchars((string)$v['thumbnail']) ?>" alt="" style="width:100px;height:56px;object-fit:cover;border-radius:6px">
            <?php else: ?>
              <div class="bg-light rounded" style="width:100px;height:56px"></div>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars((string)$v['title']) ?></td>
          <td><code><?= htmlspecialchars((string)$v['slug']) ?></code></td>
          <td><?= (int)$v['featured'] ? '<span class="badge bg-warning">Sí</span>' : '-' ?></td>
          <td><?= (int)$v['active'] ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
          <td class="text-end">
            <a href="/admin/marketing/videos/editar/<?= (int)$v['id'] ?>" class="btn btn-outline-primary btn-sm"><i class="bi bi-pencil"></i></a>
            <form method="post" action="/admin/marketing/videos/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= (int)$v['id'] ?>" />
              <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$videos): ?>
        <tr><td colspan="6" class="text-muted text-center py-4">No hay videos cargados.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
