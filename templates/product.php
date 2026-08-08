<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Service\InstallmentsService;
use Perfushopping\Web\Service\PricingService;

$p = $product;
$tiva = (float)($p['tiva'] ?? 0);
$base = (float)($isWholesale ? $p['precio1'] : $p['precio']);
$withIva = $base * (1 + $tiva/100);
$img = (string)($p['imagen'] ?: ($p['image'] ?? ''));
$imgUrl = Format::uploadUrl($img);

$pricing = new PricingService();
$weekday = (int)date('w') + 1; // 1=domingo
$inst = null;
if (!$isWholesale) {
  $inst = (new InstallmentsService())->computeAllCardsPromo($pricing->cents((float)$withIva), $weekday);
}
?>

<div class="page">
  <div class="product-hero">
    <div class="gallery">
      <div class="gallery-main">
        <?php if ($imgUrl !== ''): ?>
          <img id="mainImg" src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars((string)$p['produ']) ?>" />
        <?php else: ?>
          <div style="padding:40px;color:rgba(246,244,239,0.55)">Sin imagen</div>
        <?php endif; ?>
      </div>
      <div class="gallery-strip">
        <?php if ($imgUrl !== ''): ?>
          <img src="<?= htmlspecialchars($imgUrl) ?>" data-main-target="#mainImg" alt="" />
        <?php endif; ?>
      </div>
    </div>

    <div>
      <h2 style="margin:0;font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.6px"><?= htmlspecialchars((string)$p['produ']) ?></h2>
      <div class="kpi">
        <span class="chip gold"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?> IVA inc.</span>
        <span class="chip"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($base*100))) ?> sin IVA</span>
        <?php if (!$isWholesale): ?>
          <span class="chip">3 cuotas sin interes</span>
          <?php if ($inst): ?>
            <span class="chip"><?= htmlspecialchars((string)($inst['promo']['descrip'] ?? 'Cuotas')) ?>: <?= (int)$inst['cuotas'] ?>x <?= htmlspecialchars(Format::moneyRoundedFromCents((int)$inst['cuota_cents'])) ?></span>
          <?php else: ?>
            <span class="chip">Cuotas con Mercado Pago</span>
          <?php endif; ?>
        <?php else: ?>
          <span class="chip">Transferencia</span>
        <?php endif; ?>
      </div>
      <?php if (!empty($p['nomrub']) || !empty($p['nomsub'])): ?>
        <p style="color:rgba(246,244,239,0.65)">
          <?= htmlspecialchars((string)($p['nomrub'] ?? '')) ?>
          <?php if (!empty($p['nomsub'])): ?>
            &middot; <?= htmlspecialchars(trim((string)$p['nomsub'])) ?>
          <?php endif; ?>
        </p>
      <?php endif; ?>

      <?php if (!empty($p['observ'])): ?>
        <div class="notice" style="white-space:pre-wrap"><?= htmlspecialchars((string)$p['observ']) ?></div>
      <?php endif; ?>

      <?php if (!empty($share['url'])): ?>
      <div class="share">
        <span class="share-label">Compartir</span>
        <button class="share-btn gold" type="button" onclick="shareProduct()" title="Compartir (abre el menú nativo del teléfono)"><i class="bi bi-share"></i></button>
        <a class="share-btn" href="<?= htmlspecialchars($share['facebook']) ?>" target="_blank" rel="noopener" title="Compartir en Facebook"><i class="bi bi-facebook"></i></a>
        <a class="share-btn" href="<?= htmlspecialchars($share['x']) ?>" target="_blank" rel="noopener" title="Compartir en X"><i class="bi bi-twitter-x"></i></a>
        <a class="share-btn" href="<?= htmlspecialchars($share['whatsapp']) ?>" target="_blank" rel="noopener" title="Compartir en WhatsApp"><i class="bi bi-whatsapp"></i></a>
        <a class="share-btn" href="<?= htmlspecialchars($share['telegram']) ?>" target="_blank" rel="noopener" title="Compartir en Telegram"><i class="bi bi-send"></i></a>
        <button class="share-btn" type="button" id="shareCopyBtn" onclick="copyProductLink()" title="Copiar link + texto (pegá en TikTok, Instagram, etc.)"><i class="bi bi-link-45deg"></i></button>
      </div>
      <script>
      var SHARE_PAYLOAD = <?= json_encode([
          'url' => $share['url'],
          'text' => $share['text'] . "\n" . $share['url'],
          'native' => $share['native'],
      ], JSON_UNESCAPED_UNICODE) ?>;
      function shareProduct() {
          if (navigator.share && SHARE_PAYLOAD.native) {
              navigator.share(SHARE_PAYLOAD.native).catch(function(){});
          } else {
              copyProductLink();
          }
      }
      function copyProductLink() {
          var payload = SHARE_PAYLOAD.text;
          if (navigator.clipboard && window.isSecureContext) {
              navigator.clipboard.writeText(payload).then(flashCopy, function(){ legacyCopy(payload); });
          } else {
              legacyCopy(payload);
          }
      }
      function legacyCopy(text) {
          var ta = document.createElement('textarea');
          ta.value = text;
          ta.style.position = 'fixed';
          ta.style.opacity = '0';
          document.body.appendChild(ta);
          ta.select();
          try { document.execCommand('copy'); flashCopy(); } catch(e) {}
          ta.remove();
      }
      function flashCopy() {
          var btn = document.getElementById('shareCopyBtn');
          var old = btn.innerHTML;
          btn.innerHTML = '<i class="bi bi-check-lg"></i>';
          setTimeout(function(){ btn.innerHTML = old; }, 1500);
      }
      </script>
      <?php endif; ?>

      <h3 style="margin:18px 0 10px">Variedades</h3>
      <div class="variants">
        <?php foreach ($variants as $v): ?>
          <?php $stock = (float)($v['stockact'] ?? 0); ?>
          <div class="variant">
            <h4><?= htmlspecialchars(trim((string)$v['nomgusto'])) ?></h4>
            <div class="meta">Stock: <?= ($stock > 0) ? htmlspecialchars((string)$stock) : 'Sin stock' ?></div>
            <form method="post" action="/cart/add" style="margin-top:10px">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
              <input type="hidden" name="idcodgusto" value="<?= (int)$v['idcodgusto'] ?>" />
              <div class="row">
                <div class="grow"><input type="number" name="qty" value="1" min="1" max="999" /></div>
                <button class="btn" type="submit" <?= ($stock > 0) ? '' : 'disabled' ?>>Agregar</button>
              </div>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</div>
