<?php
use Perfushopping\Web\Support\Format;

$routine = $routine ?? [];
$items = $items ?? [];
$needs = $needs ?? [];
$topics = $topics ?? [];
$videos = $videos ?? [];
$articles = $articles ?? [];
$seo = $seo ?? [];
?>
<div class="page">
  <nav aria-label="breadcrumb" class="mb-2">
    <a href="/rutinas" style="color:rgba(246,244,239,0.7)">Rutinas</a>
    <span style="color:rgba(246,244,239,0.5);margin:0 8px">/</span>
    <span><?= htmlspecialchars((string)$routine['name']) ?></span>
  </nav>

  <?php if ($routine['image']): ?>
  <div style="margin-bottom:16px"><img src="<?= htmlspecialchars((string)$routine['image']) ?>" alt="" style="width:100%;max-height:320px;object-fit:cover;border-radius:12px"></div>
  <?php endif; ?>

  <h2 style="margin:0 0 10px"><?= htmlspecialchars((string)$routine['name']) ?></h2>
  <?php if ($routine['description']): ?><div class="notice"><?= nl2br(htmlspecialchars((string)$routine['description'])) ?></div><?php endif; ?>
  <?php if ($routine['problem']): ?><p><strong>Resuelve:</strong> <?= htmlspecialchars((string)$routine['problem']) ?></p><?php endif; ?>
  <?php if ($routine['expected_result']): ?><p><strong>Resultado:</strong> <?= htmlspecialchars((string)$routine['expected_result']) ?></p><?php endif; ?>

  <?php if ($items): ?>
  <h3 style="margin:24px 0 10px">Tu rutina paso a paso</h3>
  <form method="post" action="/cart/add-routine">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Perfushopping\Web\Support\Csrf::token()) ?>" />
    <input type="hidden" name="routine_id" value="<?= (int)$routine['id'] ?>" />
    <div class="grid" style="grid-template-columns:1fr;gap:12px">
      <?php foreach ($items as $it):
        $tiva = (float)($it['tiva'] ?? 0);
        $base = (float)($isWholesale ? $it['precio1'] : $it['precio']);
        $withIva = $base * (1 + $tiva/100);
      ?>
      <div class="card" style="display:flex;gap:14px;align-items:center;padding:14px">
        <div style="font-size:24px;font-weight:700;color:var(--gold)"><?= (int)$it['step_order'] ?></div>
        <?php if ($it['imagen']): ?><img src="/imagenes/<?= htmlspecialchars((string)$it['imagen']) ?>" alt="" style="width:80px;height:80px;object-fit:contain"><?php endif; ?>
        <div style="flex:1">
          <strong><?= htmlspecialchars((string)$it['produ']) ?></strong>
          <div class="small text-muted"><?= htmlspecialchars((string)($it['instructions'] ?? '')) ?></div>
          <div class="small"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?> IVA inc.</div>
        </div>
        <?php if (!(int)$it['optional']): ?>
        <input type="hidden" name="items[]" value="<?= (int)$it['product_id'] ?>" />
        <?php else: ?>
        <label class="form-check-label small"><input type="checkbox" name="items[]" value="<?= (int)$it['product_id'] ?>" checked /> Incluir</label>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-3">
      <button class="btn" type="submit"><i class="bi bi-cart-plus"></i> Agregar rutina al carrito</button>
    </div>
  </form>
  <?php endif; ?>

  <?php if ($videos): ?>
  <h3 style="margin:24px 0 10px">Videos</h3>
  <ul>
    <?php foreach ($videos as $v): ?>
    <li><a href="/aprende/videos/<?= htmlspecialchars((string)$v['slug']) ?>"><?= htmlspecialchars((string)$v['title']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <?php if ($articles): ?>
  <h3 style="margin:24px 0 10px">Artículos</h3>
  <ul>
    <?php foreach ($articles as $a): ?>
    <li><a href="/articulos/<?= htmlspecialchars((string)$a['slug']) ?>"><?= htmlspecialchars((string)$a['title']) ?></a></li>
    <?php endforeach; ?>
  </ul>
  <?php endif; ?>

  <div class="mt-3">
    <a class="btn" href="https://wa.me/?text=<?= urlencode('Hola, estoy viendo la rutina "' . ($routine['name'] ?? '') . '" en Perfushopping y quería asesoramiento.') ?>" target="_blank" rel="noopener">Consultar por WhatsApp</a>
  </div>
</div>
