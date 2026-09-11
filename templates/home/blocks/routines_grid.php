<?php
$limit = (int)($block['settings']['limit'] ?? 4);
$routines = $homeRoutines ?? [];
$items = array_slice($routines, 0, $limit);
?>
<?php if ($items): ?>
<div class="page" style="margin-top:24px">
  <h3 style="color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars((string)($block['title'] ?? 'Rutinas recomendadas')) ?></h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:14px;margin-top:12px">
    <?php foreach ($items as $r): ?>
    <a href="/rutinas/<?= htmlspecialchars((string)$r['slug']) ?>" class="card" style="text-decoration:none;color:inherit">
      <?php if ($r['image']): ?><img src="<?= htmlspecialchars((string)$r['image']) ?>" alt="" style="width:100%;height:180px;object-fit:cover;border-radius:8px 8px 0 0"><?php endif; ?>
      <div class="card-body">
        <div class="card-title" style="font-size:15px"><?= htmlspecialchars((string)$r['name']) ?></div>
        <div style="font-size:12px;color:rgba(246,244,239,0.6)"><?= htmlspecialchars((string)($r['description'] ?? '')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
