<?php
use Perfushopping\Web\Support\Format;

$customer = $customer ?? null;
$orders = $orders ?? [];
$itemsByOrder = $itemsByOrder ?? [];
$clienteErp = $clienteErp ?? null;
$notas = $notas ?? [];
$customerCategories = [
    'none' => 'Sin categoría', 'peluquero' => 'Peluquero/a', 'cosmetologa' => 'Cosmetóloga',
    'esteticista' => 'Esteticista', 'manicura' => 'Manicura/o', 'masajista' => 'Masajista',
    'barbero' => 'Barbero/a', 'maquillador' => 'Maquillador/a', 'spa' => 'Spa / centro estético',
    'revendedor' => 'Revendedor/a', 'otro' => 'Otro profesional',
];

if (!$customer):
?>
    <div class="alert alert-warning">Cliente no encontrado.</div>
<?php return; endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/clientes">Clientes</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars((string)($customer['name'] ?? $customer['email'] ?? '')) ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm h-100">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person"></i> Datos del cliente</span>
                <?php if (!empty($customer['disabled_at'])): ?>
                    <span class="badge bg-secondary">Bloqueado</span>
                <?php else: ?>
                    <span class="badge bg-success">Activo</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">ID</dt>
                    <dd class="col-sm-8"><?= (int)($customer['id'] ?? 0) ?></dd>

                    <dt class="col-sm-4">Nombre</dt>
                    <dd class="col-sm-8 fw-bold"><?= htmlspecialchars((string)($customer['name'] ?? 'Sin nombre')) ?></dd>

                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($customer['email'] ?? '-')) ?></dd>

                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($customer['phone'] ?? '-')) ?></dd>

                    <dt class="col-sm-4">Dirección</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($customer['address'] ?? '-')) ?>, <?= htmlspecialchars((string)($customer['city'] ?? '-')) ?> <?= htmlspecialchars((string)($customer['postal_code'] ?? '')) ?></dd>

                    <dt class="col-sm-4">Categoría</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars($customerCategories[$customer['customer_category'] ?? 'none'] ?? 'Sin categoría') ?></dd>

                    <dt class="col-sm-4">Mayorista</dt>
                    <dd class="col-sm-8">
                        <?php $ws = $customer['wholesale_status'] ?? 'none'; ?>
                        <?php if ($ws === 'approved'): ?><span class="badge bg-info">Aprobado</span>
                        <?php elseif ($ws === 'pending'): ?><span class="badge bg-warning">Pendiente</span>
                        <?php elseif ($ws === 'rejected'): ?><span class="badge bg-danger">Rechazado</span>
                        <?php else: ?><span class="text-muted">No aplica</span>
                        <?php endif; ?>
                    </dd>

                    <dt class="col-sm-4">Registrado</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($customer['created_at'] ?? '-')) ?></dd>

                    <dt class="col-sm-4">Último login</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($customer['last_login_at'] ?? '-')) ?></dd>

                    <dt class="col-sm-4">Pedidos</dt>
                    <dd class="col-sm-8"><?= (int)($customer['order_count'] ?? 0) ?></dd>

                    <dt class="col-sm-4">Total gastado</dt>
                    <dd class="col-sm-8 fw-bold"><?= htmlspecialchars(Format::moneyFromCents((int)($customer['total_spent_cents'] ?? 0))) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <?php if ($clienteErp): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-building"></i> Cliente ERP (tabla <code>clientes</code>)</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">ID ERP</dt>
                    <dd class="col-sm-8"><?= (int)($clienteErp['idclien'] ?? 0) ?></dd>
                    <dt class="col-sm-4">Código</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['cod_cli'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Razón social</dt>
                    <dd class="col-sm-8 fw-bold"><?= htmlspecialchars((string)($clienteErp['razon'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">CUIT</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['cuit'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Dirección</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['direc'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Localidad</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['Localidad'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">C.P.</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['codpost'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['tele'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['mail'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Categoría</dt>
                    <dd class="col-sm-8"><?= (int)($clienteErp['codcat'] ?? 0) ?></dd>
                    <dt class="col-sm-4">Vendedor</dt>
                    <dd class="col-sm-8"><?= (int)($clienteErp['codvend'] ?? 0) ?></dd>
                    <dt class="col-sm-4">Alta</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($clienteErp['fealta'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-journal-text"></i> Notas internas</div>
            <div class="card-body">
                <form method="post" action="/admin/clientes/nota" class="mb-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="user_id" value="<?= (int)($customer['id'] ?? 0) ?>" />
                    <div class="input-group input-group-sm">
                        <textarea class="form-control" name="nota" rows="2" placeholder="Agregar nota..." required></textarea>
                        <button class="btn btn-accent" type="submit"><i class="bi bi-plus-lg"></i></button>
                    </div>
                </form>

                <?php if (!$notas): ?>
                    <div class="small text-muted">Sin notas aún.</div>
                <?php else: ?>
                    <div style="max-height:280px;overflow-y:auto">
                        <?php foreach ($notas as $n): ?>
                            <div class="border-bottom pb-2 mb-2">
                                <div class="small"><?= nl2br(htmlspecialchars((string)($n['nota'] ?? ''))) ?></div>
                                <div class="small text-muted mt-1">
                                    <?= htmlspecialchars((string)($n['admin_nombre'] ?? 'Admin')) ?>
                                    · <?= htmlspecialchars((string)($n['created_at'] ?? '-')) ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cart-check"></i> Historial de pedidos</span>
        <span class="badge bg-secondary"><?= count($orders) ?> pedidos</span>
    </div>
    <div class="card-body p-0">
        <?php if (!$orders): ?>
            <div class="p-3 text-muted small">Este cliente no tiene pedidos registrados.</div>
        <?php else: ?>
            <?php foreach ($orders as $o):
                $oid = (int)($o['id'] ?? 0);
                $items = $itemsByOrder[$oid] ?? [];
                $statusLabels = [
                    'pending_payment' => ['Pendiente pago', 'warning'],
                    'paid' => ['Pagado', 'success'],
                    'preparing' => ['Preparando', 'info'],
                    'prepared' => ['Preparado', 'primary'],
                    'shipped' => ['Enviado', 'primary'],
                    'cancelled' => ['Cancelado', 'danger'],
                    'archived' => ['Archivado', 'secondary'],
                    'pending_transfer' => ['Transf. pend.', 'warning'],
                    'transfer_reported' => ['Transf. inf.', 'info'],
                ];
                $sl = $statusLabels[$o['status'] ?? ''] ?? [$o['status'] ?? 'Desconocido', 'secondary'];
            ?>
                <div class="border-bottom">
                    <div class="p-3">
                        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                            <div>
                                <strong><?= htmlspecialchars((string)($o['order_code'] ?? '#' . $oid)) ?></strong>
                                <span class="badge bg-<?= $sl[1] ?> ms-1"><?= htmlspecialchars($sl[0]) ?></span>
                                <div class="small text-muted mt-1">
                                    <?= htmlspecialchars((string)($o['created_at'] ?? '-')) ?>
                                    · <?= htmlspecialchars((string)($o['customer_type'] ?? '-')) ?>
                                    · <?= (int)($o['items_count'] ?? 0) ?> item(s)
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="fw-bold"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)($o['total_cents'] ?? 0))) ?></div>
                                <button class="btn btn-sm btn-outline-secondary mt-1" type="button" data-bs-toggle="collapse" data-bs-target="#orderItems<?= $oid ?>">
                                    <i class="bi bi-chevron-down"></i> Detalle
                                </button>
                            </div>
                        </div>
                        <div class="collapse mt-2" id="orderItems<?= $oid ?>">
                            <?php if (!$items): ?>
                                <div class="small text-muted">Sin detalle.</div>
                            <?php else: ?>
                                <table class="table table-sm table-borderless mb-0 small">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            <th>Variedad</th>
                                            <th class="text-center">Cant.</th>
                                            <th class="text-end">P. unit.</th>
                                            <th class="text-end">Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($items as $it): ?>
                                        <tr>
                                            <td><?= htmlspecialchars((string)($it['product_name'] ?? '')) ?></td>
                                            <td><?= htmlspecialchars((string)($it['variant_name'] ?? '')) ?></td>
                                            <td class="text-center"><?= (int)($it['qty'] ?? 0) ?></td>
                                            <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($it['unit_net_cents'] ?? 0))) ?></td>
                                            <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($it['line_total_cents'] ?? 0))) ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                            <div class="small text-muted mt-1">
                                Envío: <?= htmlspecialchars((string)($o['shipping_method'] ?? '-')) ?>
                                · <?= htmlspecialchars((string)($o['ship_city'] ?? '-')) ?>, <?= htmlspecialchars((string)($o['ship_province_name'] ?? '-')) ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
