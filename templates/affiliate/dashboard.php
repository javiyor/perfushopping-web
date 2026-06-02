<?php
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Support\Csrf;

$balance = (int)($balance ?? 0);
$refCode = (string)($refCode ?? '');
$withdrawals = $withdrawals ?? [];
$movs = $movs ?? [];
$appUrl = rtrim((string)($_ENV['APP_URL'] ?? getenv('APP_URL') ?: 'https://perfushopping.ar'), '/');
$refLink = $refCode !== '' ? ($appUrl . '/?ref=' . urlencode($refCode)) : '';
?>

<div class="page" style="max-width:920px;margin:18px auto 0">
  <h2 style="margin:0 0 10px">Mi credito y referidos</h2>

  <div class="notice">
    Credito disponible: <strong><?= htmlspecialchars(Format::moneyFromCents($balance)) ?></strong>
    <div style="margin-top:6px;color:rgba(246,244,239,0.65)">Comision: 10% sobre productos (con IVA y descuentos), libera a los 7 dias del pago aprobado.</div>
  </div>

  <div class="page" style="margin-top:12px">
    <h3 style="margin:0 0 10px;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.6px">Tu link de referido</h3>
    <?php if ($refLink === ''): ?>
      <div class="notice danger">No se pudo generar tu codigo. Intenta mas tarde.</div>
    <?php else: ?>
      <input value="<?= htmlspecialchars($refLink) ?>" readonly onclick="this.select()" />
      <div style="margin-top:8px;color:rgba(246,244,239,0.65);font-size:12px">Compartilo por Instagram/WhatsApp. El cliente queda asignado al primer referidor.</div>
      <div style="margin-top:8px"><a class="btn secondary" href="/terms/affiliate" target="_blank" rel="noopener">Ver terminos del programa</a></div>
    <?php endif; ?>
  </div>

  <div class="page" style="margin-top:12px">
    <h3 style="margin:0 0 10px;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.6px">Movimientos</h3>
    <?php if (empty($movs)): ?>
      <div class="notice">Aun no hay movimientos.</div>
    <?php else: ?>
      <?php foreach ($movs as $m): ?>
        <?php
          $amt = (int)$m['amount_cents'];
          $kind = (string)$m['type'];
          $status = (string)($m['status'] ?? '');
          $note = (string)($m['note'] ?? '');
          $badge = $amt >= 0 ? 'ok' : 'danger';
          $statusTxt = $status === 'pending' ? 'pendiente' : 'disponible';
          $avail = (string)($m['available_at'] ?? '');
        ?>
        <div class="cartline" style="margin-bottom:10px">
          <div>
            <h4 style="margin:0 0 6px"><?= htmlspecialchars($kind) ?> &middot; <?= htmlspecialchars($statusTxt) ?></h4>
            <?php if ($note !== ''): ?><div class="meta"><?= htmlspecialchars($note) ?></div><?php endif; ?>
            <div class="meta">Fecha: <?= htmlspecialchars((string)$m['created_at']) ?><?php if ($status === 'pending' && $avail !== ''): ?> &middot; Libera: <?= htmlspecialchars($avail) ?><?php endif; ?></div>
          </div>
          <div class="right">
            <div class="notice <?= $badge ?>" style="margin:0;display:inline-block;padding:8px 10px">
              <strong><?= htmlspecialchars(Format::moneyFromCents($amt)) ?></strong>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <div class="page" style="margin-top:12px">
    <h3 style="margin:0 0 10px;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.6px">Retirar en pesos</h3>
    <div style="color:rgba(246,244,239,0.7);line-height:1.5">
      Minimo: <strong>$20.000</strong> de credito. Al retirar, se paga el <strong>50%</strong> en ARS (el 100% del credito solicitado se debita).
    </div>

    <form method="post" action="/affiliate/withdraw" style="margin-top:12px;display:grid;gap:10px">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
      <input name="amount" placeholder="Importe a retirar (Credito ARS)" />
      <input name="titular" placeholder="Titular" />
      <input name="cbu" placeholder="CBU (opcional si usas alias)" />
      <input name="alias" placeholder="Alias (opcional si usas CBU)" />
      <button class="btn" type="submit">Solicitar retiro</button>
    </form>
  </div>

  <div class="page" style="margin-top:12px">
    <h3 style="margin:0 0 10px;color:var(--gold);font-family:Georgia, 'Times New Roman', serif;letter-spacing:0.6px">Tus solicitudes</h3>
    <?php if (empty($withdrawals)): ?>
      <div class="notice">Aun no hay solicitudes.</div>
    <?php else: ?>
      <?php foreach ($withdrawals as $w): ?>
        <div class="cartline" style="margin-bottom:10px">
          <div>
            <h4>Retiro #<?= (int)$w['id'] ?> &middot; Estado: <?= htmlspecialchars((string)$w['status']) ?></h4>
            <div class="meta">Credito debitado: <?= htmlspecialchars(Format::moneyFromCents((int)$w['credit_amount_cents'])) ?> | Pago ARS: <?= htmlspecialchars(Format::moneyFromCents((int)$w['payout_amount_cents'])) ?></div>
            <div class="meta">Destino: <?= htmlspecialchars((string)$w['destination']) ?></div>
          </div>
          <div class="right">
            <div class="meta"><?= htmlspecialchars((string)$w['created_at']) ?></div>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
