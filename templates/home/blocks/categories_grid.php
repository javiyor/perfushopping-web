<?php
$limit = (int)($block['settings']['limit'] ?? 6);
$items = array_slice($rubros, 0, $limit);
?>
<?php if ($items): ?>
<div class="page" style="margin-top:24px">
  <h3 style="color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars((string)($block['title'] ?? 'Comprar por categoría')) ?></h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-top:12px">
    <?php foreach ($items as $r): ?>
    <a href="/?codrub=<?= (int)$r['codrub'] ?>" class="card" style="text-align:center;text-decoration:none;color:inherit">
      <div class="card-title" style="font-size:15px"><?= htmlspecialchars((string)$r['nomrub']) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
