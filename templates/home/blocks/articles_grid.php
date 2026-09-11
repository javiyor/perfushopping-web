<?php
$limit = (int)($block['settings']['limit'] ?? 4);
$items = array_slice($homeArticles, 0, $limit);
?>
<?php if ($items): ?>
<div class="page" style="margin-top:24px">
  <h3 style="color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars((string)($block['title'] ?? 'Artículos y guías')) ?></h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-top:12px">
    <?php foreach ($items as $a): ?>
    <a href="/articulos/<?= htmlspecialchars((string)$a['slug']) ?>" class="card" style="text-decoration:none;color:inherit">
      <div class="card-body">
        <div class="card-title" style="font-size:15px"><?= htmlspecialchars((string)$a['title']) ?></div>
        <div style="font-size:12px;color:rgba(246,244,239,0.6)"><?= htmlspecialchars((string)($a['excerpt'] ?? '')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
