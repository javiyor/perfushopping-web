<?php
use Perfushopping\Web\Support\Format;

$quiz = $quiz ?? [];
$result = $result ?? [];
$products = $result['products'] ?? [];
$routines = $result['routines'] ?? [];
$needs = $result['needs'] ?? [];
$firstRoutine = $firstRoutine ?? null;
$routineItems = $routineItems ?? [];

$waText = 'Hola, completé el recomendador "' . ($quiz['title'] ?? '') . '" de Perfushopping.';
if ($firstRoutine) {
    $waText .= ' Me recomendaron la rutina: ' . $firstRoutine['name'] . '.';
}
$waText .= ' ¿Podrían revisar mi recomendación?';
?>
<div class="page">
  <h2 style="margin:0 0 10px">Tu recomendación</h2>
  <?php if ($needs): ?>
  <p class="lead">Detectamos: <?= htmlspecialchars(implode(', ', array_merge(...array_values($needs)))) ?></p>
  <?php endif; ?>

  <?php if ($firstRoutine): ?>
  <h3 style="margin:24px 0 10px">Tu rutina: <?= htmlspecialchars((string)$firstRoutine['name']) ?></h3>
  <?php if ($firstRoutine['description']): ?><p><?= htmlspecialchars((string)$firstRoutine['description']) ?></p><?php endif; ?>
  <div class="card" style="padding:16px;margin-bottom:14px">
    <?php foreach ($routineItems as $idx => $it):
      $tiva = (float)($it['tiva'] ?? 0);
      $base = (float)($isWholesale ? $it['precio1'] : $it['precio']);
      $withIva = $base * (1 + $tiva/100);
    ?>
    <div style="display:flex;gap:12px;align-items:center;padding:10px 0;border-bottom:1px solid rgba(216,178,90,0.15)">
      <div style="font-weight:800;color:var(--gold)"><?= $idx + 1 ?></div>
      <div style="flex:1">
        <div style="font-weight:600"><?= htmlspecialchars((string)$it['produ']) ?></div>
        <?php if ($it['instructions']): ?><div class="small text-muted"><?= htmlspecialchars((string)$it['instructions']) ?></div><?php endif; ?>
      </div>
      <div class="price"><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?></strong><small>IVA inc.</small></div>
    </div>
    <?php endforeach; ?>
    <div class="mt-3">
      <a class="btn" href="/cart/add-routine/<?= (int)$firstRoutine['id'] ?>">Agregar rutina completa al carrito</a>
    </div>
  </div>
  <?php endif; ?>

  <?php if ($products): ?>
  <h3 style="margin:24px 0 10px">Productos recomendados</h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
    <?php foreach ($products as $p):
      $tiva = (float)($p['tiva'] ?? 0);
      $base = (float)($isWholesale ? $p['precio1'] : $p['precio']);
      $withIva = $base * (1 + $tiva/100);
    ?>
    <a class="card" href="/p/<?= (int)$p['idprodu'] ?>">
      <div class="thumb">
        <?php if (!empty($p['imagen'])): ?><img src="<?= htmlspecialchars(Format::uploadUrl((string)$p['imagen'])) ?>" alt="" loading="lazy" /><?php endif; ?>
      </div>
      <div class="card-body">
        <div class="card-title" style="font-size:14px"><?= htmlspecialchars((string)$p['produ']) ?></div>
        <div class="price"><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?></strong><small>IVA inc.</small></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if (!$products && !$firstRoutine): ?>
  <div class="notice">No encontramos coincidencias. Probá con otras respuestas o consultanos por WhatsApp.</div>
  <?php endif; ?>

  <div class="mt-3" style="display:flex;gap:10px;flex-wrap:wrap">
    <a class="btn" href="https://wa.me/?text=<?= urlencode($waText) ?>" target="_blank" rel="noopener">Quiero que revisen mi recomendación</a>
    <?php if ($firstRoutine): ?>
    <a class="btn secondary" href="/rutinas/<?= htmlspecialchars((string)$firstRoutine['slug']) ?>">Ver detalle de la rutina</a>
    <?php endif; ?>
  </div>
</div>
