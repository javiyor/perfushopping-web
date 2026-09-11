<?php
$quizzes = $quizzes ?? [];
?>
<div class="page-title">
  <h2>Recomendadores</h2>
  <p>Quizzes para ayudar al cliente a elegir productos y rutinas.</p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/recomendadores/nuevo"><i class="bi bi-plus-lg"></i> Nuevo recomendador</a>

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
      <?php foreach ($quizzes as $q): ?>
      <tr>
        <td><?= htmlspecialchars((string)$q['name']) ?></td>
        <td><?= htmlspecialchars((string)$q['slug']) ?></td>
        <td><?= htmlspecialchars((string)$q['title']) ?></td>
        <td><?= (int)$q['active'] ? 'Sí' : 'No' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/recomendadores/<?= (int)$q['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/recomendadores/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar recomendador?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" value="<?= (int)$q['id'] ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$quizzes): ?>
      <tr><td colspan="5" class="text-muted text-center">No hay recomendadores cargados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
