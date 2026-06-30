<?php
use Perfushopping\Web\Support\Csrf;

$forcePasswordChange = !empty($forcePasswordChange);
?>

<div class="page" style="max-width:560px;margin:18px auto 0">
  <h2 style="margin:0 0 12px">Cambiar clave</h2>
  <?php if ($forcePasswordChange): ?>
    <div class="notice">Tu cuenta requiere cambiar la clave antes de seguir navegando.</div>
  <?php endif; ?>
  <form method="post" action="/account/password">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
    <div style="display:grid;gap:10px">
      <input name="current_password" type="password" placeholder="Clave actual" required />
      <input name="new_password" type="password" placeholder="Nueva clave (min 8)" minlength="8" required />
      <input name="confirm_password" type="password" placeholder="Confirmar nueva clave" minlength="8" required />
      <button class="btn" type="submit">Guardar nueva clave</button>
    </div>
  </form>
</div>
