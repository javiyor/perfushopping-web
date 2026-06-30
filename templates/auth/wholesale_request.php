<?php
use Perfushopping\Web\Support\Csrf;
?>

<div class="page" style="max-width:760px;margin:18px auto 0">
  <h2 style="margin:0 0 12px">Solicitud mayorista</h2>
  <div class="notice">La aprobacion es manual. Una vez aprobado, veras precio mayorista y pago solo por transferencia.</div>
  <form method="post" action="/wholesale/request">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
    <div class="filters" style="grid-template-columns:1fr 1fr">
      <input name="razon_social" placeholder="Razon social" />
      <input name="cuit" placeholder="CUIT" />
      <input name="address" placeholder="Direccion" />
      <input name="city" placeholder="Localidad" />
      <input name="postal_code" placeholder="Codigo postal" />
      <select name="customer_category">
        <?php foreach (($customerCategories ?? []) as $value => $label): ?>
          <option value="<?= htmlspecialchars((string)$value) ?>"><?= htmlspecialchars((string)$label) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="province_codprov">
        <option value="0">Provincia</option>
        <?php foreach ($provincias as $p): ?>
          <option value="<?= (int)$p['codprov'] ?>"><?= htmlspecialchars((string)$p['provinci']) ?></option>
        <?php endforeach; ?>
      </select>
      <textarea name="notes" placeholder="Notas (opcional)" style="grid-column:1/-1;min-height:90px"></textarea>
    </div>
    <button class="btn" type="submit" style="margin-top:12px">Enviar solicitud</button>
  </form>
</div>
