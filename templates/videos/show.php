<?php
$video = $video ?? [];
$products = $products ?? [];
$articles = $articles ?? [];
$title = $title ?? ($video['title'] ?? 'Video');
$embedUrl = \Perfushopping\Web\Repo\Marketing\VideoRepo::youtubeEmbedUrl((string)($video['url'] ?? ''));
$youtubeUrl = \Perfushopping\Web\Repo\Marketing\VideoRepo::youtubeWatchUrl((string)($video['url'] ?? ''));
?>
<div class="page">
  <nav aria-label="breadcrumb" class="mb-2">
    <a href="/aprende" style="color:rgba(246,244,239,0.7)">Aprendé</a>
    <span style="color:rgba(246,244,239,0.5);margin:0 8px">/</span>
    <span><?= htmlspecialchars((string)$video['title']) ?></span>
  </nav>

  <h2 style="margin:0 0 12px"><?= htmlspecialchars((string)$video['title']) ?></h2>

  <?php if ($embedUrl !== ''): ?>
    <div style="position:relative;padding-top:56.25%;background:#000;border-radius:12px;overflow:hidden;margin-bottom:10px">
      <iframe src="<?= htmlspecialchars($embedUrl) ?>" title="<?= htmlspecialchars((string)$video['title']) ?>" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
    </div>
    <?php if ($youtubeUrl !== ''): ?><p style="text-align:right;margin:0 0 16px"><a href="<?= htmlspecialchars($youtubeUrl) ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-youtube"></i> Ver en YouTube</a></p><?php endif; ?>
  <?php else: ?>
    <div class="notice danger">No se pudo reconocer el enlace de YouTube. Revisá la URL en la administración del video.</div>
    <?php if (!empty($video['url'])): ?><p><a href="<?= htmlspecialchars((string)$video['url']) ?>" target="_blank" rel="noopener noreferrer">Abrir enlace original</a></p><?php endif; ?>
  <?php endif; ?>

  <?php if ($video['description_long']): ?>
  <div class="notice"><?= nl2br(htmlspecialchars((string)$video['description_long'])) ?></div>
  <?php elseif ($video['description_short']): ?>
  <div class="notice"><?= nl2br(htmlspecialchars((string)$video['description_short'])) ?></div>
  <?php endif; ?>

  <?php if ($products): ?>
  <h3 style="margin:18px 0 10px">Productos utilizados</h3>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:12px">
    <?php foreach ($products as $p): ?>
    <a href="/p/<?= (int)$p['idprodu'] ?>" class="card-link" style="text-decoration:none;color:inherit">
      <div class="card" style="padding:10px;text-align:center">
        <?php if ($p['imagen']): ?>
        <img src="/imagenes/<?= htmlspecialchars((string)$p['imagen']) ?>" alt="" style="width:100%;height:140px;object-fit:contain;margin-bottom:8px">
        <?php endif; ?>
        <div class="small"><?= htmlspecialchars((string)$p['produ']) ?></div>
        <span class="btn btn-sm mt-2"><i class="bi bi-bag"></i> Comprar producto</span>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php if ($articles): ?>
  <h3 style="margin:24px 0 10px">Artículos relacionados</h3>
  <ul>
    <?php foreach ($articles as $a): ?>
    <li><a href="/articulos/<?= htmlspecialchars((string)$a['slug']) ?>"><?= htmlspecialchars((string)$a['title']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <div class="mt-3">
    <a class="btn" href="https://wa.me/?text=<?= urlencode('Hola, vi el video "' . ($video['title'] ?? '') . '" en Perfushopping y quería asesoramiento.') ?>" target="_blank" rel="noopener">Consultar por WhatsApp</a>
  </div>
</div>
