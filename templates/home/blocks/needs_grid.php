<?php
$limit = (int)($block['settings']['limit'] ?? 6);
$items = array_slice($homeNeeds, 0, $limit);
?>
<?php if ($items): ?>
<div class="page" style="margin-top:24px">
  <h3 style="color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars((string)($block['title'] ?? '¿Qué querés mejorar?')) ?></h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin-top:12px">
    <?php foreach ($items as $n): ?>
    <a href="/soluciones/<?= htmlspecialchars((string)$n['slug']) ?>" class="card" style="text-align:center;text-decoration:none;color:inherit">
      <?php if ($n['icon']): ?><i class="bi <?= htmlspecialchars((string)$n['icon']) ?>" style="font-size:28px"></i><?php endif; ?>
      <?php if ($n['image']): ?><img src="<?= htmlspecialchars((string)$n['image']) ?>" alt="" style="width:100%;height:120px;object-fit:cover;border-radius:8px;margin-bottom:8px"><?php endif; ?>
      <div class="card-title" style="font-size:14px"><?= htmlspecialchars((string)$n['name']) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
