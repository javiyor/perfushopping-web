<?php
use Perfushopping\Web\Support\Format;

$quiz = $quiz ?? [];
$result = $result ?? [];
$products = $result['products'] ?? [];
$routines = $result['routines'] ?? [];
$needs = $result['needs'] ?? [];
?>
<div class="page">
  <h2 style="margin:0 0 10px">Tu recomendación</h2>
  <?php if ($needs): ?>
  <p class="lead">Detectamos: <?= htmlspecialchars(implode(', ', array_merge(...array_values($needs)))) ?></p>
  <?php endif; ?>

  <?php if ($routines): ?>
  <h3 style="margin:24px 0 10px">Rutinas sugeridas</h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
    <?php foreach ($routines as $r): ?>
    <a href="/rutinas/<?= htmlspecialchars((string)$r['slug']) ?>" class="card" style="text-decoration:none;color:inherit">
      <?php if ($r['image']): ?><img src="<?= htmlspecialchars((string)$r['image']) ?>" alt="" style="width:100%;height:180px;object-fit:cover;border-radius:8px 8px 0 0"><?php endif; ?>
      <div class="card-body">
        <div class="card-title" style="font-size:15px"><?= htmlspecialchars((string)$r['name']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
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

  <?php if (!$products && !$routines): ?>
  <div class="notice">No encontramos coincidencias. Probá con otras respuestas o consultanos por WhatsApp.</div>
  <?php endif; ?>

  <div class="mt-3">
    <a class="btn" href="https://wa.me/?text=<?= urlencode('Hola, completé el recomendador "' . ($quiz['title'] ?? '') . '" en Perfushopping y quería que revisen mi recomendación.') ?>" target="_blank" rel="noopener">Quiero que revisen mi recomendación</a>
  </div>
</div>
