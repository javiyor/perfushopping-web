<?php
use Perfushopping\Web\Support\Format;

$orders = $orders ?? [];
$itemsByOrder = $itemsByOrder ?? [];
?>

<div class="page">
  <div style="display:flex;gap:12px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
    <div>
      <h2 style="margin:0 0 8px">Pedidos a preparar</h2>
      <p style="margin:0;color:rgba(246,244,239,0.72)">Pedidos pagados o con transferencia pendiente, listos para preparar.</p>
    </div>
    <a class="btn secondary" href="/admin">Volver al admin</a>
  </div>
</div>

<div class="page" style="margin-top:14px">
  <?php if (!$orders): ?>
    <div class="notice ok">No hay pedidos pendientes de preparacion.</div>
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
              <div class="meta">Envio: <?= htmlspecialchars((string)($order['shipping_detail'] ?? $order['shipping_method'] ?? '-')) ?> · <?= htmlspecialchars((string)($order['ship_city'] ?? '-')) ?>, <?= htmlspecialchars((string)($order['ship_province_name'] ?? '-')) ?></div>
              <div class="meta">Direccion: <?= htmlspecialchars((string)($order['ship_address'] ?? '-')) ?> (CP <?= htmlspecialchars((string)($order['ship_postal_code'] ?? '-')) ?>)</div>
            </div>
            <div style="text-align:right">
              <div><strong><?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['total_cents'] ?? 0))) ?></strong></div>
              <div class="meta">Items: <?= (int)($order['items_count'] ?? 0) ?> · Unidades: <?= (int)($order['units_count'] ?? 0) ?></div>
            </div>
          </div>

          <?php if ($detailItems): ?>
            <div style="margin-top:12px;display:grid;gap:8px">
              <?php foreach ($detailItems as $item): ?>
                <div style="display:flex;gap:10px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;border-top:1px solid rgba(255,255,255,0.06);padding-top:8px">
                  <div style="flex:1">
                    <strong><?= htmlspecialchars((string)($item['product_name'] ?? '-')) ?></strong>
                    <div class="meta">Variedad: <?= htmlspecialchars((string)($item['variant_name'] ?? '-')) ?></div>
                  </div>
                  <div style="text-align:right;min-width:60px">
                    <div style="font-size:18px;font-weight:700">x<?= (int)($item['qty'] ?? 0) ?></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <div style="margin-top:12px;display:flex;gap:8px;flex-wrap:wrap">
            <form method="post" action="/admin/order/status" style="display:inline">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
              <input type="hidden" name="order_id" value="<?= $orderId ?>" />
              <input type="hidden" name="status" value="prepared" />
              <button class="btn" type="submit">Marcar preparado</button>
            </form>
            <form method="post" action="/admin/order/status" style="display:inline">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
              <input type="hidden" name="order_id" value="<?= $orderId ?>" />
              <input type="hidden" name="status" value="cancelled" />
              <button class="btn danger" type="submit">Cancelar pedido</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
