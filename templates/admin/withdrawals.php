<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
?>

<div class="page">
  <h2 style="margin:0 0 10px">Retiros (solicitados/aprobados)</h2>
  <?php if (!$list): ?>
    <div class="notice">No hay retiros pendientes.</div>
  <?php else: ?>
    <?php foreach ($list as $w): ?>
      <div class="cartline" style="margin-bottom:12px">
        <div>
          <h4>Retiro #<?= (int)$w['id'] ?> &middot; <?= htmlspecialchars((string)$w['status']) ?></h4>
          <div class="meta">Usuario: <?= htmlspecialchars((string)$w['name']) ?> (<?= htmlspecialchars((string)$w['email']) ?>)</div>
          <div class="meta">Credito: <?= htmlspecialchars(Format::moneyFromCents((int)$w['credit_amount_cents'])) ?> | Pagar: <?= htmlspecialchars(Format::moneyFromCents((int)$w['payout_amount_cents'])) ?></div>
          <div class="meta">Destino: <?= htmlspecialchars((string)$w['destination']) ?></div>
          <div class="meta">Fecha: <?= htmlspecialchars((string)$w['created_at']) ?></div>
        </div>
        <div class="right">
          <form method="post" action="/admin/withdrawals/approve" style="margin-bottom:8px">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="id" value="<?= (int)$w['id'] ?>" />
            <button class="btn" type="submit">Aprobar</button>
          </form>
          <form method="post" action="/admin/withdrawals/paid" style="margin-bottom:8px">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="id" value="<?= (int)$w['id'] ?>" />
            <button class="btn secondary" type="submit">Marcar pagado</button>
          </form>
          <form method="post" action="/admin/withdrawals/reject">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="id" value="<?= (int)$w['id'] ?>" />
            <input name="reason" placeholder="Motivo (opcional)" style="margin-bottom:8px" />
            <button class="btn danger" type="submit">Rechazar y reintegrar</button>
          </form>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
