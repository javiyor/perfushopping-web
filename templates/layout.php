<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Env;
/** @var string $body */
$appName = Env::get('APP_NAME', 'Perfushopping');
$user = $user ?? null;
$isWholesale = $isWholesale ?? false;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($appName) ?></title>
    <link rel="icon" href="/assets/brand/favicon.ico" sizes="any" />
    <link rel="stylesheet" href="/assets/app.css" />
    <script defer src="/assets/app.js"></script>
  </head>
  <body>
    <div class="wrap">
      <div class="topbar">
        <a class="brand" href="/">
          <img class="brand-mark" src="/assets/brand/logo-header.png" alt="Perfushopping" loading="eager" decoding="async" />
          <div>
            <div class="brand-title">Perfushopping</div>
            <span class="brand-sub">Todo lo que te gusta y te hace sentir bien</span>
          </div>
        </a>
        <div class="nav">
          <?php if ($isWholesale): ?>
            <span class="pill">Modo mayorista</span>
          <?php else: ?>
            <span class="pill secondary">Minorista</span>
          <?php endif; ?>
          <a class="pill secondary" href="/cart">Carrito</a>
          <a class="pill secondary" href="/eventos/capacitaciones">Capacitaciones</a>
          <?php if ($user): ?>
            <?php if (!empty($user['force_password_change'])): ?>
              <a class="pill" href="/account/password">Cambiar clave</a>
            <?php endif; ?>
            <a class="pill secondary" href="/affiliate">Mi credito</a>
            <a class="pill secondary" href="/wholesale/request">Solicitar mayorista</a>

            <form method="post" action="/logout" style="display:inline">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
              <button class="pill secondary" type="submit">Salir</button>
            </form>
          <?php else: ?>
            <a class="pill secondary" href="/login">Ingresar</a>
          <?php endif; ?>
        </div>
      </div>

      <?php if (is_array($flash) && ($flash['text'] ?? '') !== ''): ?>
        <div class="notice <?= htmlspecialchars((string)($flash['type'] ?? '')) ?>"><?= htmlspecialchars((string)$flash['text']) ?></div>
      <?php endif; ?>

      <?= $body ?>

      <div style="margin-top:26px;color:rgba(246,244,239,0.62);font-size:12px;display:flex;gap:14px;flex-wrap:wrap;justify-content:center">
        <a href="/terms" style="text-decoration:underline">Terminos</a>
        <a href="/privacy" style="text-decoration:underline">Privacidad y cookies</a>
        <a href="/terms/affiliate" style="text-decoration:underline">Programa de referidos</a>
      </div>
    </div>
  </body>
</html>
