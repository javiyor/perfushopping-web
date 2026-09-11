<?php
$s = $block['settings'] ?? [];
$style = !empty($s['bg_color']) ? 'background:' . htmlspecialchars((string)$s['bg_color']) . ';' : '';
?>
<?php if (!empty($s['image']) || !empty($s['text'])): ?>
<div class="page" style="margin-top:24px">
  <a href="<?= htmlspecialchars((string)($s['link'] ?? '#')) ?>" style="display:block;border-radius:12px;padding:20px;text-align:center;color:inherit;text-decoration:none;<?= $style ?>">
    <?php if (!empty($s['image'])): ?>
    <img src="<?= htmlspecialchars((string)$s['image']) ?>" alt="" style="max-width:100%;border-radius:8px" />
    <?php endif; ?>
    <?php if (!empty($s['text'])): ?>
    <div style="margin-top:10px;font-weight:600"><?= htmlspecialchars((string)$s['text']) ?></div>
    <?php endif; ?>
  </a>
</div>
<?php endif; ?>
