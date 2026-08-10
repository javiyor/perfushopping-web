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
$statusBadge = [
    'pending_payment' => 'bg-warning text-dark',
    'paid' => 'bg-success',
    'preparing' => 'bg-primary',
    'prepared' => 'bg-secondary',
    'shipped' => 'bg-info text-dark',
    'cancelled' => 'bg-danger',
    'archived' => 'bg-dark',
    'pending_transfer' => 'bg-warning text-dark',
    'transfer_reported' => 'bg-info text-dark',
];
?>

<div class="page-title">
    <h2><i class="bi bi-cart"></i> Pedidos web</h2>
    <p>Consulta los pedidos generados desde checkout, con cliente, estado, envio y detalle de items.</p>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="get" action="/admin/orders" class="row g-2">
            <div class="col-md-5">
                <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por codigo, email, telefono, nombre o ciudad" />
            </div>
            <div class="col-md-3">
                <select class="form-select" name="status">
                    <?php foreach ($statusOptions as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button class="btn btn-accent" type="submit"><i class="bi bi-search"></i> Buscar</button>
                <?php if ($q !== '' || $status !== ''): ?>
                    <a class="btn btn-outline-secondary" href="/admin/orders"><i class="bi bi-x-circle"></i> Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<?php if (!$orders): ?>
    <div class="alert alert-info">No se encontraron pedidos.</div>
<?php else: ?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Resultados</span>
        <span class="badge bg-secondary"><?= count($orders) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-admin table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Pedido</th>
                    <th>Fecha</th>
                    <th>Cliente</th>
                    <th>Envio</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php $orderId = (int)($order['id'] ?? 0); ?>
                    <?php $detailItems = $itemsByOrder[$orderId] ?? []; ?>
                    <?php $currentStatus = (string)($order['status'] ?? ''); ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)($order['order_code'] ?? ('#' . $orderId))) ?></strong>
                            <div class="small text-muted">#<?= $orderId ?> · <?= htmlspecialchars((string)($order['customer_type'] ?? '-')) ?></div>
                            <?php if ($detailItems): ?>
                                <details class="mt-1">
                                    <summary class="small text-primary" style="cursor:pointer">Items (<?= (int)($order['items_count'] ?? count($detailItems)) ?>)</summary>
                                    <ul class="list-unstyled small mt-2 mb-1" style="max-width:340px">
                                        <?php foreach ($detailItems as $item): ?>
                                            <li class="border-top pt-1 mt-1">
                                                <div class="fw-semibold"><?= htmlspecialchars((string)($item['product_name'] ?? '-')) ?></div>
                                                <div class="text-muted"><?= htmlspecialchars((string)($item['variant_name'] ?? '-')) ?> · Cant. <?= (int)($item['qty'] ?? 0) ?> · <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($item['line_total_cents'] ?? 0))) ?></div>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </details>
                            <?php endif; ?>
                        </td>
                        <td class="text-nowrap">
                            <div><?= htmlspecialchars((string)($order['created_at'] ?? '-')) ?></div>
                            <div class="small text-muted">Items: <?= (int)($order['items_count'] ?? 0) ?> · Unid.: <?= (int)($order['units_count'] ?? 0) ?></div>
                        </td>
                        <td>
                            <div class="fw-semibold"><?= htmlspecialchars((string)($order['ship_name'] ?? '-')) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string)($order['email'] ?? '-')) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string)($order['phone'] ?? '-')) ?></div>
                        </td>
                        <td>
                            <div><?= htmlspecialchars((string)($order['shipping_method'] ?? '-')) ?></div>
                            <div class="small text-muted"><?= htmlspecialchars((string)($order['ship_city'] ?? '-')) ?><?= !empty($order['ship_province_name']) ? ', ' . htmlspecialchars((string)$order['ship_province_name']) : '' ?></div>
                            <?php if (!empty($order['shipping_detail'])): ?>
                                <div class="small text-muted"><?= htmlspecialchars((string)$order['shipping_detail']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="text-end text-nowrap">
                            <span class="fw-bold"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['total_cents'] ?? 0))) ?></span>
                            <div class="small text-muted">Subtotal: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['subtotal_net_cents'] ?? 0))) ?></div>
                            <div class="small text-muted">Envio: <?= htmlspecialchars(Format::moneyRoundedFromCents((int)($order['shipping_cost_cents'] ?? 0))) ?></div>
                        </td>
                        <td>
                            <span class="badge <?= $statusBadge[$currentStatus] ?? 'bg-secondary' ?>"><?= htmlspecialchars($statusOptions[$currentStatus] ?? $currentStatus) ?></span>
                        </td>
                        <td class="text-end">
                            <form method="post" action="/admin/order/status" class="d-inline-flex gap-1">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                <input type="hidden" name="order_id" value="<?= $orderId ?>" />
                                <select class="form-select form-select-sm" name="status" style="max-width:170px">
                                    <?php foreach ($statusOptions as $value => $label): ?>
                                        <?php if ($value === '') continue; ?>
                                        <option value="<?= htmlspecialchars($value) ?>" <?= $currentStatus === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
