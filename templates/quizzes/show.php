<?php
$quiz = $quiz ?? [];
$questions = $questions ?? [];
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= htmlspecialchars((string)$quiz['title']) ?></h2>
  <?php if ($quiz['description']): ?><p class="lead"><?= htmlspecialchars((string)$quiz['description']) ?></p><?php endif; ?>

  <form method="post" action="/encontra-tu-rutina/<?= htmlspecialchars((string)$quiz['slug']) ?>/resultado">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Perfushopping\Web\Support\Csrf::token()) ?>" />
    <?php foreach ($questions as $idx => $q): ?>
    <div class="card" style="padding:16px;margin-bottom:14px">
      <h5 style="margin:0 0 10px"><?= ($idx + 1) ?>. <?= htmlspecialchars((string)$q['question']) ?></h5>
      <div style="display:flex;flex-direction:column;gap:8px">
        <?php foreach ($q['options'] as $opt): ?>
        <label style="display:flex;align-items:center;gap:8px;cursor:pointer">
          <input type="radio" name="answers[<?= (int)$q['id'] ?>]" value="<?= (int)$opt['id'] ?>" required />
          <span><?= htmlspecialchars((string)$opt['label']) ?></span>
        </label>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
    <button class="btn" type="submit">Ver mi recomendación</button>
  </form>
</div>
