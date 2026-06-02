<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;
use Perfushopping\Web\Service\ShippingService;

$form = $form ?? [];
$city = (string)($form['city'] ?? '');
$prov = (int)($form['province_codprov'] ?? 0);
$localityKey = Format::slugKey($city);
$shippingSvc = new ShippingService();
$localOpts = $shippingSvc->deliveryLocalOptions($localityKey, $prov);
$inst = $inst ?? null;
$creditBalance = (int)($creditBalance ?? 0);
?>

<div class="page">
  <h2 style="margin:0 0 12px">Checkout</h2>
  <p style="color:rgba(246,244,239,0.7);margin-top:0">Completa los datos de envio antes de pagar. Si sos mayorista aprobado, el pago es solo transferencia.</p>

  <form method="post" action="/checkout">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
    <div class="filters" style="grid-template-columns:1fr 1fr">
      <input name="name" placeholder="Nombre y apellido" value="<?= htmlspecialchars((string)($form['name'] ?? ($user['name'] ?? ''))) ?>" />
      <input name="email" placeholder="Email" value="<?= htmlspecialchars((string)($form['email'] ?? ($user['email'] ?? ''))) ?>" />
      <input name="phone" placeholder="Telefono" value="<?= htmlspecialchars((string)($form['phone'] ?? ($user['phone'] ?? ''))) ?>" />
      <input name="address" placeholder="Direccion" value="<?= htmlspecialchars((string)($form['address'] ?? '')) ?>" />
      <input name="city" placeholder="Localidad" value="<?= htmlspecialchars((string)($form['city'] ?? '')) ?>" />
      <input name="postal_code" placeholder="Codigo postal" value="<?= htmlspecialchars((string)($form['postal_code'] ?? '')) ?>" />
      <select name="province_codprov">
        <option value="0">Provincia</option>
        <?php foreach ($provincias as $p): ?>
          <option value="<?= (int)$p['codprov'] ?>" <?= ((int)$p['codprov'] === $prov) ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['provinci']) ?></option>
        <?php endforeach; ?>
      </select>
      <?php if (!$isWholesale): ?>
        <select name="tarjeta_id">
          <option value="0">Tarjeta (para promos)</option>
          <?php foreach ($tarjetas as $t): ?>
            <option value="<?= (int)$t['idtarje'] ?>" <?= ((int)$t['idtarje'] === (int)($form['tarjeta_id'] ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['nomtar']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="cuotas">
          <option value="3" <?= ((int)($form['cuotas'] ?? 3) === 3) ? 'selected' : '' ?>>3 cuotas sin interes</option>
          <?php if ($inst): ?>
            <option value="6" <?= ((int)($form['cuotas'] ?? 3) === 6) ? 'selected' : '' ?>><?= htmlspecialchars((string)($inst['promo']['descrip'] ?? 'Cuotas')) ?>: <?= (int)$inst['cuotas'] ?>x <?= htmlspecialchars(\Perfushopping\Web\Support\Format::moneyFromCents((int)$inst['cuota_cents'])) ?></option>
          <?php else: ?>
            <option value="6" <?= ((int)($form['cuotas'] ?? 3) === 6) ? 'selected' : '' ?>>Cuotas con Mercado Pago</option>
          <?php endif; ?>
        </select>
      <?php else: ?>
        <input value="Transferencia (mayorista)" disabled />
        <input value="Sin promos" disabled />
      <?php endif; ?>
    </div>

    <?php if (!$isWholesale && $user && $creditBalance > 0): ?>
      <div class="notice" style="margin-top:12px">
        Tenes credito disponible: <strong><?= htmlspecialchars(\Perfushopping\Web\Support\Format::moneyFromCents($creditBalance)) ?></strong>
        <div style="margin-top:8px;display:flex;gap:10px;flex-wrap:wrap;align-items:center">
          <input style="max-width:220px" type="number" name="credit_use" min="0" step="0.01" placeholder="Usar credito (ARS)" value="<?= htmlspecialchars((string)($form['credit_use'] ?? '')) ?>" />
          <span style="color:rgba(246,244,239,0.65);font-size:12px">Solo productos. Max 50%.</span>
        </div>
      </div>
    <?php endif; ?>

    <h3 style="margin:18px 0 10px">Metodo de envio</h3>
    <div class="variants" style="grid-template-columns:1fr">
      <?php if ($localOpts): ?>
        <?php foreach ($localOpts as $o): ?>
          <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
            <span>
              <strong><?= htmlspecialchars((string)$o['label']) ?></strong>
              <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Pago en mano / coordinado</span>
            </span>
            <span style="display:flex;align-items:center;gap:12px">
              <span class="chip gold"><?= htmlspecialchars(Format::moneyFromCents((int)$o['price_cents'])) ?></span>
              <input type="radio" name="shipping_choice" value="<?= htmlspecialchars((string)$o['id']) ?>" <?= ((string)($form['shipping_choice'] ?? '') === (string)$o['id']) ? 'checked' : '' ?> />
            </span>
          </label>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="notice">Delivery local disponible solo para localidades cercanas a Reconquista (Santa Fe).</div>
      <?php endif; ?>

      <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
        <span>
          <strong>Correo Argentino</strong>
          <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Se calcula por zona (cuando este cargado)</span>
        </span>
        <span style="display:flex;align-items:center;gap:12px">
          <input type="radio" name="shipping_choice" value="correo" <?= ((string)($form['shipping_choice'] ?? '') === 'correo') ? 'checked' : '' ?> />
        </span>
      </label>
    </div>

    <button class="btn" type="submit" style="margin-top:14px; width:100%">Confirmar datos y generar pedido</button>
  </form>

  <div class="notice" style="margin-top:14px">
    <strong>Mayoristas:</strong> una vez generado el pedido, vas a ver los datos para transferencia.
  </div>
</div>
