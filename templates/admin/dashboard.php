<?php
$stats = $stats ?? [];
$pendingOrders = $pendingOrders ?? [];
$paidOrders = $paidOrders ?? [];
$abandonedCarts = $abandonedCarts ?? [];
$topProducts = $topProducts ?? [];
$adminRol = $adminUser['rol'] ?? '';
$isSuper = $adminRol === 'superadmin';
?>

<div class="d-flex justify-content-between align-items-start mb-4">
    <div>
        <h2 class="fw-bold mb-1">Panel Principal</h2>
        <p class="text-muted">Resumen del sistema de gestión</p>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0"><?= (int)($stats['orders_today'] ?? 0) ?></div>
            <div class="small text-muted">Pedidos hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0 text-warning"><?= (int)($stats['pending_payment'] ?? 0) ?></div>
            <div class="small text-muted">Pendientes pago</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0 text-primary"><?= (int)($stats['paid'] ?? 0) ?></div>
            <div class="small text-muted">Pagados</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0 text-info"><?= (int)($stats['pending_transfer'] ?? 0) ?></div>
            <div class="small text-muted">Transf. pend.</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0 text-success"><?= (int)($stats['users_today'] ?? 0) ?></div>
            <div class="small text-muted">Usuarios hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0"><?= (int)($stats['admins'] ?? 0) ?></div>
            <div class="small text-muted">Admins activos</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0 text-success"><?= (int)($stats['visitas_hoy'] ?? 0) ?></div>
            <div class="small text-muted">Visitas hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0 text-success"><?= (int)($stats['visitantes_hoy'] ?? 0) ?></div>
            <div class="small text-muted">Visitantes hoy</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0"><?= (int)($stats['visitas_7d'] ?? 0) ?></div>
            <div class="small text-muted">Visitas 7 días</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0"><?= (int)($stats['visitantes_7d'] ?? 0) ?></div>
            <div class="small text-muted">Visitantes 7 días</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="card-dashboard text-center">
            <div class="h3 fw-bold mb-0 text-danger"><?= (int)($stats['abandoned'] ?? 0) ?></div>
            <div class="small text-muted">Carritos abandonados</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Acceso rápido</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-accent" href="/admin/prepare"><i class="bi bi-box"></i> Preparar pedidos</a>
                    <a class="btn btn-outline-secondary" href="/admin/orders"><i class="bi bi-cart"></i> Todos los pedidos</a>
                    <a class="btn btn-outline-secondary" href="/admin/productos"><i class="bi bi-box-seam"></i> Productos</a>
                    <a class="btn btn-outline-secondary" href="/admin/clientes"><i class="bi bi-people"></i> Clientes</a>
                    <a class="btn btn-outline-secondary" href="/admin/users"><i class="bi bi-person-gear"></i> Usuarios web</a>
                    <?php if ($isSuper): ?>
                    <a class="btn btn-outline-secondary" href="/admin/usuarios"><i class="bi bi-shield-lock"></i> Admins</a>
                    <?php endif; ?>
                    <a class="btn btn-outline-secondary" href="/admin/wholesale"><i class="bi bi-shop"></i> Mayoristas</a>
                    <a class="btn btn-outline-secondary" href="/admin/withdrawals"><i class="bi bi-cash"></i> Retiros</a>
                    <a class="btn btn-outline-secondary" href="/admin/correo"><i class="bi bi-truck"></i> Correo Argentino</a>
                    <a class="btn btn-outline-secondary" href="/admin/capacitaciones"><i class="bi bi-calendar-event"></i> Capacitaciones</a>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Acciones</div>
            <div class="card-body">
                <form method="post" action="/admin/affiliate/release" class="mb-2">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-cash-stack"></i> Liberar comisiones pendientes</button>
                </form>
                <form method="post" action="/admin/orders/recover-abandoned" class="mb-2">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-envelope"></i> Email recuperación carritos</button>
                </form>
                <form method="post" action="/admin/orders/archive-abandoned">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-archive"></i> Archivar carritos abandonados</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Últimos pedidos pendientes</span>
                <span class="badge bg-warning"><?= count($pendingOrders) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!$pendingOrders): ?>
                    <div class="p-3 text-muted small">No hay pedidos pendientes de pago.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($pendingOrders as $o): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <a href="/admin/orders?q=<?= urlencode((string)($o['order_code'] ?? '')) ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars((string)($o['order_code'] ?? '#' . $o['id'])) ?></a>
                                    <div class="small text-muted"><?= htmlspecialchars(mb_substr((string)($o['email'] ?? ''), 0, 30)) ?></div>
                                </div>
                                <span class="badge bg-warning rounded-pill"><?= \Perfushopping\Web\Support\Format::moneyRoundedFromCents((int)($o['total_cents'] ?? 0)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Últimos pagados</span>
                <span class="badge bg-primary"><?= count($paidOrders) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!$paidOrders): ?>
                    <div class="p-3 text-muted small">No hay pedidos pagados recientes.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($paidOrders as $o): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <a href="/admin/orders?q=<?= urlencode((string)($o['order_code'] ?? '')) ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars((string)($o['order_code'] ?? '#' . $o['id'])) ?></a>
                                    <div class="small text-muted"><?= htmlspecialchars(mb_substr((string)($o['email'] ?? ''), 0, 30)) ?></div>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?= \Perfushopping\Web\Support\Format::moneyRoundedFromCents((int)($o['total_cents'] ?? 0)) ?></span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row g-3 mt-1">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Productos más vistos (30 días)</span>
            </div>
            <div class="card-body p-0">
                <?php if (!$topProducts): ?>
                    <div class="p-3 text-muted small">Aún no hay datos de visitas de productos.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($topProducts as $tp): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                                <a href="/admin/productos/<?= (int)($tp['idprodu'] ?? 0) ?>" class="fw-semibold text-decoration-none text-truncate" style="max-width:75%"><?= htmlspecialchars((string)($tp['produ'] ?? '')) ?></a>
                                <span class="badge bg-success rounded-pill"><?= (int)($tp['vistas'] ?? 0) ?> visitas</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Carritos abandonados con contacto</span>
                <span class="badge bg-danger"><?= count($abandonedCarts) ?></span>
            </div>
            <div class="card-body p-0">
                <?php if (!$abandonedCarts): ?>
                    <div class="p-3 text-muted small">No hay carritos abandonados recientes.</div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($abandonedCarts as $o): ?>
                            <li class="list-group-item py-2">
                                <div class="d-flex justify-content-between align-items-center">
                                    <a href="/admin/orders?q=<?= urlencode((string)($o['order_code'] ?? '')) ?>" class="fw-semibold text-decoration-none"><?= htmlspecialchars((string)($o['order_code'] ?? '#' . $o['id'])) ?></a>
                                    <span class="badge bg-warning rounded-pill"><?= \Perfushopping\Web\Support\Format::moneyRoundedFromCents((int)($o['total_cents'] ?? 0)) ?></span>
                                </div>
                                <div class="small text-muted"><?= htmlspecialchars((string)($o['ship_name'] ?? '')) ?></div>
                                <div class="small">
                                    <?php $abEmail = trim((string)($o['email'] ?? '')); ?>
                                    <?php $abPhone = trim((string)($o['phone'] ?? '')); ?>
                                    <?php if ($abEmail !== ''): ?>
                                        <a class="me-2" href="mailto:<?= htmlspecialchars($abEmail) ?>"><i class="bi bi-envelope"></i> <?= htmlspecialchars(mb_substr($abEmail, 0, 30)) ?></a>
                                    <?php endif; ?>
                                    <?php if ($abPhone !== ''): ?>
                                        <a href="https://wa.me/<?= rawurlencode(preg_replace('/\D/', '', $abPhone)) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> <?= htmlspecialchars($abPhone) ?></a>
                                    <?php endif; ?>
                                </div>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
