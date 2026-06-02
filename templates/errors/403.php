<?php $message = $message ?? 'Acceso denegado.'; ?>
<div class="page">
  <h2 style="margin:0 0 10px">403</h2>
  <div class="notice danger"><?= htmlspecialchars((string)$message) ?></div>
  <a class="btn" href="/" style="margin-top:12px">Volver</a>
</div>
