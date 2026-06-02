<?php
use Perfushopping\Web\Support\Csrf;
?>

<div class="page" style="max-width:560px;margin:18px auto 0">
  <h2 style="margin:0 0 12px">Activar cuenta</h2>
  <form method="post" action="/activate">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
    <input type="hidden" name="token" value="<?= htmlspecialchars((string)($token ?? '')) ?>" />
    <div style="display:grid;gap:10px">
      <input name="password" type="password" placeholder="Elegir clave (min 8)" />
      <button class="btn" type="submit">Activar</button>
    </div>
  </form>
</div>
