<?php
use Perfushopping\Web\Support\Csrf;
use Perfushopping\Web\Support\Format;

$form = $form ?? [];
$prov = (int)($form['province_codprov'] ?? 0);
$lugares = $lugares ?? [];
$selectedLugarId = (int)($selectedLugarId ?? ($form['cod_lugar'] ?? 0));
$inst = $inst ?? null;
$creditBalance = (int)($creditBalance ?? 0);
$correoCost = $correoCost ?? null;
$cartTotalCents = (int)($cartTotalCents ?? 0);
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
      <select name="province_codprov">
        <option value="0">Provincia</option>
        <?php foreach ($provincias as $p): ?>
          <option value="<?= (int)$p['codprov'] ?>" <?= ((int)$p['codprov'] === $prov) ? 'selected' : '' ?>><?= htmlspecialchars((string)$p['provinci']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="cod_lugar" id="checkout-cod-lugar" data-selected="<?= $selectedLugarId ?>">
        <option value="0">Localidad</option>
        <?php foreach ($lugares as $lugar): ?>
          <option value="<?= (int)$lugar['cod_lugar'] ?>" data-prov="<?= (int)$lugar['codprov'] ?>" data-codpost="<?= htmlspecialchars((string)($lugar['codpost'] ?? '')) ?>" <?= $selectedLugarId === (int)$lugar['cod_lugar'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$lugar['lug_lugar']) ?></option>
        <?php endforeach; ?>
      </select>
      <input name="postal_code" id="checkout-postal-code" placeholder="Codigo postal" value="<?= htmlspecialchars((string)($form['postal_code'] ?? '')) ?>" />
      <input type="hidden" name="city" id="checkout-city" value="<?= htmlspecialchars((string)($form['city'] ?? '')) ?>" />
      <?php if (!$isWholesale): ?>
        <select name="tarjeta_id" id="checkout-tarjeta">
          <option value="0">Tarjeta (para promos)</option>
          <?php foreach ($tarjetas as $t): ?>
            <option value="<?= (int)$t['idtarje'] ?>" <?= ((int)$t['idtarje'] === (int)($form['tarjeta_id'] ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['nomtar']) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="cuotas" id="checkout-cuotas">
          <option value="3" <?= ((int)($form['cuotas'] ?? 3) === 3) ? 'selected' : '' ?>>3 cuotas sin interes</option>
          <?php if ($inst): ?>
              <option value="6" <?= ((int)($form['cuotas'] ?? 3) === 6) ? 'selected' : '' ?>><?= htmlspecialchars((string)($inst['promo']['descrip'] ?? 'Cuotas')) ?>: <?= (int)$inst['cuotas'] ?>x <?= htmlspecialchars(\Perfushopping\Web\Support\Format::moneyRoundedFromCents((int)$inst['cuota_cents'])) ?></option>
          <?php else: ?>
            <option value="6" <?= ((int)($form['cuotas'] ?? 3) === 6) ? 'selected' : '' ?>>Cuotas con Mercado Pago</option>
          <?php endif; ?>
        </select>
      <?php else: ?>
        <input value="Transferencia" disabled />
        <input value="Sin promos" disabled />
      <?php endif; ?>
    </div>

    <?php if (!$isWholesale): ?>
      <h3 style="margin:18px 0 10px">Metodo de pago</h3>
      <div class="variants" style="grid-template-columns:1fr 1fr">
        <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <span>
            <strong>Mercado Pago</strong>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Tarjeta de credito/debito</span>
          </span>
          <input type="radio" name="payment_method" value="mp" <?= ((string)($form['payment_method'] ?? 'mp') === 'mp') ? 'checked' : '' ?> />
        </label>
        <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <span>
            <strong>Transferencia bancaria</strong>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Alias MP: <strong>perfushopping.mp</strong></span>
          </span>
          <input type="radio" name="payment_method" value="transfer" <?= ((string)($form['payment_method'] ?? '') === 'transfer') ? 'checked' : '' ?> />
        </label>
      </div>
    <?php endif; ?>

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
      <div class="notice" id="local-delivery-note">Si la localidad es Reconquista o Avellaneda (Santa Fe), podes elegir delivery local. Para otras localidades, el envio es por Correo Argentino con costo segun provincia y descuentos por monto de compra.</div>

      <div id="local-options-reconquista" style="display:none">
        <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <span>
            <strong>Delivery gratis (Reconquista)</strong>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Horarios: 12:00 o 19:30</span>
          </span>
          <span style="display:flex;align-items:center;gap:12px">
            <span class="chip gold">GRATIS</span>
            <input type="radio" name="shipping_choice" value="local_reconquista_gratis" <?= ((string)($form['shipping_choice'] ?? '') === 'local_reconquista_gratis') ? 'checked' : '' ?> />
          </span>
        </label>
        <div class="meta" style="margin:4px 0 8px 0;padding-left:4px">
          Horario: <select name="shipping_time" id="shipping-time-reconquista-gratis">
            <option value="12:00" <?= ((string)($form['shipping_time'] ?? '12:00') === '12:00') ? 'selected' : '' ?>>12:00 hs</option>
            <option value="19:30" <?= ((string)($form['shipping_time'] ?? '') === '19:30') ? 'selected' : '' ?>>19:30 hs</option>
          </select>
        </div>
        <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <span>
            <strong>Delivery especial (Reconquista)</strong>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Fuera de horario, a coordinar</span>
          </span>
          <span style="display:flex;align-items:center;gap:12px">
            <span class="chip gold"><?= htmlspecialchars(Format::moneyRoundedFromCents(200000)) ?></span>
            <input type="radio" name="shipping_choice" value="local_reconquista_especial" <?= ((string)($form['shipping_choice'] ?? '') === 'local_reconquista_especial') ? 'checked' : '' ?> />
          </span>
        </label>
      </div>

      <div id="local-options-avellaneda" style="display:none">
        <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <span>
            <strong>Delivery gratis (Avellaneda)</strong>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Horarios: 12:00 o 19:30</span>
          </span>
          <span style="display:flex;align-items:center;gap:12px">
            <span class="chip gold">GRATIS</span>
            <input type="radio" name="shipping_choice" value="local_avellaneda_gratis" <?= ((string)($form['shipping_choice'] ?? '') === 'local_avellaneda_gratis') ? 'checked' : '' ?> />
          </span>
        </label>
        <div class="meta" style="margin:4px 0 8px 0;padding-left:4px">
          Horario: <select name="shipping_time" id="shipping-time-avellaneda-gratis">
            <option value="12:00" <?= ((string)($form['shipping_time'] ?? '12:00') === '12:00') ? 'selected' : '' ?>>12:00 hs</option>
            <option value="19:30" <?= ((string)($form['shipping_time'] ?? '') === '19:30') ? 'selected' : '' ?>>19:30 hs</option>
          </select>
        </div>
        <label class="variant" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
          <span>
            <strong>Delivery especial (Avellaneda)</strong>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Fuera de horario, a coordinar</span>
          </span>
          <span style="display:flex;align-items:center;gap:12px">
            <span class="chip gold"><?= htmlspecialchars(Format::moneyRoundedFromCents(300000)) ?></span>
            <input type="radio" name="shipping_choice" value="local_avellaneda_especial" <?= ((string)($form['shipping_choice'] ?? '') === 'local_avellaneda_especial') ? 'checked' : '' ?> />
          </span>
        </label>
      </div>

      <label class="variant" id="correo-option" style="display:flex;justify-content:space-between;align-items:center;gap:12px">
        <span>
          <strong>Correo Argentino</strong>
          <?php if ($correoCost): ?>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">
              <?php if ($correoCost['free']): ?>
                Envio gratis a todo el pais
              <?php else: ?>
                Costo: <strong><?= htmlspecialchars(Format::moneyRoundedFromCents($correoCost['final_cents'])) ?></strong>
                <?php if ($correoCost['discount_percent'] > 0): ?>
                  <span style="text-decoration:line-through;color:rgba(246,244,239,0.35)"><?= htmlspecialchars(Format::moneyRoundedFromCents($correoCost['base_cents'])) ?></span>
                  (-<?= (int)$correoCost['discount_percent'] ?>%)
                <?php endif; ?>
              <?php endif; ?>
            </span>
          <?php else: ?>
            <span style="display:block;color:rgba(246,244,239,0.6);font-size:12px">Selecciona provincia para ver costo</span>
          <?php endif; ?>
        </span>
        <span style="display:flex;align-items:center;gap:12px">
          <input type="radio" name="shipping_choice" value="correo" <?= ((string)($form['shipping_choice'] ?? '') === 'correo') ? 'checked' : '' ?> />
        </span>
      </label>
    </div>

    <?php if ($correoCost && !$correoCost['free'] && (int)$correoCost['discount_percent'] < 100): ?>
      <div class="notice" style="margin-top:12px">
        <strong>Ahorra en envio:</strong>
        <?php if ($cartTotalCents < 10000000): ?>
          Suma $<?= htmlspecialchars(Format::moneyFromCents(10000000 - $cartTotalCents)) ?> mas para obtener 50% de descuento en el envio.
        <?php elseif ($cartTotalCents < 18000000): ?>
          Suma $<?= htmlspecialchars(Format::moneyFromCents(18000000 - $cartTotalCents)) ?> mas para obtener 75% de descuento en el envio.
        <?php elseif ($cartTotalCents < 25000000): ?>
          Suma $<?= htmlspecialchars(Format::moneyFromCents(25000000 - $cartTotalCents)) ?> mas para obtener envio gratis a todo el pais.
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <button class="btn" type="submit" style="margin-top:14px; width:100%">Confirmar datos y generar pedido</button>
  </form>

  <div class="notice" style="margin-top:14px">
    <strong>Transferencia:</strong> una vez generado el pedido, vas a ver los datos de alias MP para transferir. Recorda enviar el comprobante para confirmar el pago.
  </div>
</div>

<script>
(function () {
  var province = document.querySelector('select[name="province_codprov"]')
  var place = document.getElementById('checkout-cod-lugar')
  var postal = document.getElementById('checkout-postal-code')
  var city = document.getElementById('checkout-city')
  var localReconquista = document.getElementById('local-options-reconquista')
  var localAvellaneda = document.getElementById('local-options-avellaneda')
  var localNote = document.getElementById('local-delivery-note')
  var paymentRadios = document.querySelectorAll('input[name="payment_method"]')
  var tarjetaSelect = document.getElementById('checkout-tarjeta')
  var cuotasSelect = document.getElementById('checkout-cuotas')
  if (!province || !place || !postal || !city) return

  function togglePaymentFields() {
    var selected = document.querySelector('input[name="payment_method"]:checked')
    var isMp = selected && selected.value === 'mp'
    if (tarjetaSelect) tarjetaSelect.style.display = isMp ? '' : 'none'
    if (cuotasSelect) cuotasSelect.style.display = isMp ? '' : 'none'
  }
  paymentRadios && paymentRadios.forEach(function (r) { r.addEventListener('change', togglePaymentFields) })
  togglePaymentFields()

  var allOptions = []
  Array.prototype.forEach.call(place.querySelectorAll('option'), function (option) {
    allOptions.push({
      value: option.value,
      label: option.textContent || '',
      prov: option.getAttribute('data-prov') || '',
      codpost: option.getAttribute('data-codpost') || ''
    })
  })

  function syncPlaceData(replacePostal) {
    var selected = place.options[place.selectedIndex]
    if (!selected || !selected.value || selected.value === '0') {
      city.value = ''
      return
    }
    city.value = selected.textContent || ''
    var codpost = selected.getAttribute('data-codpost') || ''
    if (replacePostal || !postal.value.trim()) {
      postal.value = codpost
    }
  }

  function syncShippingOptions() {
    var selectedValue = place.value
    var showReconquista = province.value === '3' && selectedValue === '1'
    var showAvellaneda = province.value === '3' && selectedValue === '6'
    if (localReconquista) localReconquista.style.display = showReconquista ? 'block' : 'none'
    if (localAvellaneda) localAvellaneda.style.display = showAvellaneda ? 'block' : 'none'
    if (localNote) localNote.style.display = (!showReconquista && !showAvellaneda) ? 'block' : 'none'
    if (!showReconquista && !showAvellaneda) {
      var localChecked = document.querySelector('input[name="shipping_choice"]:checked')
      if (localChecked && String(localChecked.value).indexOf('local_') === 0) {
        localChecked.checked = false
      }
    }
    toggleTimeSelectors()
  }

  function toggleTimeSelectors() {
    ['reconquista', 'avellaneda'].forEach(function (city) {
      var gratis = document.querySelector('input[name="shipping_choice"][value="local_' + city + '_gratis"]')
      var timeSel = document.getElementById('shipping-time-' + city + '-gratis')
      if (timeSel) timeSel.parentElement.style.display = (gratis && gratis.checked) ? '' : 'none'
    })
  }
  var shipRadios = document.querySelectorAll('input[name="shipping_choice"]')
  shipRadios.forEach(function (r) { r.addEventListener('change', toggleTimeSelectors) })

  function renderPlaces() {
    var selectedProv = province.value
    var currentValue = place.value || place.getAttribute('data-selected') || '0'
    place.innerHTML = ''
    allOptions.forEach(function (option) {
      if (option.value !== '0' && option.prov !== selectedProv) return
      var el = document.createElement('option')
      el.value = option.value
      el.textContent = option.label
      if (option.prov) el.setAttribute('data-prov', option.prov)
      if (option.codpost) el.setAttribute('data-codpost', option.codpost)
      if (option.value === currentValue) el.selected = true
      place.appendChild(el)
    })
    if (!place.value) {
      place.value = '0'
    }
    syncPlaceData(false)
    syncShippingOptions()
  }

  province.addEventListener('change', renderPlaces)
  place.addEventListener('change', function () {
    syncPlaceData(true)
    syncShippingOptions()
  })
  renderPlaces()
})()
</script>
