<?php
$summary = $summary ?? [];
$topPaths = $topPaths ?? [];
$topProducts = $topProducts ?? [];
$products = $products ?? [];
$pageviewsDaily = $pageviewsDaily ?? [];
$funnel = $funnel ?? [];
$days = $days ?? 30;
?>
<div class="page-title">
  <h2>Analytics</h2>
  <p>Visitas, interacciones y embudo de conversión.</p>
</div>

<form method="get" class="row g-2 align-items-end mb-3" style="max-width:260px">
  <div class="col">
    <label class="form-label small">Días</label>
    <select class="form-select form-select-sm" name="days" onchange="this.form.submit()">
      <option value="7" <?= $days==7?'selected':'' ?>>7 días</option>
      <option value="30" <?= $days==30?'selected':'' ?>>30 días</option>
      <option value="60" <?= $days==60?'selected':'' ?>>60 días</option>
      <option value="90" <?= $days==90?'selected':'' ?>>90 días</option>
    </select>
  </div>
</form>

<div class="row g-3 mb-4">
  <div class="col-md-3">
    <div class="card shadow-sm p-3">
      <div class="small text-muted">Pageviews</div>
      <div class="fs-4 fw-bold"><?= number_format((int)($funnel['pageview'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm p-3">
      <div class="small text-muted">Vistas de producto</div>
      <div class="fs-4 fw-bold"><?= number_format((int)($funnel['product_view'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm p-3">
      <div class="small text-muted">Add to cart</div>
      <div class="fs-4 fw-bold"><?= number_format((int)($funnel['add_to_cart'] ?? 0)) ?></div>
    </div>
  </div>
  <div class="col-md-3">
    <div class="card shadow-sm p-3">
      <div class="small text-muted">Checkout completado</div>
      <div class="fs-4 fw-bold"><?= number_format((int)($funnel['checkout_complete'] ?? 0)) ?></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Eventos por tipo</div>
      <table class="table table-admin mb-0">
        <?php foreach ($summary as $s): ?>
        <tr><td><?= htmlspecialchars((string)$s['event_type']) ?></td><td class="text-end"><?= number_format((int)$s['c']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$summary): ?><tr><td class="text-muted">Sin datos</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card shadow-sm">
      <div class="card-header fw-semibold">Páginas más vistas</div>
      <table class="table table-admin mb-0">
        <?php foreach ($topPaths as $tp): ?>
        <tr><td><?= htmlspecialchars((string)$tp['path']) ?></td><td class="text-end"><?= number_format((int)$tp['c']) ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$topPaths): ?><tr><td class="text-muted">Sin datos</td></tr><?php endif; ?>
      </table>
    </div>
  </div>
</div>

<div class="card shadow-sm mb-4">
  <div class="card-header fw-semibold">Productos más vistos</div>
  <table class="table table-admin mb-0">
    <thead><tr><th>Producto</th><th class="text-end">Vistas</th></tr></thead>
    <tbody>
      <?php foreach ($topProducts as $tp): ?>
      <tr>
        <td><?= htmlspecialchars((string)($products[(int)$tp['product_id']] ?? ('ID ' . $tp['product_id']))) ?></td>
        <td class="text-end"><?= number_format((int)$tp['c']) ?></td>
      </tr>
      <?php endforeach; ?>
      <?php if (!$topProducts): ?><tr><td class="text-muted" colspan="2">Sin datos</td></tr><?php endif; ?>
    </tbody>
  </table>
</div>

<div class="card shadow-sm">
  <div class="card-header fw-semibold">Embudo de conversión (30 días)</div>
  <table class="table table-admin mb-0">
    <thead><tr><th>Etapa</th><th class="text-end">Cantidad</th></tr></thead>
    <tbody>
      <?php foreach ($funnel as $step => $count): ?>
      <tr><td><?= htmlspecialchars($step) ?></td><td class="text-end"><?= number_format((int)$count) ?></td></tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>
