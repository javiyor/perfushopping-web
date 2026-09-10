<?php
$article = $article ?? [];
$products = $products ?? [];
$videos = $videos ?? [];
$seo = $seo ?? [];
?>
<div class="page">
  <nav aria-label="breadcrumb" class="mb-2">
    <a href="/articulos" style="color:rgba(246,244,239,0.7)">Artículos</a>
    <span style="color:rgba(246,244,239,0.5);margin:0 8px">/</span>
    <span><?= htmlspecialchars((string)$article['title']) ?></span>
  </nav>

  <h2 style="margin:0 0 12px"><?= htmlspecialchars((string)$article['title']) ?></h2>

  <?php if ($article['excerpt']): ?>
  <p class="lead"><?= htmlspecialchars((string)$article['excerpt']) ?></p>
  <?php endif; ?>

  <article class="notice" style="line-height:1.7">
    <?= (string)($article['content'] ?? '') ?>
  </article>

  <?php if ($products): ?>
  <h3 style="margin:24px 0 10px">Productos relacionados</h3>
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
  <h3 style="margin:24px 0 10px">Videos relacionados</h3>
  <ul>
    <?php foreach ($videos as $v): ?>
    <li><a href="/aprende/videos/<?= htmlspecialchars((string)$v['slug']) ?>"><?= htmlspecialchars((string)$v['title']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <div class="mt-3">
    <a class="btn" href="https://wa.me/?text=<?= urlencode('Hola, leí "' . ($article['title'] ?? '') . '" en Perfushopping y quería asesoramiento.') ?>" target="_blank" rel="noopener">Consultar por WhatsApp</a>
  </div>
</div>
