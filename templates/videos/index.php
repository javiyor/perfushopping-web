<?php
$videos = $videos ?? [];
$articles = $articles ?? [];
$title = $title ?? 'Aprendé con Perfushopping';
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= htmlspecialchars($title) ?></h2>

  <?php if ($videos): ?>
  <h3 style="margin:18px 0 10px">Videos</h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    <?php foreach ($videos as $v): ?>
    <a href="/aprende/videos/<?= htmlspecialchars((string)$v['slug']) ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="overflow:hidden">
        <div style="position:relative;padding-top:56.25%;background:#000">
          <?php
          $thumb = (string)($v['thumbnail'] ?? '');
          if ($thumb === '') $thumb = \Perfushopping\Web\Repo\Marketing\VideoRepo::youtubeThumb((string)$v['url']);
          if ($thumb):
          ?>
          <img src="<?= htmlspecialchars($thumb) ?>" alt="" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover">
          <?php endif; ?>
        </div>
        <div style="padding:12px">
          <strong><?= htmlspecialchars((string)$v['title']) ?></strong>
          <div class="small text-muted"><?= htmlspecialchars((string)($v['description_short'] ?? '')) ?></div>
        </div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($articles): ?>
  <h3 style="margin:24px 0 10px">Artículos</h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    <?php foreach ($articles as $a): ?>
    <a href="/articulos/<?= htmlspecialchars((string)$a['slug']) ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="padding:14px">
        <strong><?= htmlspecialchars((string)$a['title']) ?></strong>
        <div class="small text-muted"><?= htmlspecialchars((string)($a['excerpt'] ?? '')) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
