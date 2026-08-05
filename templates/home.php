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
?>

<div class="hero">
  <div style="display:flex;justify-content:center;">
    <img src="<?= htmlspecialchars($empresaBanner) ?>" alt="<?= $empresaNombre ?>" loading="eager" decoding="async" style="width:26%;max-width:300px;height:auto;border-radius:22px;border:1px solid rgba(216,178,90,0.18);box-shadow:0 22px 70px rgba(0,0,0,0.55);" />
  </div>
  <h1><?= $empresaNombre ?></h1>
  <p>Carrito de compras. Compra, paga facil y seguro en cuotas con Mercado Pago.</p>
  <div style="display:flex;gap:12px;justify-content:center;flex-wrap:wrap;margin-top:14px">
    <span class="pill">Envios a todo el pais</span>
    <span class="pill secondary">3 y 6 cuotas con Mercado Pago</span>
    <span class="pill secondary">Retire en el local</span>
  </div>
</div>

<div class="page" style="margin-top:16px">
  <?php if ($isNovedades): ?>
    <div style="margin-top:10px;color:rgba(246,244,239,0.72);line-height:1.55">
      Tel: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a> &middot; Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a><br />
      Direcciones: <?= $empresaDir ?><br />
      Instagram: <a href="https://www.instagram.com/perfushopping" target="_blank" rel="noopener" style="text-decoration:underline">@perfushopping</a>
    </div>
    <h3 style="margin:10px 0 0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Novedades</h3>
  <?php else: ?>
    <h3 style="margin:0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Catalogo</h3>
    <div style="margin-top:10px;color:rgba(246,244,239,0.72);line-height:1.55">
      Tel: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a> &middot; Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a><br />
      Direcciones: <?= $empresaDir ?><br />
      Instagram: <a href="https://www.instagram.com/perfushopping" target="_blank" rel="noopener" style="text-decoration:underline">@perfushopping</a>
    </div>
  <?php endif; ?>
</div>

<form class="filters" method="get" action="/">
  <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar (producto, variedad, codigo)" />
  <select name="codrub">
    <option value="0">Todas las categorias</option>
    <?php foreach ($rubros as $r): ?>
      <option value="<?= (int)$r['codrub'] ?>" <?= ((int)$r['codrub'] === $codrub) ? 'selected' : '' ?>><?= htmlspecialchars((string)$r['nomrub']) ?></option>
    <?php endforeach; ?>
  </select>
  <select name="codsub">
    <option value="0">Todas las marcas</option>
    <?php foreach ($marcas as $m): ?>
      <option value="<?= (int)$m['codsub'] ?>" <?= ((int)$m['codsub'] === $codsub) ? 'selected' : '' ?>><?= htmlspecialchars(trim((string)$m['nomsub'])) ?></option>
    <?php endforeach; ?>
  </select>
  <button class="btn" type="submit">Buscar</button>
</form>

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

<div class="page" style="margin-top:26px;padding-top:18px;border-top:1px solid rgba(216,178,90,0.25)">
  <h3 style="margin:0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Contacto y horarios</h3>
  <div style="margin-top:10px;color:rgba(246,244,239,0.78);line-height:1.7">
    <div>WhatsApp: <a href="https://wa.me/<?= $empresaWhatsapp ?>?text=Hola%20<?= urlencode($empresaNombre) ?>" target="_blank" rel="noopener" style="text-decoration:underline"><strong><?= $empresaTel ?></strong></a></div>
    <div>Mail: <a href="mailto:<?= $empresaMail ?>" style="text-decoration:underline"><?= $empresaMail ?></a></div>
    <div>Direcciones: <?= $empresaDir ?></div>
    <div>Horarios: <?= htmlspecialchars($empresaHorarios) ?></div>
  </div>
</div>
