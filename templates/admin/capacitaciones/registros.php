<?php
$flash = $flash ?? null;
$statusMap = [
    'new' => ['Novedad', 'secondary'],
    'contacted' => ['Contactado', 'info'],
    'confirmed' => ['Confirmado', 'success'],
    'cancelled' => ['Cancelado', 'danger'],
];
$counts = ['new' => 0, 'contacted' => 0, 'confirmed' => 0, 'cancelled' => 0];
foreach ($list as $r) {
    $s = (string)($r['status'] ?? '');
    if (isset($counts[$s])) {
        $counts[$s]++;
    }
}
$total = count($list);
?>
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
  <div>
    <h2 class="h4 mb-0">Capacitaciones</h2>
    <p class="text-muted mb-0" style="font-size:13px">Registros de inscripción a capacitaciones (<?= (int)$total ?> en total)</p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="/admin/capacitaciones/horarios">
    <i class="bi bi-calendar3"></i> Horarios / cupos / sede
  </a>
</div>

<div class="row g-2 mb-3">
  <?php foreach ($statusMap as $key => $meta): ?>
    <div class="col-6 col-md-3">
      <div class="card shadow-sm h-100 border-0">
        <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between">
          <div>
            <div style="font-size:20px;font-weight:700;line-height:1.1" class="text-<?= $meta[1] === 'secondary' ? 'dark' : $meta[1] ?>">
              <?= (int)$counts[$key] ?>
            </div>
            <div class="text-muted" style="font-size:12px"><?= htmlspecialchars($meta[0]) ?></div>
          </div>
          <span class="badge text-bg-<?= $meta[1] ?>"><?= htmlspecialchars($meta[0]) ?></span>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
</div>

<?php if ($flash): ?>
  <div class="alert alert-<?= ($flash['type'] ?? '') === 'ok' ? 'success' : (($flash['type'] ?? '') === 'danger' ? 'danger' : 'info') ?> py-2">
    <?= htmlspecialchars((string)($flash['text'] ?? '')) ?>
  </div>
<?php endif; ?>

<div class="card shadow-sm border-0">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0" style="font-size:13px">
      <thead class="table-light">
        <tr>
          <th style="width:56px">ID</th>
          <th style="width:96px">Tipo</th>
          <th style="width:110px">Fecha (lunes)</th>
          <th style="width:130px">Horario / sede</th>
          <th>Nombre</th>
          <th style="width:120px">Salón</th>
          <th style="width:140px">Ciudad</th>
          <th>Contacto</th>
          <th style="width:110px">Estado</th>
          <th style="width:190px">Acción</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($list as $r): ?>
          <?php
            $st = (string)($r['status'] ?? '');
            $meta = $statusMap[$st] ?? [$st !== '' ? $st : '—', 'secondary'];
            $rowClass = $st === 'confirmed' ? 'table-success' : ($st === 'cancelled' ? 'table-danger' : '');
          ?>
          <tr class="<?= $rowClass ?>">
            <td class="text-muted">#<?= (int)$r['id'] ?></td>
            <td>
              <?php if (($r['kind'] ?? '') === 'pro'): ?>
                <span class="badge text-bg-dark">Prof.</span>
              <?php else: ?>
                <span class="badge text-bg-light border">Cliente</span>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars((string)($r['monday_date'] ?? '')) ?></td>
            <td>
              <?= htmlspecialchars(substr((string)($r['start_time'] ?? ''), 0, 5) . '-' . substr((string)($r['end_time'] ?? ''), 0, 5)) ?>
              <div class="text-muted" style="font-size:12px"><?= htmlspecialchars((string)($r['venue_name'] ?? '')) ?></div>
            </td>
            <td class="fw-semibold"><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
            <td><?= htmlspecialchars((string)($r['salon_name'] ?? '')) ?></td>
            <td><?= htmlspecialchars(trim((string)($r['city'] ?? '')) . (isset($r['province']) && $r['province'] !== '' ? ' - ' . (string)$r['province'] : '')) ?></td>
            <td>
              <?= htmlspecialchars((string)($r['phone'] ?? '')) ?>
              <?php if (!empty($r['email'])): ?>
                <div class="text-muted" style="font-size:12px"><?= htmlspecialchars((string)$r['email']) ?></div>
              <?php endif; ?>
            </td>
            <td><span class="badge text-bg-<?= $meta[1] ?>"><?= htmlspecialchars($meta[0]) ?></span></td>
            <td>
              <form method="post" action="/admin/capacitaciones/status" class="d-flex gap-1 align-items-center">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
                <select name="status" class="form-select form-select-sm" style="min-width:120px">
                  <?php foreach ($statusMap as $k => $m): ?>
                    <option value="<?= htmlspecialchars($k) ?>" <?= $st === $k ? 'selected' : '' ?>><?= htmlspecialchars($m[0]) ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn btn-outline-secondary btn-sm" type="submit" title="Guardar">
                  <i class="bi bi-check-lg"></i>
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$list): ?>
          <tr>
            <td colspan="10" class="text-center text-muted py-4">Sin registros.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
