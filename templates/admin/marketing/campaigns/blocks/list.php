<?php
$campaign = $campaign ?? [];
$blocks = $blocks ?? [];
$campaignId = (int)$campaign['id'];
?>
<div class="page-title">
  <h2>Bloques de campaña: <?= htmlspecialchars((string)$campaign['name']) ?></h2>
  <p><a href="/admin/marketing/campanas">← Volver a campañas</a></p>
</div>

<a class="btn btn-accent btn-sm mb-3" href="/admin/marketing/campanas/<?= $campaignId ?>/bloques/nuevo"><i class="bi bi-plus-lg"></i> Agregar bloque</a>

<form method="post" action="/admin/marketing/campanas/<?= $campaignId ?>/bloques/ordenar" id="reorder-form">
  <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
  <input type="hidden" name="ids" id="reorder-ids" value="" />
</form>

<div class="card shadow-sm">
  <table class="table table-admin mb-0" id="blocks-table">
    <thead>
      <tr>
        <th style="width:40px">☰</th>
        <th>Bloque</th>
        <th>Tipo</th>
        <th>Activo</th>
        <th style="width:140px"></th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($blocks as $b): ?>
      <tr data-id="<?= (int)$b['id'] ?>">
        <td class="handle" style="cursor:grab">☰</td>
        <td><?= htmlspecialchars((string)($b['title'] ?? $b['block_type'])) ?></td>
        <td><?= htmlspecialchars((string)$b['block_type']) ?></td>
        <td><?= (int)$b['active'] ? 'Sí' : 'No' ?></td>
        <td class="text-end">
          <a class="btn btn-sm btn-outline-secondary" href="/admin/marketing/campanas/<?= $campaignId ?>/bloques/<?= (int)$b['id'] ?>"><i class="bi bi-pencil"></i></a>
          <form method="post" action="/admin/marketing/campanas/<?= $campaignId ?>/bloques/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar bloque?')">
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

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
(function(){
  var tbody = document.querySelector('#blocks-table tbody');
  if (!tbody) return;
  new Sortable(tbody, {
    handle: '.handle',
    animation: 150,
    onEnd: function(){
      var ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function(tr){ return tr.dataset.id; }).join(',');
      document.getElementById('reorder-ids').value = ids;
      document.getElementById('reorder-form').submit();
    }
  });
})();
</script>
