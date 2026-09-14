<?php
$s = $block['settings'] ?? [];
$message = trim((string)($s['message'] ?? 'Comprá con confianza. Nos eligen miles de clientes y nuestra reputación habla por sí sola.'));
$layout = ($s['layout'] ?? 'horizontal') === 'stacked' ? 'stacked' : 'horizontal';
$bg = trim((string)($s['bg_color'] ?? '#f8f9fa'));
$color = trim((string)($s['text_color'] ?? '#212529'));
$showStars = ($s['show_stars'] ?? '1') !== '0';

$googleScore = trim((string)($s['google_score'] ?? '4.8'));
$googleReviews = trim((string)($s['google_reviews'] ?? '120+'));
$googleImage = trim((string)($s['google_image'] ?? ''));
$googleUrl = trim((string)($s['google_url'] ?? '#'));

$mlScore = trim((string)($s['ml_score'] ?? 'Platinum'));
$mlSales = trim((string)($s['ml_sales'] ?? '10.000+'));
$mlImage = trim((string)($s['ml_image'] ?? ''));
$mlUrl = trim((string)($s['ml_url'] ?? '#'));

$hasGoogleImage = $googleImage !== '';
$hasMlImage = $mlImage !== '';
?>
<style>
.trust-badges {
  padding: 32px 0;
  background: <?= htmlspecialchars($bg) ?>;
  color: <?= htmlspecialchars($color) ?>;
}
.trust-badges__inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 16px;
  text-align: center;
}
.trust-badges__message {
  font-size: 1.1rem;
  margin-bottom: 20px;
  font-weight: 500;
}
.trust-badges__items {
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 32px;
  flex-wrap: wrap;
}
.trust-badges__items.stacked {
  flex-direction: column;
  gap: 16px;
}
.trust-badge {
  display: inline-flex;
  align-items: center;
  gap: 12px;
  text-decoration: none;
  color: inherit;
  padding: 12px 18px;
  border-radius: 10px;
  background: rgba(255,255,255,0.6);
  box-shadow: 0 2px 6px rgba(0,0,0,0.06);
  transition: transform .15s ease, box-shadow .15s ease;
}
.trust-badge:hover {
  transform: translateY(-2px);
  box-shadow: 0 6px 14px rgba(0,0,0,0.1);
}
.trust-badge__img {
  height: 44px;
  width: auto;
  object-fit: contain;
}
.trust-badge__text {
  text-align: left;
  line-height: 1.25;
}
.trust-badge__title {
  font-weight: 700;
  font-size: 1rem;
}
.trust-badge__subtitle {
  font-size: .85rem;
  opacity: .9;
}
.trust-badge__stars {
  color: #ffc107;
  letter-spacing: 2px;
}
</style>
<section class="trust-badges">
  <div class="trust-badges__inner">
    <?php if ($message !== ''): ?>
      <p class="trust-badges__message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <div class="trust-badges__items <?= $layout === 'stacked' ? 'stacked' : '' ?>">

      <a href="<?= htmlspecialchars($googleUrl) ?>" target="_blank" rel="noopener noreferrer" class="trust-badge">
        <?php if ($hasGoogleImage): ?>
          <img src="<?= htmlspecialchars($googleImage) ?>" alt="Google opiniones" class="trust-badge__img" loading="lazy" />
        <?php else: ?>
          <div class="trust-badge__text">
            <div class="trust-badge__title">
              <?php if ($showStars): ?><span class="trust-badge__stars">★★★★★</span> <?php endif; ?>
              <?= htmlspecialchars($googleScore) ?>
            </div>
            <div class="trust-badge__subtitle"><?= htmlspecialchars($googleReviews) ?> opiniones en Google</div>
          </div>
        <?php endif; ?>
      </a>

      <a href="<?= htmlspecialchars($mlUrl) ?>" target="_blank" rel="noopener noreferrer" class="trust-badge">
        <?php if ($hasMlImage): ?>
          <img src="<?= htmlspecialchars($mlImage) ?>" alt="Mercado Libre reputación" class="trust-badge__img" loading="lazy" />
        <?php else: ?>
          <div class="trust-badge__text">
            <div class="trust-badge__title">Mercado Libre <?= htmlspecialchars($mlScore) ?></div>
            <div class="trust-badge__subtitle"><?= htmlspecialchars($mlSales) ?> ventas</div>
          </div>
        <?php endif; ?>
      </a>

    </div>
  </div>
</section>
