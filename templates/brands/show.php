<?php
$page = $page ?? [];
$blocks = $blocks ?? [];
?>
<div class="page" style="margin-top:16px">
  <h2 style="margin:0 0 10px"><?= htmlspecialchars((string)$page['title']) ?></h2>
  <?php if ($page['description']): ?>
  <div class="notice"><?= nl2br(htmlspecialchars((string)$page['description'])) ?></div>
  <?php endif; ?>
</div>

<?php if ($blocks): ?>
  <?php
  foreach ($blocks as $block) {
      $blockFile = __DIR__ . '/../home/blocks/' . preg_replace('/[^a-z0-9_]/', '', (string)$block['block_type']) . '.php';
      if (is_file($blockFile)) {
          include $blockFile;
      }
  }
  ?>
<?php else: ?>
<div class="page">
  <p class="lead">Productos de <?= htmlspecialchars((string)$page['brand_name']) ?></p>
  <?php if (!empty($products)): ?>
  <div class="grid">
    <?php foreach ($products as $p):
      $tiva = (float)($p['tiva'] ?? 0);
      $base = (float)($isWholesale ? $p['precio1'] : $p['precio']);
      $withIva = $base * (1 + $tiva/100);
    ?>
    <a class="card" href="/p/<?= (int)$p['idprodu'] ?>">
      <div class="thumb"><?php if (!empty($p['imagen'])): ?><img src="<?= htmlspecialchars(\Perfushopping\Web\Support\Format::uploadUrl((string)$p['imagen'])) ?>" alt="" loading="lazy" /><?php endif; ?></div>
      <div class="card-body">
        <div class="card-title" style="font-size:14px"><?= htmlspecialchars((string)$p['produ']) ?></div>
        <div class="price"><strong><?= htmlspecialchars(\Perfushopping\Web\Support\Format::moneyRoundedFromCents((int)round($withIva*100))) ?></strong><small>IVA inc.</small></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>
