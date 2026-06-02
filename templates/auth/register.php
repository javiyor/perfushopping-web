<?php
use Perfushopping\Web\Support\Csrf;
?>

<div class="page" style="max-width:560px;margin:18px auto 0">
  <h2 style="margin:0 0 12px">Crear cuenta</h2>
  <div class="notice">Te enviamos un link para activar la cuenta y crear tu clave.</div>
  <form method="post" action="/register">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
    <div style="display:grid;gap:10px">
      <input name="name" placeholder="Nombre" />
      <input name="phone" placeholder="Telefono" />
      <input name="email" placeholder="Email" />
      <label style="display:flex;gap:10px;align-items:center">
        <input type="checkbox" name="want_wholesale" value="1" />
        <span>Quiero solicitar cuenta mayorista (requiere aprobacion)</span>
      </label>
      <label style="display:flex;gap:10px;align-items:flex-start">
        <input type="checkbox" name="accept_terms" value="1" />
        <span>
          Acepto los <a href="/terms" target="_blank" rel="noopener" style="text-decoration:underline">Terminos del sitio</a>, la
          <a href="/privacy" target="_blank" rel="noopener" style="text-decoration:underline">Politica de privacidad y cookies</a> y los
          <a href="/terms/affiliate" target="_blank" rel="noopener" style="text-decoration:underline">Terminos del programa de referidos</a>.
        </span>
      </label>
      <button class="btn" type="submit">Enviar activacion</button>
    </div>
  </form>
</div>
