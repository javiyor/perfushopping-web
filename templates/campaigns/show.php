<?php
$campaign = $campaign ?? [];
$blocks = $blocks ?? [];
?>
<div class="page" style="margin-top:16px">
  <?php if ($campaign['description']): ?>
  <div class="notice"><?= nl2br(htmlspecialchars((string)$campaign['description'])) ?></div>
  <?php endif; ?>
</div>

<?php if ($blocks): ?>
  <?php
  foreach ($blocks as $block) {
      $blockFile = __DIR__ . '/../home/blocks/' . preg_replace('/[^a-z0-9_]/', '', (string)$block['block_type']) . '.php';
      if (is_file($blockFile)) {
          include $blockFile;
      }
  }
  ?>
<?php else: ?>
<div class="page">
  <h2 style="margin:0 0 10px"><?= htmlspecialchars((string)$campaign['title']) ?></h2>
  <a class="btn" href="https://wa.me/?text=<?= urlencode('Hola, estoy interesado en la campaña "' . ($campaign['title'] ?? '') . '" de Perfushopping.') ?>" target="_blank" rel="noopener">Consultar por WhatsApp</a>
</div>
<?php endif; ?>
