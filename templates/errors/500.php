<?php $message = $message ?? 'Error interno.'; ?>
<div class="page">
  <h2 style="margin:0 0 10px">Error</h2>
  <div class="notice danger"><?= htmlspecialchars((string)$message) ?></div>
  <a class="btn" href="/" style="margin-top:12px">Volver</a>
</div>
