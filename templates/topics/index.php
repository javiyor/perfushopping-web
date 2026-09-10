<?php
$topics = $topics ?? [];
$title = $title ?? 'Temas';
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= htmlspecialchars($title) ?></h2>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
    <?php foreach ($topics as $t): ?>
    <a href="/temas/<?= htmlspecialchars((string)$t['slug']) ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="padding:16px;text-align:center">
        <?php if ($t['image']): ?>
        <img src="<?= htmlspecialchars((string)$t['image']) ?>" alt="" style="width:100%;height:160px;object-fit:cover;border-radius:8px;margin-bottom:10px">
        <?php endif; ?>
        <strong><?= htmlspecialchars((string)$t['name']) ?></strong>
        <div class="small text-muted"><?= htmlspecialchars((string)($t['description_short'] ?? '')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
