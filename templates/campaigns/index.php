<?php
$campaigns = $campaigns ?? [];
?>
<div class="page">
  <h2 style="margin:0 0 12px">Campañas</h2>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    <?php foreach ($campaigns as $c): ?>
    <a href="/campanas/<?= htmlspecialchars((string)$c['slug']) ?>" class="card" style="text-decoration:none;color:inherit;padding:20px">
      <strong><?= htmlspecialchars((string)$c['title']) ?></strong>
      <div class="small text-muted"><?= htmlspecialchars((string)($c['description'] ?? '')) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
