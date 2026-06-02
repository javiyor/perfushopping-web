<?php
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Service\AuthService;

$q = (string)($_GET['q'] ?? '');
$codrub = (int)($_GET['codrub'] ?? 0);
$codsub = (int)($_GET['codsub'] ?? 0);
$isNovedades = ($q === '' && $codrub === 0 && $codsub === 0);
?>

<div class="hero">
  <div style="display:flex;justify-content:center;">
    <img src="/assets/brand/logo-banner.jpg" alt="Perfushopping" loading="eager" decoding="async" style="max-width:80%;height:auto;border-radius:22px;border:1px solid rgba(216,178,90,0.18);box-shadow:0 22px 70px rgba(0,0,0,0.55);" />
  </div>
  <h1>Perfushopping</h1>
  <p>Catalogo. Precios con IVA / sin IVA, paga facil y seguro en cuotas con Mercado Pago.</p>
</div>

<div class="page" style="margin-top:16px">
  <?php if ($isNovedades): ?>
    <div style="margin-top:10px;color:rgba(246,244,239,0.72);line-height:1.55">
      Tel: <strong>3482 765798</strong> &middot; Mail: <a href="mailto:clientes@perfushopping.com.ar" style="text-decoration:underline">clientes@perfushopping.com.ar</a><br />
      Direcciones: 9 de julio 1610 - Hipolito Irigoyen 465 - Reconquista, Santa Fe - Argentina<br />
      Instagram: <a href="https://www.instagram.com/perfushopping" target="_blank" rel="noopener" style="text-decoration:underline">@perfushopping</a>
    </div>
    <h3 style="margin:10px 0 0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Novedades</h3>
  <?php else: ?>
    <h3 style="margin:0;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Catalogo</h3>
    <div style="margin-top:10px;color:rgba(246,244,239,0.72);line-height:1.55">
      Tel: <strong>3482 765798</strong> &middot; Mail: <a href="mailto:clientes@perfushopping.com.ar" style="text-decoration:underline">clientes@perfushopping.com.ar</a><br />
      Direcciones: 9 de julio 1610 - Hipolito Irigoyen 465 - Reconquista, Santa Fe - Argentina<br />
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
            <strong><?= htmlspecialchars(Format::moneyFromCents((int)round($withIva*100))) ?></strong>
            <small>IVA inc.</small>
          </div>
        </div>
      </a>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
