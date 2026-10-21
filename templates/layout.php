<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Env;
use Perfushopping\Web\Support\Format;
/** @var string $body */
$appName = Env::get('APP_NAME', 'Perfushopping');
$empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();
$portadaConfigGlobal = (new \Perfushopping\Web\Repo\PortadaRepo())->getConfig();
$footerText = trim((string)($portadaConfigGlobal['footer_text'] ?? ''));
$temaTienda = \Perfushopping\Web\Repo\PortadaRepo::tema($portadaConfigGlobal);
$temaTiendaStyle = '--bg0:' . $temaTienda['color_fondo'] . ';'
    . '--bg1:' . $temaTienda['color_fondo_secundario'] . ';'
    . '--card:' . $temaTienda['color_tarjeta'] . ';'
    . '--card2:' . $temaTienda['color_tarjeta'] . ';'
    . '--text:' . $temaTienda['color_texto'] . ';'
    . '--gold:' . $temaTienda['color_acento'] . ';'
    . '--gold2:' . $temaTienda['color_acento'] . ';'
    . '--line:color-mix(in srgb, ' . $temaTienda['color_acento'] . ' 32%, transparent);'
    . '--product-font:' . match ($temaTienda['fuente_tarjeta']) {
        'serif' => 'Georgia, "Times New Roman", serif',
        'rounded' => '"Trebuchet MS", Arial, sans-serif',
        default => 'ui-sans-serif, system-ui, -apple-system, "Segoe UI", Arial, sans-serif',
    } . ';'
    . '--product-name-color:' . $temaTienda['color_nombre_producto'] . ';'
    . '--product-name-size:' . $temaTienda['tamano_nombre_producto'] . 'px;'
    . '--product-price-color:' . $temaTienda['color_precio_producto'] . ';'
    . '--product-price-size:' . $temaTienda['tamano_precio_producto'] . 'px;';
$appCssVersion = (string)(@filemtime(__DIR__ . '/../public/assets/app.css') ?: 1);
$empresaNombre = htmlspecialchars($empresa['nomemp'] ?? $appName);
$empresaLogo = trim((string)($empresa['logo'] ?? ''));
$empresaLogoUrl = $empresaLogo !== '' ? Format::uploadUrl($empresaLogo) : '/assets/brand/logo-header.png';
$user = $user ?? null;
$isWholesale = $isWholesale ?? false;
$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

$stats = new \Perfushopping\Web\Repo\WebStatsRepo();
try {
    $statsIdprodu = (isset($product) && is_array($product)) ? (int)($product['idprodu'] ?? 0) : 0;
    $statsUserId = (is_array($user) && !empty($user['id'])) ? (int)$user['id'] : 0;
    $stats->registrarVisita((string)($_SERVER['REQUEST_URI'] ?? ''), $statsIdprodu, $statsUserId, session_id(), (string)($_SERVER['REMOTE_ADDR'] ?? ''));
    if ($statsIdprodu > 0) {
        $stats->incrementarProducto($statsIdprodu);
    }
} catch (\Throwable $e) {
}
?>
<!doctype html>
<html lang="es">
  <head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars((string)($pageTitle ?? '')) ?><?= ($pageTitle ?? '') !== '' ? ' · ' : '' ?><?= $empresaNombre ?></title>
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($empresaLogoUrl) ?>" sizes="any" />
    <?php
    $seo = $seo ?? null;
    if (is_array($seo) && !empty($seo['title'])): ?>
    <meta name="description" content="<?= htmlspecialchars((string)($seo['meta_description'] ?? '')) ?>" />
    <link rel="canonical" href="<?= htmlspecialchars((string)($seo['canonical'] ?? Format::baseUrl())) ?>" />
    <meta property="og:title" content="<?= htmlspecialchars((string)($seo['title'])) ?>" />
    <meta property="og:description" content="<?= htmlspecialchars((string)($seo['meta_description'] ?? '')) ?>" />
    <?php if (!empty($seo['og_image'])): ?><meta property="og:image" content="<?= htmlspecialchars((string)$seo['og_image']) ?>" /><?php endif; ?>
    <?php endif; ?>
    <?= $head ?? '' ?>
    <link rel="stylesheet" href="/assets/app.css?v=<?= htmlspecialchars($appCssVersion) ?>" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" />
    <style>
      .thumb::before {
        background-image: url("<?= htmlspecialchars($empresaLogoUrl) ?>");
      }
    </style>
    <script defer src="/assets/app.js"></script>
    <!-- Meta Pixel Code -->
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '815822046012434');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=815822046012434&ev=PageView&noscript=1"
    /></noscript>
    <!-- End Meta Pixel Code -->
  </head>
  <body data-theme="<?= htmlspecialchars($temaTienda['mode']) ?>" data-store-theme-mode="<?= htmlspecialchars($temaTienda['mode']) ?>" data-store-template="<?= htmlspecialchars($temaTienda['plantilla']) ?>" style="<?= htmlspecialchars($temaTiendaStyle) ?>">
    <div class="wrap">
      <div class="topbar">
        <a class="brand" href="/">
          <img class="brand-mark" src="<?= $empresaLogoUrl ?>" alt="<?= $empresaNombre ?>" loading="eager" decoding="async" onerror="this.style.display='none'" />
          <div>
            <div class="brand-title"><?= $empresaNombre ?></div>
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
          <a class="pill secondary" href="/aprende">Aprendé</a>
          <a class="pill secondary" href="/articulos">Artículos</a>
          <a class="pill secondary" href="/eventos/capacitaciones">Capacitaciones</a>
          <a class="pill promo-link" href="/promociones">💳 Promociones</a>
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

      <?php if ($footerText !== ''): ?>
      <footer style="margin-top:26px;padding:18px 12px 0;border-top:1px solid var(--line);color:var(--text);opacity:.78;text-align:center;font-size:13px;line-height:1.6">
        <?= nl2br(htmlspecialchars($footerText)) ?>
      </footer>
      <?php endif; ?>
      <div style="margin-top:26px;color:rgba(246,244,239,0.62);font-size:12px;display:flex;gap:14px;flex-wrap:wrap;justify-content:center">
        <a href="/terms" style="text-decoration:underline">Terminos</a>
        <a href="/privacy" style="text-decoration:underline">Privacidad y cookies</a>
        <a href="/terms/affiliate" style="text-decoration:underline">Programa de referidos</a>
      </div>
    </div>
    <script>
    (function(){
      try {
        var sid = localStorage.getItem('pfs_sid') || 's-' + Math.random().toString(36).slice(2);
        localStorage.setItem('pfs_sid', sid);
        document.cookie = 'pfs_sid=' + encodeURIComponent(sid) + ';path=/;SameSite=Lax';
        var payload = {
          t: 'pageview',
          sid: sid,
          u: window.location.href,
          p: window.location.pathname + window.location.search,
          pid: <?= (int)(isset($product) && is_array($product) ? ($product['idprodu'] ?? 0) : 0) ?>,
          v: null
        };
        navigator.sendBeacon ? navigator.sendBeacon('/api/a/event', new URLSearchParams(payload)) : fetch('/api/a/event', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:new URLSearchParams(payload), keepalive:true}).catch(function(){});
      } catch (e) {}
    })();
    </script>
  </body>
</html>
