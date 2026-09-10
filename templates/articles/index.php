<?php
$articles = $articles ?? [];
$title = $title ?? 'Artículos y guías';
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= htmlspecialchars($title) ?></h2>

  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    <?php foreach ($articles as $a): ?>
    <a href="/articulos/<?= htmlspecialchars((string)$a['slug']) ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="padding:16px">
        <strong><?= htmlspecialchars((string)$a['title']) ?></strong>
        <div class="small text-muted mt-1"><?= htmlspecialchars((string)($a['excerpt'] ?? '')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
