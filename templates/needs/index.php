<?php
$needs = $needs ?? [];
$title = $title ?? 'Soluciones';
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= htmlspecialchars($title) ?></h2>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
    <?php foreach ($needs as $n): ?>
    <a href="/soluciones/<?= htmlspecialchars((string)$n['slug']) ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="padding:16px;text-align:center">
        <?php if ($n['icon']): ?><i class="bi <?= htmlspecialchars((string)$n['icon']) ?>" style="font-size:32px;margin-bottom:8px;display:block"></i><?php endif; ?>
        <?php if ($n['image']): ?>
        <img src="<?= htmlspecialchars((string)$n['image']) ?>" alt="" style="width:100%;height:160px;object-fit:cover;border-radius:8px;margin-bottom:10px">
        <?php endif; ?>
        <strong><?= htmlspecialchars((string)$n['name']) ?></strong>
        <div class="small text-muted"><?= htmlspecialchars((string)($n['description_short'] ?? '')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
