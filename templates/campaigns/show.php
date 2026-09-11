<?php
$campaign = $campaign ?? [];
?>
<div class="page">
  <h2 style="margin:0 0 10px"><?= htmlspecialchars((string)$campaign['title']) ?></h2>
  <?php if ($campaign['description']): ?>
  <div class="notice"><?= nl2br(htmlspecialchars((string)$campaign['description'])) ?></div>
  <?php endif; ?>
  <div class="mt-3">
    <a class="btn" href="https://wa.me/?text=<?= urlencode('Hola, estoy interesado en la campaña "' . ($campaign['title'] ?? '') . '" de Perfushopping.') ?>" target="_blank" rel="noopener">Consultar por WhatsApp</a>
  </div>
</div>
