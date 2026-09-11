<?php
$limit = (int)($block['settings']['limit'] ?? 4);
$featuredOnly = !empty($block['settings']['featured_only']);
$items = $featuredOnly ? array_filter($homeVideos, static fn($v) => (int)$v['featured'] === 1) : $homeVideos;
$items = array_slice($items, 0, $limit);
?>
<?php if ($items): ?>
<div class="page" style="margin-top:24px">
  <h3 style="color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars((string)($block['title'] ?? 'Aprendé con Perfushopping')) ?></h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-top:12px">
    <?php foreach ($items as $v):
      $thumb = (string)($v['thumbnail'] ?? '');
      if ($thumb === '') $thumb = \Perfushopping\Web\Repo\Marketing\VideoRepo::youtubeThumb((string)$v['url']);
    ?>
    <a href="/aprende/videos/<?= htmlspecialchars((string)$v['slug']) ?>" class="card" style="overflow:hidden;text-decoration:none;color:inherit">
      <div style="position:relative;padding-top:56.25%;background:#000">
        <?php if ($thumb): ?><img src="<?= htmlspecialchars($thumb) ?>" alt="" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover"><?php endif; ?>
      </div>
      <div class="card-body"><div class="card-title" style="font-size:14px"><?= htmlspecialchars((string)$v['title']) ?></div></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>
