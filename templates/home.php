<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Service\AuthService;

$q = (string)($_GET['q'] ?? '');
$codrub = (int)($_GET['codrub'] ?? 0);
$codsub = (int)($_GET['codsub'] ?? 0);
$isNovedades = ($q === '' && $codrub === 0 && $codsub === 0);
$empresa = (new \Perfushopping\Web\Repo\EmpresaRepo())->getDefault();
$empresaNombre = htmlspecialchars($empresa['nomemp'] ?? 'Perfushopping');
$empresaLogo = trim((string)($empresa['logo'] ?? ''));
$empresaBanner = $empresaLogo !== '' ? Format::uploadUrl($empresaLogo) : '/assets/brand/logo-banner.jpg';
$empresaTel = htmlspecialchars($empresa['telefono'] ?? '3482 765798');
$empresaMail = htmlspecialchars($empresa['mail'] ?? 'clientes@perfushopping.com.ar');
$empresaDir = htmlspecialchars($empresa['dire_emp'] ?? '9 de julio 1610 - Hipolito Irigoyen 465 - Reconquista, Santa Fe - Argentina');
$empresaWhatsapp = preg_replace('/[^0-9]/', '', $empresa['telefono'] ?? '543482765798') ?: '543482765798';
$empresaHorarios = 'Lunes a Viernes 08:00-19:00 | Sabados 08:00-13:00';
$defaultBenefs = ['Envios a todo el pais', '3 y 6 cuotas con Mercado Pago', 'Retire en el local'];
$benefs = [];
foreach ([1, 2, 3] as $bi) {
    $v = trim((string)($empresa['benef' . $bi] ?? ''));
    $benefs[] = $v !== '' ? $v : $defaultBenefs[$bi - 1];
}
?>

<?php if (!empty($blocks)): ?>
  <?php
  $trustBlock = null;
  foreach ($blocks as $block) {
      if (($block['block_type'] ?? '') === 'trust_badges' && $trustBlock === null) {
          $trustBlock = $block;
          continue;
      }
      $blockFile = __DIR__ . '/home/blocks/' . preg_replace('/[^a-z0-9_]/', '', (string)$block['block_type']) . '.php';
      if (is_file($blockFile)) {
          include $blockFile;
      }
  }
  $trustSettings = is_array($trustBlock) && is_array($trustBlock['settings'] ?? null) ? $trustBlock['settings'] : [];
  $googleScore = trim((string)($trustSettings['google_score'] ?? '4.8'));
  if ($googleScore === '') $googleScore = '4.8';
  $googleReviews = trim((string)($trustSettings['google_reviews'] ?? ''));
  $googleUrl = trim((string)($trustSettings['google_url'] ?? ''));
  ?>
<?php else: ?>
<div class="hero">
  <div style="display:flex;justify-content:center;">
    <img src="<?= htmlspecialchars($empresaBanner) ?>" alt="<?= $empresaNombre ?>" loading="eager" decoding="async" style="width:26%;max-width:300px;height:auto;border-radius:22px;border:1px solid rgba(216,178,90,0.18);box-shadow:0 22px 70px rgba(0,0,0,0.55);" />
  </div>
  <h1><?= $empresaNombre ?></h1>
  <p>Carrito de compras. Compra, paga facil y seguro en cuotas con Mercado Pago.</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:14px">
    <?php foreach ($benefs as $i => $benef): ?>
      <span class="<?= $i === 0 ? 'pill' : 'pill secondary' ?>"><?= htmlspecialchars($benef) ?></span>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php if (($googleUrl ?? '') !== '' && $googleUrl !== '#'): ?>
<div style="text-align:right;margin:8px 2px 0">
  <a href="<?= htmlspecialchars($googleUrl) ?>" target="_blank" rel="noopener" style="font-size:11px;color:rgba(246,244,239,0.8);text-decoration:none;border:1px solid rgba(216,178,90,0.4);border-radius:20px;padding:2px 10px;display:inline-block">★ <?= htmlspecialchars($googleScore) ?> en Google<?= $googleReviews !== '' ? ' · ' . htmlspecialchars($googleReviews) . ' opiniones' : '' ?></a>
</div>
<?php endif; ?>

<div class="page" style="margin-top:16px">
  <?php if ($isNovedades): ?>
    <div style="margin-top:10px;color:rgba(246,244,239,0.72);line-height:1.55">
      <i class="bi bi-whatsapp" aria-label="WhatsApp"></i> Tel: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a> &middot; <i class="bi bi-envelope-fill" aria-label="Correo"></i> Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a><br />
      Direcciones: <?= $empresaDir ?><br />
      <i class="bi bi-instagram" aria-label="Instagram"></i> Instagram: <a href="https://www.instagram.com/perfushopping" target="_blank" rel="noopener" style="text-decoration:underline">@perfushopping</a>
    </div>
    <?php
    $portadaTitles = ['auto'=>'Novedades','rubro'=>'Selección por rubro','marca'=>'Selección por marca','ultimos'=>'Últimos ingresos','manual'=>'Destacados'];
    $portadaLabel = $portadaTitles[(string)($portadaInfo['modo'] ?? 'auto')] ?? 'Novedades';
    $bannerText = trim((string)($portadaInfo['banner_text'] ?? ''));
    $showBanner = (($portadaInfo['modo'] ?? '') === 'manual') && $bannerText !== '';
    if ($showBanner):
        $bSizes = ['chico' => '1.1rem', 'normal' => '1.4rem', 'grande' => '1.9rem'];
        $bSize = $bSizes[(string)($portadaInfo['banner_size'] ?? '')] ?? '1.9rem';
        $bColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($portadaInfo['banner_color'] ?? '')) ? (string)$portadaInfo['banner_color'] : '#C9A227';
        $bBg = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($portadaInfo['banner_bg'] ?? '')) ? (string)$portadaInfo['banner_bg'] : '';
        $bStyle = 'margin:10px 0 0;text-align:center;letter-spacing:0.7px;font-family:Georgia,\'Times New Roman\',serif;'
            . 'font-size:' . $bSize . ';color:' . $bColor . ';'
            . 'font-weight:' . (!empty($portadaInfo['banner_bold']) ? 'bold' : 'normal') . ';'
            . 'font-style:' . (!empty($portadaInfo['banner_italic']) ? 'italic' : 'normal') . ';';
        if ($bBg !== '') {
            $bStyle .= 'background:' . $bBg . ';padding:6px 18px;border-radius:10px;display:inline-block;';
        }
        $bannerSub = trim((string)($portadaInfo['banner_sub'] ?? ''));
        $bSubSize = $bSizes[(string)($portadaInfo['banner_sub_size'] ?? '')] ?? '1.4rem';
        $bSubColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($portadaInfo['banner_sub_color'] ?? '')) ? (string)$portadaInfo['banner_sub_color'] : '#FFFFFF';
        $bSubStyle = 'font-family:Georgia,\'Times New Roman\',serif;letter-spacing:0.7px;margin-top:2px;'
            . 'font-size:' . $bSubSize . ';color:' . $bSubColor . ';'
            . 'font-weight:' . (!empty($portadaInfo['banner_sub_bold']) ? 'bold' : 'normal') . ';'
            . 'font-style:' . (!empty($portadaInfo['banner_sub_italic']) ? 'italic' : 'normal') . ';';
    ?>
    <div style="text-align:center"><div style="<?= $bStyle ?>">
        <div><?= htmlspecialchars($bannerText) ?></div>
        <?php if ($bannerSub !== ''): ?><div style="<?= $bSubStyle ?>"><?= htmlspecialchars($bannerSub) ?></div><?php endif; ?>
    </div></div>
    <?php else: ?>
    <h3 style="margin:10px 0 0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px"><?= htmlspecialchars($portadaLabel) ?></h3>
    <?php endif; ?>
  <?php else: ?>
    <h3 style="margin:0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Catalogo</h3>
    <div style="margin-top:10px;color:rgba(246,244,239,0.72);line-height:1.55">
      <i class="bi bi-whatsapp" aria-label="WhatsApp"></i> Tel: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a> &middot; <i class="bi bi-envelope-fill" aria-label="Correo"></i> Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a><br />
      Direcciones: <?= $empresaDir ?><br />
      <i class="bi bi-instagram" aria-label="Instagram"></i> Instagram: <a href="https://www.instagram.com/perfushopping" target="_blank" rel="noopener" style="text-decoration:underline">@perfushopping</a>
    </div>
  <?php endif; ?>
</div>

<?php
$promos = $promos ?? [];
$promosByProduct = [];
foreach ($promos as $pr) {
    $idp = (int)($pr['idprodu'] ?? 0);
    if ($idp <= 0 || isset($promosByProduct[$idp])) continue;
    $prTiva = (float)($pr['tiva'] ?? 0);
    $prNormalNet = (float)($isWholesale ? ($pr['precio1'] ?? 0) : ($pr['precio'] ?? 0));
    $prPromoNet = (float)($pr['promo_precio'] ?? 0);
    if ($prNormalNet <= 0 || $prPromoNet <= 0 || $prPromoNet >= $prNormalNet) continue;
    $promosByProduct[$idp] = [
        'idprodu' => $idp,
        'produ' => (string)($pr['produ'] ?? ''),
        'imagen' => (string)($pr['imagen'] ?? ''),
        'normal' => $prNormalNet * (1 + $prTiva / 100),
        'promo' => $prPromoNet * (1 + $prTiva / 100),
        'off' => (int)round((1 - $prPromoNet / $prNormalNet) * 100),
        'variant' => (int)($pr['first_variant_id'] ?? 0),
    ];
}
?>
<?php
$promos = $promos ?? [];
$promoBanners = $promoBanners ?? [];
$showPromos = $promosByProduct || $promoBanners;
?>
<?php if ($showPromos): ?>
<style>
.promo-banda { margin-top:18px; }
.promo-banda-head { display:flex;justify-content:space-between;align-items:center;margin-bottom:8px; }
.promo-banda-head h3 { margin:0;color:#ff6b6b;font-family:Georgia,'Times New Roman',serif;letter-spacing:0.7px; }
.promo-nav { display:flex;gap:6px; }
.promo-nav button { width:32px;height:32px;border-radius:50%;border:1px solid rgba(216,178,90,0.5);background:transparent;color:#f6f4ef;font-size:18px;line-height:1;cursor:pointer; }
.promo-track { display:flex;gap:12px;overflow-x:auto;scroll-snap-type:x mandatory;padding:4px 2px 10px; }
.promo-card { flex:0 0 200px;scroll-snap-align:start;background:rgba(255,255,255,0.05);border:1px solid rgba(216,178,90,0.35);border-radius:14px;padding:10px;text-align:center; }
.promo-card img { width:100%;height:130px;object-fit:contain;border-radius:8px;background:#fff; }
.promo-card-title { font-size:13px;margin:6px 0;min-height:32px;color:#f6f4ef; }
.promo-old { font-size:12px;color:rgba(246,244,239,0.55); }
.promo-off { display:inline-block;background:#c0392b;color:#fff;font-weight:800;font-size:13px;border-radius:8px;padding:1px 8px;margin:3px 0; }
.promo-new { font-size:1.4rem;font-weight:800;color:#7CFC9A;line-height:1.2; }
.promo-add { margin-top:8px;width:100%;padding:8px;border:none;border-radius:10px;background:linear-gradient(135deg,#d8b25a,#f3d48a);color:#17140f;font-weight:800;cursor:pointer; }
.promo-banner-card { display:flex;flex-direction:column;justify-content:center; }
.promo-banner-title { font-size:1.15rem;font-weight:800;line-height:1.25;font-family:Georgia,'Times New Roman',serif; }
.promo-banner-text { font-size:12px;margin-top:6px;opacity:0.92;line-height:1.4; }
.promo-banner-btn { margin-top:10px;display:inline-block;padding:8px 14px;border-radius:10px;background:rgba(255,255,255,0.92);color:#17140f;font-weight:800;font-size:13px;text-decoration:none; }
</style>
<div class="promo-banda">
  <div class="promo-banda-head">
    <h3>🔥 Promociones</h3>
    <div class="promo-nav">
      <button type="button" onclick="promoScroll(-1)" aria-label="Anterior">‹</button>
      <button type="button" onclick="promoScroll(1)" aria-label="Siguiente">›</button>
    </div>
  </div>
  <div class="promo-track" id="promoTrack">
    <?php foreach ($promosByProduct as $pr): ?>
    <div class="promo-card">
      <a href="/p/<?= $pr['idprodu'] ?>" style="text-decoration:none;color:inherit">
        <?php if ($pr['imagen'] !== ''): ?><img src="<?= htmlspecialchars(Format::uploadUrl($pr['imagen'])) ?>" alt="" loading="lazy" /><?php endif; ?>
        <div class="promo-card-title"><?= htmlspecialchars(mb_substr($pr['produ'], 0, 45)) ?></div>
      </a>
      <div class="promo-old"><s>$<?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($pr['normal'] * 100))) ?></s></div>
      <div><span class="promo-off">-<?= $pr['off'] ?>% HOY</span></div>
      <div class="promo-new">$<?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($pr['promo'] * 100))) ?></div>
      <?php if ($pr['variant'] > 0): ?>
      <form method="post" action="/cart/add">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
        <input type="hidden" name="idcodgusto" value="<?= $pr['variant'] ?>" />
        <input type="hidden" name="qty" value="1" />
        <button class="promo-add" type="submit">Agregar al carrito</button>
      </form>
      <?php endif; ?>
    </div>
    <?php endforeach; ?>
    <?php foreach ($promoBanners as $bn):
        $bnColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($bn['color'] ?? '')) ? (string)$bn['color'] : '#FFFFFF';
        $bnBg = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($bn['bg'] ?? '')) ? (string)$bn['bg'] : '';
        $bnHref = (string)($bn['href'] ?? '');
        $bnMarca = trim((string)($bn['nomsub'] ?? ''));
    ?>
    <div class="promo-card promo-banner-card" style="color:<?= $bnColor ?>;<?= $bnBg !== '' ? 'background:' . $bnBg . ';border-color:transparent;' : '' ?>">
      <div class="promo-banner-title"><?= htmlspecialchars((string)($bn['titulo'] ?? '')) ?></div>
      <?php if (trim((string)($bn['texto'] ?? '')) !== ''): ?><div class="promo-banner-text"><?= htmlspecialchars((string)$bn['texto']) ?></div><?php endif; ?>
      <?php if ($bnHref !== ''): ?><div><a class="promo-banner-btn" href="<?= htmlspecialchars($bnHref) ?>"><?= $bnMarca !== '' ? 'Ver ' . htmlspecialchars($bnMarca) : 'Ver más' ?></a></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<script>
function promoScroll(dir) {
  const t = document.getElementById('promoTrack');
  if (t) t.scrollBy({left: dir * 220, behavior: 'smooth'});
}
(function(){
  const t = document.getElementById('promoTrack');
  if (!t) return;
  const timer = setInterval(function() {
    if (document.hidden) return;
    const max = t.scrollWidth - t.clientWidth - 10;
    if (t.scrollLeft >= max) t.scrollTo({left: 0, behavior: 'smooth'});
    else t.scrollBy({left: 220, behavior: 'smooth'});
  }, 6000);
  ['mouseenter', 'touchstart'].forEach(function(ev) { t.addEventListener(ev, function() { clearInterval(timer); }, {once: true}); });
})();
</script>
<?php endif; ?>

<form class="filters" method="get" action="/buscar" id="formBuscarPortada">
  <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar productos, marcas, rutinas, necesidades, contenido..." style="flex:1" />
  <select name="codsub" id="selMarca">
    <option value="0">Todas las marcas</option>
    <?php foreach (($marcas ?? []) as $m): ?>
    <option value="<?= (int)($m['codsub'] ?? 0) ?>" <?= $codsub === (int)($m['codsub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars(trim((string)($m['nomsub'] ?? ''))) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="codrub" id="selRubro">
    <option value="0">Todas las categorías</option>
    <?php foreach (($rubros ?? []) as $r): ?>
    <option value="<?= (int)($r['codrub'] ?? 0) ?>" <?= $codrub === (int)($r['codrub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($r['nomrub'] ?? '')) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Buscar</button>
</form>
<?php if ($codrub > 0 || $codsub > 0): ?>
<div style="margin-top:8px"><a href="/" style="font-size:12px;color:rgba(246,244,239,0.75)">✕ Limpiar filtros y ver todo</a></div>
<?php endif; ?>
<script>
document.getElementById('formBuscarPortada').addEventListener('submit', function(e) {
    const q = this.querySelector('input[name="q"]').value.trim();
    if (q !== '') return;
    e.preventDefault();
    const params = new URLSearchParams();
    const codsub = document.getElementById('selMarca').value;
    const codrub = document.getElementById('selRubro').value;
    if (codsub !== '0') params.set('codsub', codsub);
    if (codrub !== '0') params.set('codrub', codrub);
    window.location = '/' + (params.toString() ? '?' + params.toString() : '');
});
</script>

<?php if (!$products): ?>
  <div class="page">
    No hay productos para mostrar.
  </div>
<?php else: ?>
  <div class="grid">
    <?php foreach ($products as $p): ?>
      <?php
        $tiva = (float)($p['tiva'] ?? 0);
        $base = (float)($isWholesale ? $p['precio1'] : $p['precio']);
        $withIva = $base * (1 + $tiva/100);
      ?>
      <a class="card" href="/p/<?= (int)$p['idprodu'] ?>">
        <div class="thumb">
          <?php if (!empty($p['imagen'])): ?>
            <img src="<?= htmlspecialchars(Format::uploadUrl((string)$p['imagen'])) ?>" alt="<?= htmlspecialchars((string)$p['produ']) ?>" loading="lazy" />
          <?php else: ?>
            <div style="color:rgba(246,244,239,0.55);font-weight:800">&nbsp;</div>
          <?php endif; ?>
        </div>
        <div class="card-body">
          <div class="card-title"><?= htmlspecialchars((string)$p['produ']) ?></div>
          <div style="margin-top:6px;color:rgba(246,244,239,0.55);font-size:12px">
            <?= htmlspecialchars((string)($p['nomrub'] ?? '')) ?>
            <?php if (!empty($p['nomsub'])): ?>
              &middot; <?= htmlspecialchars(trim((string)$p['nomsub'])) ?>
            <?php endif; ?>
          </div>
          <div class="price">
            <?php $gPromo = $promosByProduct[(int)$p['idprodu']] ?? null; ?>
            <?php if ($gPromo): ?>
              <div style="font-size:12px;color:rgba(246,244,239,0.55)"><s>$<?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?></s> <span class="promo-off" style="font-size:11px">-<?= $gPromo['off'] ?>%</span></div>
              <strong style="color:#7CFC9A">$<?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($gPromo['promo']*100))) ?></strong>
            <?php else: ?>
              <strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?></strong>
            <?php endif; ?>
            <small>IVA inc.</small>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<?php if (!empty($trustBlock ?? null)): ?>
  <?php
  $block = $trustBlock;
  $blockFile = __DIR__ . '/home/blocks/trust_badges.php';
  if (is_file($blockFile)) {
      include $blockFile;
  }
  ?>
<?php endif; ?>

<div class="page" style="margin-top:26px;padding-top:18px;border-top:1px solid rgba(216,178,90,0.25)">
  <h3 style="margin:0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Contacto y horarios</h3>
  <div style="margin-top:10px;color:rgba(246,244,239,0.78);line-height:1.7">
    <div>WhatsApp: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a></div>
    <div>Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a></div>
    <div>Direcciones: <?= $empresaDir ?></div>
    <div>Horarios: <?= htmlspecialchars($empresaHorarios) ?></div>
  </div>
</div>
