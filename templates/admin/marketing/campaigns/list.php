<?php
$campaigns = $campaigns ?? [];
?>
<div class="page-title">
  <h2>Campañas</h2>
  <p>Landings y campañas comerciales.</p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/campanas/nuevo"><i class="bi bi-plus-lg"></i> Nueva campaña</a>

<div class="card shadow-sm">
  <table class="table table-admin mb-0">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Slug</th>
        <th>Título</th>
        <th>Activo</th>
        <th style="width:120px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($campaigns as $c): ?>
      <tr>
        <td><?= htmlspecialchars((string)$c['name']) ?></td>
        <td><?= htmlspecialchars((string)$c['slug']) ?></td>
        <td><?= htmlspecialchars((string)$c['title']) ?></td>
        <td><?= (int)$c['active'] ? 'Sí' : 'No' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/campanas/<?= (int)$c['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/campanas/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar campaña?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$campaigns): ?>
      <tr><td colspan="5" class="text-muted text-center">No hay campañas cargadas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
