<?php
$s = $block['settings'] ?? [];
$message = trim((string)($s['message'] ?? 'Comprá con confianza. Nos avalan las opiniones de nuestros clientes en Google.'));
$layout = ($s['layout'] ?? 'horizontal') === 'stacked' ? 'stacked' : 'horizontal';
$bg = trim((string)($s['bg_color'] ?? '#f8f9fa'));
$color = trim((string)($s['text_color'] ?? '#212529'));
$showStars = ($s['show_stars'] ?? '1') !== '0';

$googleScore = trim((string)($s['google_score'] ?? '4.8'));
$googleReviews = trim((string)($s['google_reviews'] ?? '120+'));
$googleImage = trim((string)($s['google_image'] ?? ''));
$googleUrl = trim((string)($s['google_url'] ?? '#'));

$mlScore = trim((string)($s['ml_score'] ?? ''));
$mlSales = trim((string)($s['ml_sales'] ?? ''));
$mlImage = trim((string)($s['ml_image'] ?? ''));
$mlUrl = trim((string)($s['ml_url'] ?? '#'));

$hasGoogleImage = $googleImage !== '';
$hasMlImage = $mlImage !== '';
$showGoogle = $hasGoogleImage || $googleScore !== '';
$showMl = $hasMlImage || $mlScore !== '';
?>
<style>
.trust-badges {
  padding: 56px 16px;
  background: <?= htmlspecialchars($bg) ?>;
  color: <?= htmlspecialchars($color) ?>;
}
.trust-badges__inner {
  max-width: 1100px;
  margin: 0 auto;
  text-align: center;
}
.trust-badges__message {
  font-size: clamp(1.35rem, 2.5vw, 1.85rem);
  font-weight: 700;
  margin-bottom: 28px;
  line-height: 1.25;
}
.trust-badges__items {
  display: flex;
  justify-content: center;
  align-items: stretch;
  gap: 24px;
  flex-wrap: wrap;
}
.trust-badges__items.stacked {
  flex-direction: column;
  align-items: center;
}
.trust-card {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  gap: 14px;
  min-width: 260px;
  max-width: 420px;
  padding: 32px 28px;
  border-radius: 22px;
  background: #fff;
  box-shadow: 0 14px 40px rgba(0,0,0,0.12);
  text-decoration: none;
  color: inherit;
  transition: transform .2s ease, box-shadow .2s ease;
}
.trust-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 22px 55px rgba(0,0,0,0.18);
}
.trust-card__img {
  max-height: 110px;
  width: auto;
  object-fit: contain;
}
.trust-card__stars {
  color: #ffc107;
  font-size: 1.6rem;
  letter-spacing: 4px;
  line-height: 1;
}
.trust-card__score {
  font-size: 3rem;
  font-weight: 800;
  line-height: 1;
}
.trust-card__label {
  font-size: 1.1rem;
  font-weight: 600;
  opacity: .9;
}
.trust-card__sub {
  font-size: .95rem;
  opacity: .75;
}
.trust-card__cta {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  margin-top: 4px;
  padding: 10px 20px;
  border-radius: 50px;
  background: #4285F4;
  color: #fff;
  font-weight: 600;
  font-size: .9rem;
  transition: background .2s ease;
}
.trust-card__cta:hover {
  background: #3367d6;
}
@media (max-width: 576px) {
  .trust-badges { padding: 36px 12px; }
  .trust-card { padding: 24px 20px; min-width: unset; width: 100%; }
  .trust-card__score { font-size: 2.4rem; }
}
</style>
<section class="trust-badges">
  <div class="trust-badges__inner">
    <?php if ($message !== ''): ?>
      <p class="trust-badges__message"><?= htmlspecialchars($message) ?></p>
    <?php endif; ?>
    <div class="trust-badges__items <?= $layout === 'stacked' ? 'stacked' : '' ?>">

      <?php if ($showGoogle): ?>
      <a href="<?= htmlspecialchars($googleUrl) ?>" target="_blank" rel="noopener noreferrer" class="trust-card">
        <?php if ($hasGoogleImage): ?>
          <img src="<?= htmlspecialchars($googleImage) ?>" alt="Calificación de Google" class="trust-card__img" loading="lazy" />
        <?php endif; ?>
        <?php if ($showStars): ?>
          <div class="trust-card__stars">★★★★★</div>
        <?php endif; ?>
        <?php if (!$hasGoogleImage && $googleScore !== ''): ?>
          <div class="trust-card__score"><?= htmlspecialchars($googleScore) ?></div>
        <?php elseif ($googleScore !== ''): ?>
          <div class="trust-card__score"><?= htmlspecialchars($googleScore) ?></div>
        <?php endif; ?>
        <?php if ($googleReviews !== ''): ?>
          <div class="trust-card__label"><?= htmlspecialchars($googleReviews) ?> opiniones en Google</div>
        <?php endif; ?>
        <span class="trust-card__cta">Ver opiniones <span style="font-size:1.1em">→</span></span>
      </a>
      <?php endif; ?>

      <?php if ($showMl): ?>
      <a href="<?= htmlspecialchars($mlUrl) ?>" target="_blank" rel="noopener noreferrer" class="trust-card">
        <?php if ($hasMlImage): ?>
          <img src="<?= htmlspecialchars($mlImage) ?>" alt="Reputación en Mercado Libre" class="trust-card__img" loading="lazy" />
        <?php endif; ?>
        <?php if ($mlScore !== ''): ?>
          <div class="trust-card__score"><?= htmlspecialchars($mlScore) ?></div>
        <?php endif; ?>
        <?php if ($mlSales !== ''): ?>
          <div class="trust-card__label"><?= htmlspecialchars($mlSales) ?> ventas</div>
        <?php endif; ?>
        <span class="trust-card__cta">Ver perfil <span style="font-size:1.1em">→</span></span>
      </a>
      <?php endif; ?>

    </div>
  </div>
</section>
