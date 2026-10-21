<?php
$branches = $homeBranches ?? [];
$s = $block['settings'] ?? [];
$intro = trim((string)($s['intro'] ?? 'Visitá nuestros locales. Encontrá la sucursal más cercana y cómo llegar.'));
$branches = array_values(array_filter($branches, static fn(array $branch): bool => trim((string)($branch['direccion'] ?? '')) !== ''));
?>
<?php if ($branches): ?>
<section class="page" style="margin-top:24px">
  <div style="max-width:1200px;margin:0 auto">
    <h2 style="margin:0 0 8px;color:var(--gold)"><?= htmlspecialchars((string)($block['title'] ?: 'Visitá nuestras sucursales')) ?></h2>
    <?php if ($intro !== ''): ?><p style="margin:0 0 20px;color:var(--text);opacity:.8"><?= htmlspecialchars($intro) ?></p><?php endif; ?>
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,320px),1fr));gap:18px">
      <?php foreach ($branches as $branch):
          $name = trim((string)($branch['nomsuc'] ?? 'Sucursal'));
          $address = trim((string)$branch['direccion']);
          $query = $name . ', ' . $address;
          $encodedQuery = rawurlencode($query);
      ?>
      <article style="overflow:hidden;border:1px solid var(--line);border-radius:14px;background:var(--card)">
        <div style="padding:16px 18px">
          <h3 style="margin:0 0 8px;font-size:1.1rem;color:var(--text)"><?= htmlspecialchars($name) ?></h3>
          <p style="margin:0;color:var(--text);opacity:.82"><i class="bi bi-geo-alt-fill" aria-hidden="true"></i> <?= htmlspecialchars($address) ?></p>
          <?php if (trim((string)($branch['telefono'] ?? '')) !== ''): ?><p style="margin:6px 0 0"><a href="tel:<?= htmlspecialchars(preg_replace('/[^0-9+]/', '', (string)$branch['telefono'])) ?>" style="color:var(--text)"><i class="bi bi-telephone-fill" aria-hidden="true"></i> <?= htmlspecialchars((string)$branch['telefono']) ?></a></p><?php endif; ?>
        </div>
        <iframe title="Mapa de <?= htmlspecialchars($name) ?>" src="https://www.google.com/maps?q=<?= $encodedQuery ?>&output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" style="display:block;width:100%;height:250px;border:0" allowfullscreen></iframe>
        <div style="padding:12px 18px"><a href="https://www.google.com/maps/search/?api=1&query=<?= $encodedQuery ?>" target="_blank" rel="noopener noreferrer" style="color:var(--gold)"><i class="bi bi-sign-turn-right-fill" aria-hidden="true"></i> Cómo llegar</a></div>
      </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
