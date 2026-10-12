<?php
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
      Tel: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a> &middot; Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a><br />
      Direcciones: <?= $empresaDir ?><br />
      Instagram: <a href="https://www.instagram.com/perfushopping" target="_blank" rel="noopener" style="text-decoration:underline">@perfushopping</a>
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
      Tel: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a> &middot; Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a><br />
      Direcciones: <?= $empresaDir ?><br />
      Instagram: <a href="https://www.instagram.com/perfushopping" target="_blank" rel="noopener" style="text-decoration:underline">@perfushopping</a>
    </div>
  <?php endif; ?>
</div>

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
            <strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?></strong>
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
