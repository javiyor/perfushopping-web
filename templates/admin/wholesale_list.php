<?php
use Perfushopping\Web\Support\Csrf;
?>

<div class="page">
  <h2 style="margin:0 0 10px">Solicitudes mayoristas</h2>
  <?php if (!$list): ?>
    <div class="notice">No hay solicitudes pendientes.</div>
  <?php else: ?>
    <?php foreach ($list as $r): ?>
      <div class="cartline" style="margin-bottom:12px">
        <div>
          <h4><?= htmlspecialchars((string)$r['razon_social']) ?></h4>
          <div class="meta">Email: <?= htmlspecialchars((string)$r['email']) ?> | Tel: <?= htmlspecialchars((string)$r['phone']) ?></div>
          <div class="meta">CUIT: <?= htmlspecialchars((string)$r['cuit']) ?> | Categoria: <?= htmlspecialchars((string)(($customerCategories[($r['customer_category'] ?? $r['user_customer_category'] ?? 'none')] ?? ($r['customer_category'] ?? $r['user_customer_category'] ?? 'Sin categoria')))) ?></div>
          <div class="meta">Dir: <?= htmlspecialchars((string)$r['address']) ?>, <?= htmlspecialchars((string)$r['city']) ?> (CP <?= htmlspecialchars((string)$r['postal_code']) ?>)</div>
        </div>
        <div class="right">
          <form method="post" action="/admin/wholesale/approve" style="margin-bottom:8px">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
            <button class="btn" type="submit">Aprobar</button>
          </form>
          <form method="post" action="/admin/wholesale/reject">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
            <button class="btn danger" type="submit">Rechazar</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
