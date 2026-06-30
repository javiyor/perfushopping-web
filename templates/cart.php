<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Service\InstallmentsService;

$weekday = (int)date('w') + 1; // 1=domingo
$inst = null;
if (!$isWholesale) {
  $inst = (new InstallmentsService())->computeAllCardsPromo((int)$total, $weekday);
}

?>

<div class="page two">
  <div>
    <h2 style="margin:0 0 12px">Carrito</h2>
    <?php if (!$items): ?>
      <div class="notice">Tu carrito esta vacio.</div>
    <?php else: ?>
      <?php foreach ($items as $it): ?>
        <?php $v = $it['variant']; ?>
        <div class="cartline" style="margin-bottom:12px">
          <div>
            <h4><?= htmlspecialchars((string)$v['produ']) ?></h4>
            <div class="meta">Variedad: <?= htmlspecialchars(trim((string)$v['nomgusto'])) ?></div>
            <div class="meta">Unitario (sin IVA): <?= htmlspecialchars(Format::moneyRoundedFromCents((int)$it['unit_net_cents'])) ?></div>
          </div>
          <div class="right">
            <div style="font-weight:800"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)$it['line_total_cents'])) ?></div>
            <form method="post" action="/cart/update" style="margin-top:10px">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
              <input type="hidden" name="idcodgusto" value="<?= (int)$it['idcodgusto'] ?>" />
              <div class="row" style="justify-content:flex-end">
                <input style="width:96px" type="number" name="qty" value="<?= (int)$it['qty'] ?>" min="0" max="999" />
                <button class="btn secondary" type="submit">Actualizar</button>
              </div>
            </form>
            <form method="post" action="/cart/remove" style="margin-top:8px">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
              <input type="hidden" name="idcodgusto" value="<?= (int)$it['idcodgusto'] ?>" />
              <button class="btn danger" type="submit">Quitar</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <div>
    <h2 style="margin:0 0 12px">Totales</h2>
    <div class="notice">
      <div class="row"><span class="grow">Subtotal (sin IVA)</span><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)$subtotalNet)) ?></strong></div>
      <div class="row" style="margin-top:6px"><span class="grow">IVA</span><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)$iva)) ?></strong></div>
      <div class="row" style="margin-top:10px"><span class="grow" style="font-weight:800">Total</span><strong style="font-size:18px"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)$total)) ?></strong></div>
    </div>

    <div class="notice" style="margin-top:12px">
      Minimo: <strong>$30.000</strong> (con descuento aplicado).
      Descuentos en envio: <strong>50%</strong> desde $100.000, <strong>75%</strong> desde $180.000, <strong>gratis</strong> desde $250.000.
    </div>
    <div class="notice" style="margin-top:12px">
      <strong>Reconquista y Avellaneda</strong> (Santa Fe): envio gratis en horarios de reparto (12:00 y 19:30 hs).
      Fuera de horario disponible con costo adicional.
    </div>

    <?php if ($items && !$isWholesale && $inst): ?>
      <div class="notice" style="margin-top:12px">
        <strong><?= htmlspecialchars((string)($inst['promo']['descrip'] ?? 'Cuotas')) ?>:</strong>
        <?= (int)$inst['cuotas'] ?>x <strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)$inst['cuota_cents'])) ?></strong>
        <?php if ((float)$inst['recargo_percent'] > 0): ?>
          <span style="color:rgba(246,244,239,0.65)">(+<?= htmlspecialchars(number_format((float)$inst['recargo_percent'], 2, ',', '.')) ?>%)</span>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php if ($items): ?>
      <a class="btn" href="/checkout" style="width:100%; margin-top:12px">Finalizar compra</a>
      <a class="btn secondary" href="/pay/mp/start" style="width:100%; margin-top:10px">Pagar con Mercado Pago</a>
      <?php if ($isWholesale): ?>
        <div class="notice">Modo mayorista: el pago es solo por transferencia. Completa el checkout.</div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>
