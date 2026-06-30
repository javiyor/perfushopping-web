<?php
use Perfushopping\Web\Support\Format;

$orders = $orders ?? [];
$itemsByOrder = $itemsByOrder ?? [];
$q = (string)($q ?? '');
$status = (string)($status ?? '');
$statusOptions = [
    '' => 'Todos los estados',
    'pending_payment' => 'Pendiente de pago',
    'paid' => 'Pagado',
    'preparing' => 'Preparando',
    'prepared' => 'Preparado',
    'shipped' => 'Enviado',
    'cancelled' => 'Cancelado',
    'archived' => 'Archivado',
    'pending_transfer' => 'Pendiente transferencia',
    'transfer_reported' => 'Transferencia informada',
];
?>

<div class="page">
  <div style="display:flex;gap:12px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
    <div>
      <h2 style="margin:0 0 8px">Pedidos web</h2>
      <p style="margin:0;color:rgba(246,244,239,0.72)">Consulta los pedidos generados desde checkout, con cliente, estado, envio y detalle de items.</p>
    </div>
    <a class="btn secondary" href="/admin">Volver al admin</a>
  </div>
</div>

<div class="page" style="margin-top:14px">
  <form method="get" action="/admin/orders" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por codigo, email, telefono, nombre o ciudad" style="min-width:280px" />
    <select name="status">
      <?php foreach ($statusOptions as $value => $label): ?>
        <option value="<?= htmlspecialchars($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
      <?php endforeach; ?>
    </select>
    <button class="btn" type="submit">Buscar</button>
    <?php if ($q !== '' || $status !== ''): ?>
      <a class="btn secondary" href="/admin/orders">Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<div class="page" style="margin-top:14px">
  <?php if (!$orders): ?>
    <div class="notice">No se encontraron pedidos.</div>
  <?php else: ?>
    <div style="display:grid;gap:14px">
      <?php foreach ($orders as $order): ?>
        <?php $orderId = (int)($order['id'] ?? 0); ?>
        <?php $detailItems = $itemsByOrder[$orderId] ?? []; ?>
        <div style="border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:16px;background:rgba(255,255,255,0.02)">
          <div style="display:flex;gap:12px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
            <div>
              <div style="font-weight:800;font-size:18px">Pedido <?= htmlspecialchars((string)($order['order_code'] ?? ('#' . $orderId))) ?></div>
              <div class="meta">#<?= $orderId ?> · <?= htmlspecialchars((string)($order['customer_type'] ?? '-')) ?> · Estado: <?= htmlspecialchars((string)($order['status'] ?? '-')) ?> · Fecha: <?= htmlspecialchars((string)($order['created_at'] ?? '-')) ?></div>
              <div class="meta">Cliente: <?= htmlspecialchars((string)($order['ship_name'] ?? '-')) ?> · <?= htmlspecialchars((string)($order['email'] ?? '-')) ?> · <?= htmlspecialchars((string)($order['phone'] ?? '-')) ?></div>
              <div class="meta">Envio: <?= htmlspecialchars((string)($order['shipping_method'] ?? '-')) ?><?= !empty($order['shipping_detail']) ? ' · ' . htmlspecialchars((string)$order['shipping_detail']) : '' ?> · <?= htmlspecialchars((string)($order['ship_city'] ?? '-')) ?>, <?= htmlspecialchars((string)($order['ship_province_name'] ?? '-')) ?><?= !empty($order['ship_cod_lugar']) ? ' · Lugar #' . (int)$order['ship_cod_lugar'] : '' ?></div>
              <div class="meta">Direccion: <?= htmlspecialchars((string)($order['ship_address'] ?? '-')) ?> (CP <?= htmlspecialchars((string)($order['ship_postal_code'] ?? '-')) ?>)</div>
            </div>
            <div style="min-width:220px;text-align:right">
              <div><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['total_cents'] ?? 0))) ?></strong></div>
              <div class="meta">Subtotal: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['subtotal_net_cents'] ?? 0))) ?> · IVA: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['iva_cents'] ?? 0))) ?></div>
              <div class="meta">Envio: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['shipping_cost_cents'] ?? 0))) ?> · Desc.: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['discount_cents'] ?? 0))) ?></div>
              <div class="meta">Items: <?= (int)($order['items_count'] ?? 0) ?> · Unidades: <?= (int)($order['units_count'] ?? 0) ?></div>
            </div>
          </div>

          <?php if ($detailItems): ?>
            <div style="margin-top:12px;display:grid;gap:8px">
              <?php foreach ($detailItems as $item): ?>
                <div style="display:flex;gap:10px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;border-top:1px solid rgba(255,255,255,0.06);padding-top:8px">
                  <div>
                    <strong><?= htmlspecialchars((string)($item['product_name'] ?? '-')) ?></strong>
                    <div class="meta">Variedad: <?= htmlspecialchars((string)($item['variant_name'] ?? '-')) ?> · ID producto: <?= (int)($item['idprodu'] ?? 0) ?> · ID gusto: <?= (int)($item['idcodgusto'] ?? 0) ?></div>
                  </div>
                  <div style="text-align:right">
                    <div>Cant.: <?= (int)($item['qty'] ?? 0) ?></div>
                    <div class="meta">Unit.: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($item['unit_net_cents'] ?? 0))) ?> · Linea: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($item['line_total_cents'] ?? 0))) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <?php $currentStatus = (string)($order['status'] ?? ''); ?>
          <?php if (in_array($currentStatus, ['paid','pending_transfer','preparing','prepared'], true)): ?>
            <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
              <?php if ($currentStatus === 'paid' || $currentStatus === 'pending_transfer'): ?>
                <form method="post" action="/admin/order/status" style="display:inline">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                  <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                  <input type="hidden" name="status" value="preparing" />
                  <button class="btn" type="submit">Preparando</button>
                </form>
              <?php endif; ?>
              <?php if ($currentStatus === 'preparing'): ?>
                <form method="post" action="/admin/order/status" style="display:inline">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                  <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                  <input type="hidden" name="status" value="prepared" />
                  <button class="btn" type="submit">Preparado</button>
                </form>
              <?php endif; ?>
              <?php if ($currentStatus === 'prepared'): ?>
                <form method="post" action="/admin/order/status" style="display:inline">
                  <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                  <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                  <input type="hidden" name="status" value="shipped" />
                  <button class="btn" type="submit">Enviado</button>
                </form>
              <?php endif; ?>
              <form method="post" action="/admin/order/status" style="display:inline">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                <input type="hidden" name="status" value="cancelled" />
                <button class="btn danger" type="submit">Cancelar</button>
              </form>
            </div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>


