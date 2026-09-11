<?php
use Perfushopping\Web\Support\Format;
$limit = (int)($block['settings']['limit'] ?? 8);
$items = array_slice($products, 0, $limit);
?>
<?php if ($items): ?>
<div class="page" style="margin-top:24px">
  <h3 style="color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars((string)($block['title'] ?? 'Productos destacados')) ?></h3>
  <div class="grid" style="margin-top:12px">
    <?php foreach ($items as $p):
      $tiva = (float)($p['tiva'] ?? 0);
      $base = (float)($isWholesale ? $p['precio1'] : $p['precio']);
      $withIva = $base * (1 + $tiva/100);
    ?>
    <a class="card" href="/p/<?= (int)$p['idprodu'] ?>">
      <div class="thumb">
        <?php if (!empty($p['imagen'])): ?>
        <img src="<?= htmlspecialchars(Format::uploadUrl((string)$p['imagen'])) ?>" alt="<?= htmlspecialchars((string)$p['produ']) ?>" loading="lazy" />
        <?php endif; ?>
      </div>
      <div class="card-body">
        <div class="card-title"><?= htmlspecialchars((string)$p['produ']) ?></div>
        <div class="price"><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?></strong><small>IVA inc.</small></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
