<?php
$routines = $routines ?? [];
?>
<div class="page-title">
  <h2>Rutinas</h2>
  <p>Combinaciones de productos con instrucciones de uso.</p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/rutinas/nuevo"><i class="bi bi-plus-lg"></i> Nueva rutina</a>

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
      <?php foreach ($routines as $r): ?>
      <tr>
        <td><?= htmlspecialchars((string)$r['name']) ?></td>
        <td><?= htmlspecialchars((string)$r['slug']) ?></td>
        <td><?= (int)$r['sort_order'] ?></td>
        <td><?= (int)$r['active'] ? 'Sí' : 'No' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/rutinas/<?= (int)$r['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/rutinas/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar rutina?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$routines): ?>
      <tr><td colspan="5" class="text-muted text-center">No hay rutinas cargadas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
