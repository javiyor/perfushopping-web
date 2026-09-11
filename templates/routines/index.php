<?php
$routines = $routines ?? [];
$title = $title ?? 'Rutinas';
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= htmlspecialchars($title) ?></h2>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px">
    <?php foreach ($routines as $r): ?>
    <a href="/rutinas/<?= htmlspecialchars((string)$r['slug']) ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="padding:16px">
        <?php if ($r['image']): ?><img src="<?= htmlspecialchars((string)$r['image']) ?>" alt="" style="width:100%;height:180px;object-fit:cover;border-radius:8px;margin-bottom:10px"><?php endif; ?>
        <strong><?= htmlspecialchars((string)$r['name']) ?></strong>
        <div class="small text-muted"><?= htmlspecialchars((string)($r['description'] ?? '')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
