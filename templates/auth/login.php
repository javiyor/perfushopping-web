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

  <form method="post" action="/activate/resend" style="margin-top:14px;border-top:1px solid rgba(255,255,255,0.12);padding-top:14px">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
    <div style="display:grid;gap:10px">
      <strong>Reenviar activacion</strong>
      <input name="email" placeholder="Tu email registrado" />
      <button class="btn secondary" type="submit">Reenviar email de activacion</button>
    </div>
  </form>
</div>
