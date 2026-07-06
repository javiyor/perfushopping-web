<?php
$stats = $stats ?? [];
$pendingOrders = $pendingOrders ?? [];
$paidOrders = $paidOrders ?? [];
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
                    <a class="btn btn-outline-secondary" href="/admin/demo-tecnica"><i class="bi bi-calendar-event"></i> Demo técnica</a>
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
