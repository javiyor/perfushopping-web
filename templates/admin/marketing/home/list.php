<?php
$blocks = $blocks ?? [];
?>
<div class="page-title">
  <h2>Home</h2>
  <p>Ordená, activá y editá los bloques que se muestran en la página de inicio.</p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/home/nuevo"><i class="bi bi-plus-lg"></i> Nuevo bloque</a>

<div class="card shadow-sm">
  <table class="table table-admin mb-0" id="blocks-table">
    <thead>
      <tr>
        <th style="width:50px">Orden</th>
        <th>Tipo</th>
        <th>Título</th>
        <th>Activo</th>
        <th style="width:180px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($blocks as $b): ?>
      <tr data-id="<?= (int)$b['id'] ?>">
        <td class="position"><?= (int)$b['position'] ?></td>
        <td><?= htmlspecialchars((string)$b['block_type']) ?></td>
        <td><?= htmlspecialchars((string)($b['title'] ?? '')) ?></td>
        <td>
          <button type="button" class="btn btn-sm <?= (int)$b['active'] ? 'btn-success' : 'btn-outline-secondary' ?>" onclick="toggleBlock(<?= (int)$b['id'] ?>, this)"><?= (int)$b['active'] ? 'Sí' : 'No' ?></button>
        </td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-secondary" type="button" onclick="moveRow(this,-1)"><i class="bi bi-arrow-up"></i></button>
          <button class="btn btn-sm btn-outline-secondary" type="button" onclick="moveRow(this,1)"><i class="bi bi-arrow-down"></i></button>
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/home/<?= (int)$b['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/home/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar bloque?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" value="<?= (int)$b['id'] ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
          </form>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$blocks): ?>
      <tr><td colspan="5" class="text-muted text-center">No hay bloques cargados.</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php if ($blocks): ?>
<form method="post" action="/admin/marketing/home/reordenar" id="reorder-form" class="mt-3">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
  <input type="hidden" name="ids" id="reorder-ids" value="" />
  <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar orden</button>
</form>
<?php endif; ?>

<script>
function toggleBlock(id, btn) {
  fetch('/admin/marketing/home/toggle', {
    method: 'POST',
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: '_csrf=<?= urlencode($csrf ?? '') ?>&id=' + encodeURIComponent(id)
  }).then(function(){ location.reload(); });
}
function moveRow(btn, dir) {
  var row = btn.closest('tr');
  var tbody = row.parentNode;
  if (dir < 0 && row.previousElementSibling) {
    tbody.insertBefore(row, row.previousElementSibling);
  } else if (dir > 0 && row.nextElementSibling) {
    tbody.insertBefore(row.nextElementSibling, row);
  }
  updatePositions();
}
function updatePositions() {
  var ids = [];
  document.querySelectorAll('#blocks-table tbody tr').forEach(function(r, i){
    ids.push(r.dataset.id);
    r.querySelector('.position').textContent = i;
  });
  document.getElementById('reorder-ids').value = ids.join(',');
}
document.getElementById('reorder-form') && updatePositions();
</script>
