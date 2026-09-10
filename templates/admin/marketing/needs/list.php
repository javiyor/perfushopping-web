<?php
$needs = $needs ?? [];
?>
<div class="page-title">
  <h2>Necesidades</h2>
  <p>Soluciones que resuelven problemas concretos del cliente.</p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/necesidades/nuevo"><i class="bi bi-plus-lg"></i> Nueva necesidad</a>

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
      <?php foreach ($needs as $n): ?>
      <tr>
        <td><?= htmlspecialchars((string)$n['name']) ?></td>
        <td><?= htmlspecialchars((string)$n['slug']) ?></td>
        <td><?= (int)$n['sort_order'] ?></td>
        <td><?= (int)$n['active'] ? 'Sí' : 'No' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/necesidades/<?= (int)$n['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/necesidades/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar necesidad?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" value="<?= (int)$n['id'] ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$needs): ?>
      <tr><td colspan="5" class="text-muted text-center">No hay necesidades cargadas.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
