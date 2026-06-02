<?php
use Perfushopping\Web\Support\Csrf;
?>

<div class="page" style="max-width:520px;margin:18px auto 0">
  <h2 style="margin:0 0 12px">Ingresar</h2>
  <form method="post" action="/login">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
    <div style="display:grid;gap:10px">
      <input name="email" placeholder="Email" />
      <input name="password" type="password" placeholder="Clave" />
      <button class="btn" type="submit">Ingresar</button>
      <a class="btn secondary" href="/register">Crear cuenta</a>
    </div>
  </form>
</div>
