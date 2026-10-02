<?php
use Perfushopping\Web\Support\Format;

$orders = $orders ?? [];
$itemsByOrder = $itemsByOrder ?? [];

$statusLabels = ['paid' => 'Pagado', 'pending_transfer' => 'Transf. pendiente'];
$statusBadges = ['paid' => 'success', 'pending_transfer' => 'warning'];

$totalPedidos = count($orders);
$totalUnidades = 0;
$totalMonto = 0;
foreach ($orders as $o) {
    $totalUnidades += (int)($o['units_count'] ?? 0);
    $totalMonto += (int)($o['total_cents'] ?? 0);
}
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Pedidos a preparar <span class="badge bg-accent"><?= $totalPedidos ?></span></h4>
        <p class="text-muted small mb-0">Pedidos pagados o con transferencia pendiente, listos para preparar</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin"><i class="bi bi-arrow-left"></i> Volver al admin</a>
</div>

<?php if (!$orders): ?>
    <div class="card shadow-sm">
        <div class="card-body text-center py-5">
            <i class="bi bi-box-seam" style="font-size:48px;color:#ccc"></i>
            <h5 class="mt-3">No hay pedidos pendientes de preparación</h5>
            <p class="text-muted">Cuando ingresen pedidos pagados van a aparecer acá.</p>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <div class="col-4">
            <div class="card-dashboard text-center p-3">
                <div class="h4 fw-bold mb-0"><?= $totalPedidos ?></div>
                <div class="small text-muted">Pedidos por preparar</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card-dashboard text-center p-3">
                <div class="h4 fw-bold mb-0"><?= $totalUnidades ?></div>
                <div class="small text-muted">Unidades totales</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card-dashboard text-center p-3">
                <div class="h4 fw-bold mb-0 text-success"><?= htmlspecialchars(Format::moneyRoundedFromCents($totalMonto)) ?></div>
                <div class="small text-muted">Monto total</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <?php foreach ($orders as $order): ?>
            <?php
            $orderId = (int)($order['id'] ?? 0);
            $detailItems = $itemsByOrder[$orderId] ?? [];
            $st = (string)($order['status'] ?? '');
            $fecha = !empty($order['created_at']) ? date('d/m/Y H:i', strtotime($order['created_at'])) : '-';
            ?>
            <div class="col-12 col-xl-6">
                <div class="card shadow-sm h-100">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center">
                        <span class="fw-bold fs-6"><i class="bi bi-receipt"></i> <?= htmlspecialchars((string)($order['order_code'] ?? ('#' . $orderId))) ?></span>
                        <span class="d-flex gap-2 align-items-center">
                            <span class="badge bg-<?= $statusBadges[$st] ?? 'secondary' ?>"><?= htmlspecialchars($statusLabels[$st] ?? $st) ?></span>
                            <span class="small text-muted"><i class="bi bi-clock"></i> <?= htmlspecialchars($fecha) ?></span>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row small mb-2">
                            <div class="col-md-6">
                                <div class="fw-semibold"><i class="bi bi-person"></i> <?= htmlspecialchars((string)($order['ship_name'] ?? '-')) ?></div>
                                <div class="text-muted"><?= htmlspecialchars((string)($order['email'] ?? '')) ?><?= ($order['phone'] ?? '') !== '' ? ' · ' . htmlspecialchars((string)$order['phone']) : '' ?></div>
                            </div>
                            <div class="col-md-6">
                                <div><i class="bi bi-truck"></i> <?= htmlspecialchars((string)($order['shipping_detail'] ?? $order['shipping_method'] ?? '-')) ?></div>
                                <div class="text-muted"><?= htmlspecialchars(trim((string)($order['ship_address'] ?? '') . ' (' . ($order['ship_postal_code'] ?? '') . ') ' . ($order['ship_city'] ?? '') . ', ' . ($order['ship_province_name'] ?? ''))) ?></div>
                            </div>
                        </div>

                        <?php if ($detailItems): ?>
                            <div class="table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Variedad</th>
                                            <th class="text-center" style="width:70px">Cant.</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($detailItems as $item): ?>
                                            <tr>
                                                <td><?= htmlspecialchars((string)($item['product_name'] ?? '-')) ?></td>
                                                <td class="text-muted"><?= htmlspecialchars((string)($item['variant_name'] ?? '-')) ?></td>
                                                <td class="text-center"><span class="badge bg-dark fs-6">x<?= (int)($item['qty'] ?? 0) ?></span></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-muted small mb-0">Sin detalle de productos.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span>
                            <span class="fw-bold fs-5"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['total_cents'] ?? 0))) ?></span>
                            <span class="text-muted small">· <?= (int)($order['items_count'] ?? 0) ?> ítems · <?= (int)($order['units_count'] ?? 0) ?> unid.</span>
                        </span>
                        <span class="d-flex gap-2">
                            <?php if ($st === 'pending_transfer'): ?>
                                <form method="post" action="/admin/order/status" style="display:inline">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                    <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                                    <input type="hidden" name="status" value="paid" />
                                    <button class="btn btn-outline-success btn-sm" type="submit"><i class="bi bi-cash-coin"></i> Marcar pagado</button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="/admin/order/status" style="display:inline">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                                <input type="hidden" name="status" value="prepared" />
                                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Marcar preparado</button>
                            </form>
                            <form method="post" action="/admin/order/status" style="display:inline" onsubmit="return confirm('¿Cancelar el pedido <?= htmlspecialchars((string)($order['order_code'] ?? ('#' . $orderId))) ?>?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                                <input type="hidden" name="status" value="cancelled" />
                                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-x-lg"></i></button>
                            </form>
                        </span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
