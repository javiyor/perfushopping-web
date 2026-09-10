<?php
$topics = $topics ?? [];
?>
<div class="page-title">
  <h2>Temas</h2>
  <p>Categorías editoriales para agrupar contenido, productos y necesidades.</p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/temas/nuevo"><i class="bi bi-plus-lg"></i> Nuevo tema</a>

<div class="card shadow-sm">
  <table class="table table-admin mb-0">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Slug</th>
        <th>Orden</th>
        <th>Activo</th>
        <th style="width:120px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($topics as $t): ?>
      <tr>
        <td><?= htmlspecialchars((string)$t['name']) ?></td>
        <td><?= htmlspecialchars((string)$t['slug']) ?></td>
        <td><?= (int)$t['sort_order'] ?></td>
        <td><?= (int)$t['active'] ? 'Sí' : 'No' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/temas/<?= (int)$t['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/temas/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar tema?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$topics): ?>
      <tr><td colspan="5" class="text-muted text-center">No hay temas cargados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
