<?php
$topic = $topic ?? [];
$products = $products ?? [];
$videos = $videos ?? [];
$articles = $articles ?? [];
$faqs = $faqs ?? [];
$seo = $seo ?? [];
?>
<div class="page">
  <nav aria-label="breadcrumb" class="mb-2">
    <a href="/temas" style="color:rgba(246,244,239,0.7)">Temas</a>
    <span style="color:rgba(246,244,239,0.5);margin:0 8px">/</span>
    <span><?= htmlspecialchars((string)$topic['name']) ?></span>
  </nav>

  <?php if ($topic['cover']): ?>
  <div style="margin-bottom:16px">
    <img src="<?= htmlspecialchars((string)$topic['cover']) ?>" alt="" style="width:100%;max-height:320px;object-fit:cover;border-radius:12px">
  </div>
  <?php endif; ?>

  <h2 style="margin:0 0 10px"><?= htmlspecialchars((string)$topic['name']) ?></h2>
  <?php if ($topic['description_long']): ?>
  <div class="notice"><?= nl2br(htmlspecialchars((string)$topic['description_long'])) ?></div>
  <?php elseif ($topic['description_short']): ?>
  <p class="lead"><?= htmlspecialchars((string)$topic['description_short']) ?></p>
  <?php endif; ?>

  <?php if ($products): ?>
  <h3 style="margin:24px 0 10px">Productos</h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
    <?php foreach ($products as $p): ?>
    <a href="/p/<?= (int)$p['idprodu'] ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="padding:10px;text-align:center">
        <?php if ($p['imagen']): ?>
        <img src="/imagenes/<?= htmlspecialchars((string)$p['imagen']) ?>" alt="" style="width:100%;height:140px;object-fit:contain;margin-bottom:8px">
        <?php endif; ?>
        <div class="small"><?= htmlspecialchars((string)$p['produ']) ?></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($videos): ?>
  <h3 style="margin:24px 0 10px">Videos</h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px">
    <?php foreach ($videos as $v):
      $thumb = (string)($v['thumbnail'] ?? '');
      if ($thumb === '') $thumb = \Perfushopping\Web\Repo\Marketing\VideoRepo::youtubeThumb((string)$v['url']);
    ?>
    <a href="/aprende/videos/<?= htmlspecialchars((string)$v['slug']) ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="overflow:hidden">
        <div style="position:relative;padding-top:56.25%;background:#000">
          <?php if ($thumb): ?><img src="<?= htmlspecialchars($thumb) ?>" alt="" style="position:absolute;top:0;left:0;width:100%;height:100%;object-fit:cover"><?php endif; ?>
        </div>
        <div style="padding:10px"><div class="small"><strong><?= htmlspecialchars((string)$v['title']) ?></strong></div></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($articles): ?>
  <h3 style="margin:24px 0 10px">Artículos</h3>
  <ul>
    <?php foreach ($articles as $a): ?>
    <li><a href="/articulos/<?= htmlspecialchars((string)$a['slug']) ?>"><?= htmlspecialchars((string)$a['title']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($faqs): ?>
  <h3 style="margin:24px 0 10px">Preguntas frecuentes</h3>
  <div class="notice">
    <?php foreach ($faqs as $f): ?>
    <details style="margin-bottom:8px">
      <summary style="cursor:pointer;font-weight:600"><?= htmlspecialchars((string)$f['question']) ?></summary>
      <div style="margin-top:6px;color:rgba(246,244,239,0.75)"><?= nl2br(htmlspecialchars((string)$f['answer'])) ?></div>
    </details>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
