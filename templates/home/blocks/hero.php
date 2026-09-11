<?php
$s = $block['settings'] ?? [];
?>
<div class="hero" style="text-align:center">
  <?php if (!empty($s['image'])): ?>
  <img src="<?= htmlspecialchars((string)$s['image']) ?>" alt="" style="max-width:320px;width:50%;border-radius:22px;margin-bottom:14px" />
  <?php endif; ?>
  <h1><?= htmlspecialchars((string)($s['heading'] ?? 'Perfushopping')) ?></h1>
  <p><?= htmlspecialchars((string)($s['subheading'] ?? '')) ?></p>
  <?php if (!empty($s['cta_url'])): ?>
  <a class="btn" href="<?= htmlspecialchars((string)$s['cta_url']) ?>"><?= htmlspecialchars((string)($s['cta_text'] ?? 'Ver más')) ?></a>
  <?php endif; ?>
</div>
