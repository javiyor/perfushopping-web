<?php
use Perfushopping\Web\Support\Format;

$q = $q ?? '';
$results = $results ?? [];
?>
<div class="page">
  <h2 style="margin:0 0 12px"><?= $q !== '' ? 'Resultados para "' . htmlspecialchars($q) . '"' : 'Buscá en Perfushopping' ?></h2>

  <form method="get" action="/buscar" class="filters" style="margin-bottom:20px">
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Productos, marcas, rutinas, necesidades, contenido..." />
    <button class="btn" type="submit">Buscar</button>
  </form>

  <?php if ($q !== ''): ?>

  <?php if (!empty($results['products'])): ?>
  <h3 style="color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.7px">Productos</h3>
  <div class="grid" style="margin-bottom:24px">
    <?php foreach ($results['products'] as $p):
      $tiva = (float)($p['tiva'] ?? 0);
      $base = (float)($isWholesale ? $p['precio1'] : $p['precio']);
      $withIva = $base * (1 + $tiva/100);
    ?>
    <a class="card" href="/p/<?= (int)$p['idprodu'] ?>">
      <div class="thumb"><?php if (!empty($p['imagen'])): ?><img src="<?= htmlspecialchars(Format::uploadUrl((string)$p['imagen'])) ?>" alt="" loading="lazy" /><?php endif; ?></div>
      <div class="card-body">
        <div class="card-title" style="font-size:14px"><?= htmlspecialchars((string)$p['produ']) ?></div>
        <div class="price"><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($withIva*100))) ?></strong><small>IVA inc.</small></div>
      </div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:14px;margin-bottom:24px">
    <?php if (!empty($results['needs'])): ?>
    <div class="card" style="padding:16px">
      <h5 style="margin:0 0 10px">Necesidades</h5>
      <?php foreach ($results['needs'] as $n): ?>
      <a href="/soluciones/<?= htmlspecialchars((string)$n['slug']) ?>" style="display:block;padding:4px 0;text-decoration:underline"><?= htmlspecialchars((string)$n['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['routines'])): ?>
    <div class="card" style="padding:16px">
      <h5 style="margin:0 0 10px">Rutinas</h5>
      <?php foreach ($results['routines'] as $r): ?>
      <a href="/rutinas/<?= htmlspecialchars((string)$r['slug']) ?>" style="display:block;padding:4px 0;text-decoration:underline"><?= htmlspecialchars((string)$r['name']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['brands'])): ?>
    <div class="card" style="padding:16px">
      <h5 style="margin:0 0 10px">Marcas</h5>
      <?php foreach ($results['brands'] as $b): ?>
      <a href="/?codsub=<?= (int)$b['codsub'] ?>" style="display:block;padding:4px 0;text-decoration:underline"><?= htmlspecialchars((string)$b['nomsub']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($results['categories'])): ?>
    <div class="card" style="padding:16px">
      <h5 style="margin:0 0 10px">Categorías</h5>
      <?php foreach ($results['categories'] as $c): ?>
      <a href="/?codrub=<?= (int)$c['codrub'] ?>" style="display:block;padding:4px 0;text-decoration:underline"><?= htmlspecialchars((string)$c['nomrub']) ?></a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($results['articles']) || !empty($results['videos'])): ?>
  <div class="grid" style="grid-template-columns:repeat(auto-fill,minmax(260px,1fr));gap:14px;margin-bottom:24px">
    <?php foreach ($results['articles'] as $a): ?>
    <a href="/articulos/<?= htmlspecialchars((string)$a['slug']) ?>" class="card" style="text-decoration:none;color:inherit;padding:16px">
      <strong><?= htmlspecialchars((string)$a['title']) ?></strong>
      <div class="small text-muted">Artículo</div>
    </a>
    <?php endforeach; ?>
    <?php foreach ($results['videos'] as $v): ?>
    <a href="/aprende/videos/<?= htmlspecialchars((string)$v['slug']) ?>" class="card" style="text-decoration:none;color:inherit;padding:16px">
      <strong><?= htmlspecialchars((string)$v['title']) ?></strong>
      <div class="small text-muted">Video</div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <?php
  $allEmpty = empty($results['products']) && empty($results['brands']) && empty($results['categories']) && empty($results['needs']) && empty($results['routines']) && empty($results['articles']) && empty($results['videos']);
  ?>
  <?php if ($allEmpty): ?>
  <div class="notice">No encontramos resultados para "<?= htmlspecialchars($q) ?>". Probá con otra palabra o consultanos por WhatsApp.</div>
  <?php endif; ?>

  <?php endif; ?>
</div>
