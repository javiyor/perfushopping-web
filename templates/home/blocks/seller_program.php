<?php
$s = $block['settings'] ?? [];
$eyebrow = trim((string)($s['eyebrow'] ?? 'GANÁ CON PERFUSHOPPING'));
$title = trim((string)($s['title'] ?? 'Registrate y convertite en vendedor Perfushopping'));
$body = trim((string)($s['body'] ?? 'Compartí y recomendá nuestros productos y ganá hasta un 10% de comisión. Usá tus ganancias para comprarte lo que quieras en Perfushopping o retirá una parte en efectivo.'));
$commissionPct = (int)($s['commission_pct'] ?? 10);
$imageDesktop = trim((string)($s['image_desktop'] ?? ''));
$imageMobile = trim((string)($s['image_mobile'] ?? ''));
$videoUrl = trim((string)($s['video_url'] ?? ''));
$bgStyle = !empty($s['bg_color']) ? 'background:' . htmlspecialchars((string)$s['bg_color']) . ';' : '';
$primaryText = trim((string)($s['primary_cta_text'] ?? 'QUIERO SER VENDEDOR'));
$primaryUrl = trim((string)($s['primary_cta_url'] ?? '/login'));
$secondaryText = trim((string)($s['secondary_cta_text'] ?? 'CÓMO FUNCIONA'));
$secondaryUrl = trim((string)($s['secondary_cta_url'] ?? '/terms/affiliate'));
$benefits = $s['benefits'] ?? ['Registrate gratis', 'Compartí productos', 'Ganá hasta ' . $commissionPct . '% de comisión', 'Usá tu saldo o retiralo parcialmente'];
if (!is_array($benefits)) $benefits = [];
$loggedTitle = trim((string)($s['logged_in_title'] ?? 'Ganá recomendando productos Perfushopping'));
$loggedBody = trim((string)($s['logged_in_body'] ?? 'Compartí tus productos favoritos y ganá comisiones por las ventas que generes. Podés usar tus ganancias para comprar en Perfushopping o retirar una parte en efectivo.'));
$loggedCtaText = trim((string)($s['logged_in_cta_text'] ?? 'VER MI CRÉDITO'));
$campaignId = trim((string)($s['tracking_campaign_id'] ?? ''));

$isLoggedIn = !empty($user) && is_array($user) && !empty($user['id']);
$refLink = '';
if ($isLoggedIn) {
    try {
        $code = (new \Perfushopping\Web\Repo\AffiliateRepo())->ensureForUser((int)$user['id']);
        $appUrl = rtrim((string)($_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'https://perfushopping.ar'), '/');
        $refLink = $appUrl . '/?ref=' . urlencode($code);
    } catch (\Throwable $e) {
        $refLink = '';
    }
}

$blockId = (int)($block['id'] ?? 0);
$eventPayload = json_encode([
    'source_page' => 'home',
    'block_id' => $blockId,
    'campaign_id' => $campaignId,
    'user_type' => $isLoggedIn ? 'logged_in' : 'guest',
    'device' => 'desktop',
], JSON_UNESCAPED_UNICODE);
?>
<section class="page seller-program-block" style="margin-top:24px;<?= $bgStyle ?>" data-block-id="<?= $blockId ?>" data-campaign-id="<?= htmlspecialchars($campaignId) ?>">
  <?php if (!$isLoggedIn): ?>
  <div class="seller-program-inner" style="display:flex;flex-wrap:wrap;gap:24px;align-items:center">
    <div class="seller-program-media" style="flex:1;min-width:280px">
      <?php if ($videoUrl !== ''): ?>
      <div class="ratio ratio-16x9">
        <video controls poster="<?= htmlspecialchars($imageDesktop !== '' ? $imageDesktop : ($imageMobile !== '' ? $imageMobile : '')) ?>" preload="none" style="width:100%;border-radius:12px">
          <source src="<?= htmlspecialchars($videoUrl) ?>" />
        </video>
      </div>
      <?php elseif ($imageDesktop !== '' || $imageMobile !== ''): ?>
      <picture>
        <?php if ($imageDesktop !== ''): ?><source media="(min-width:768px)" srcset="<?= htmlspecialchars($imageDesktop) ?>"><?php endif; ?>
        <?php if ($imageMobile !== ''): ?><source media="(max-width:767px)" srcset="<?= htmlspecialchars($imageMobile) ?>"><?php endif; ?>
        <img src="<?= htmlspecialchars($imageDesktop !== '' ? $imageDesktop : $imageMobile) ?>" alt="Programa de vendedores Perfushopping" loading="lazy" style="width:100%;border-radius:12px" />
      </picture>
      <?php endif; ?>
    </div>
    <div class="seller-program-content" style="flex:1;min-width:280px">
      <?php if ($eyebrow !== ''): ?><div class="seller-program-eyebrow" style="font-size:12px;letter-spacing:1px;text-transform:uppercase;opacity:.8;margin-bottom:8px"><?= htmlspecialchars($eyebrow) ?></div><?php endif; ?>
      <h2 style="margin:0 0 12px;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars($title) ?></h2>
      <p style="margin:0 0 16px;line-height:1.55"><?= nl2br(htmlspecialchars(str_replace('{{commission_pct}}', (string)$commissionPct, $body))) ?></p>
      <?php if ($benefits): ?>
      <ul style="list-style:none;padding:0;margin:0 0 18px;display:flex;flex-wrap:wrap;gap:10px 24px">
        <?php foreach ($benefits as $b): ?>
        <li style="display:flex;align-items:center;gap:6px"><i class="bi bi-check-circle" style="color:var(--gold)"></i> <?= htmlspecialchars(str_replace('{{commission_pct}}', (string)$commissionPct, (string)$b)) ?></li>
        <?php endforeach; ?>
      </ul>
      <?php endif; ?>
      <div style="display:flex;flex-wrap:wrap;gap:12px">
        <a class="btn seller-program-primary" href="<?= htmlspecialchars($primaryUrl) ?>" data-cta="primary"><?= htmlspecialchars($primaryText) ?></a>
        <a class="btn secondary seller-program-secondary" href="<?= htmlspecialchars($secondaryUrl) ?>" data-cta="secondary"><?= htmlspecialchars($secondaryText) ?></a>
      </div>
    </div>
  </div>
  <?php else: ?>
  <div class="seller-program-logged" style="border-radius:12px;padding:20px;text-align:center">
    <h3 style="margin:0 0 10px;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.6px"><?= htmlspecialchars($loggedTitle) ?></h3>
    <p style="margin:0 auto 14px;max-width:620px;line-height:1.55"><?= nl2br(htmlspecialchars(str_replace('{{commission_pct}}', (string)$commissionPct, $loggedBody))) ?></p>
    <?php if ($refLink !== ''): ?>
    <div style="display:flex;flex-wrap:wrap;justify-content:center;gap:10px;align-items:center;margin-bottom:14px">
      <input value="<?= htmlspecialchars($refLink) ?>" readonly onclick="this.select()" style="min-width:260px;max-width:90%" />
      <button type="button" class="btn secondary" onclick="navigator.clipboard.writeText(this.previousElementSibling.value);this.textContent='Copiado'">Copiar link</button>
    </div>
    <?php endif; ?>
    <a class="btn seller-program-primary" href="/affiliate" data-cta="logged_in"><?= htmlspecialchars($loggedCtaText) ?></a>
  </div>
  <?php endif; ?>
</section>

<script>
(function(){
  var block = document.querySelector('[data-block-id="<?= $blockId ?>"]');
  if (!block) return;
  var payload = <?= $eventPayload ?>;
  payload.device = window.innerWidth < 768 ? 'mobile' : 'desktop';
  navigator.sendBeacon ? navigator.sendBeacon('/api/a/event', new URLSearchParams({t:'view_seller_program_banner', sid: localStorage.getItem('pfs_sid')||'', p: window.location.pathname, payload: JSON.stringify(payload)})) : null;
  block.querySelectorAll('a[data-cta]').forEach(function(a){
    a.addEventListener('click', function(){
      var clickPayload = Object.assign({}, payload);
      clickPayload.cta = a.dataset.cta;
      var eventType = a.dataset.cta === 'secondary' ? 'click_seller_program_learn_more' : 'click_seller_program_register';
      navigator.sendBeacon ? navigator.sendBeacon('/api/a/event', new URLSearchParams({t:eventType, sid: localStorage.getItem('pfs_sid')||'', p: window.location.pathname, payload: JSON.stringify(clickPayload)})) : null;
    });
  });
})();
</script>
