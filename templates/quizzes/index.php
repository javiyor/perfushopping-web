<?php
$quizzes = $quizzes ?? [];
$title = $title ?? 'Encontrá tu rutina';
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= htmlspecialchars($title) ?></h2>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px">
    <?php foreach ($quizzes as $q): ?>
    <a href="/encontra-tu-rutina/<?= htmlspecialchars((string)$q['slug']) ?>" class="card" style="text-decoration:none;color:inherit;padding:20px">
      <strong><?= htmlspecialchars((string)$q['title']) ?></strong>
      <div class="small text-muted"><?= htmlspecialchars((string)($q['description'] ?? '')) ?></div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
