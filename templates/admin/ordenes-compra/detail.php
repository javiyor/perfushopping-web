<?php
use Perfushopping\Web\Support\Format;

$orden = $orden ?? null;
$items = $items ?? [];
if (!$orden) { echo '<div class="alert alert-warning">Orden no encontrada.</div>'; return; }

$badgeMap = ['pendiente' => 'warning', 'aprobada' => 'success', 'recibida' => 'primary', 'anulada' => 'secondary'];
$estados = ['pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'recibida' => 'Recibida', 'anulada' => 'Anulada'];
$noTransition = ['anulada', 'recibida'];
$canChange = !in_array($orden['estado'], $noTransition, true);
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/ordenes-compra">Órdenes de compra</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($orden['codigo'] ?? '') ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($orden['codigo'] ?? '') ?></span>
                <span class="badge bg-<?= $badgeMap[$orden['estado'] ?? 'pendiente'] ?? 'secondary' ?> fs-6"><?= htmlspecialchars($estados[$orden['estado'] ?? ''] ?? $orden['estado'] ?? '') ?></span>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-3">Proveedor</dt>
                    <dd class="col-sm-9 fw-bold"><?= htmlspecialchars((string)($orden['proveedor_nombre'] ?? '-')) ?></dd>
                    <dt class="col-sm-3">Fecha pedido</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)($orden['fecha'] ?? '-')) ?></dd>
                    <dt class="col-sm-3">Recepción estimada</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)($orden['fecha_estimada'] ?? 'No especificada')) ?></dd>
                    <dt class="col-sm-3">Total</dt>
                    <dd class="col-sm-9 fw-bold fs-5"><?= Format::moneyFromCents((int)($orden['total_cents'] ?? 0)) ?></dd>
                    <dt class="col-sm-3">Creado por</dt>
                    <dd class="col-sm-9 text-muted"><?= htmlspecialchars((string)($orden['created_by_nombre'] ?? '-')) ?></dd>
                    <?php if ($orden['notas'] ?? ''): ?>
                    <dt class="col-sm-3">Notas</dt>
                    <dd class="col-sm-9"><?= nl2br(htmlspecialchars((string)$orden['notas'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Productos</span>
                <span class="badge bg-secondary"><?= count($items) ?> items</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Variedad</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Precio unit.</th>
                            <th class="text-end">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$items): ?>
                            <tr><td colspan="5" class="text-muted text-center">Sin items</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($it['producto'] ?? '-')) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($it['variedad'] ?? '-')) ?></td>
                                    <td class="text-center"><?= (int)($it['qty'] ?? 0) ?></td>
                                    <td class="text-end"><?= Format::moneyFromCents((int)($it['unit_price_cents'] ?? 0)) ?></td>
                                    <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($it['total_cents'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">Total</td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($orden['total_cents'] ?? 0)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Acciones</div>
            <div class="card-body">
                <?php if ($canChange): ?>
                <div class="d-grid gap-2">
                    <?php foreach (['aprobada', 'recibida', 'anulada'] as $est): ?>
                        <?php if ($est === $orden['estado']) continue; ?>
                        <form method="post" action="/admin/ordenes-compra/estado">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$orden['id'] ?>" />
                            <input type="hidden" name="estado" value="<?= $est ?>" />
                            <button class="btn btn-<?= $est === 'aprobada' ? 'success' : ($est === 'recibida' ? 'primary' : 'danger') ?> btn-sm w-100" type="submit">
                                <i class="bi bi-<?= $est === 'aprobada' ? 'check-circle' : ($est === 'recibida' ? 'truck' : 'x-circle') ?>"></i>
                                Marcar como <?= $estados[$est] ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
                <hr />
                <?php endif; ?>
                <form method="post" action="/admin/ordenes-compra/delete" onsubmit="return confirm('¿Eliminar esta orden?')">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="id" value="<?= (int)$orden['id'] ?>" />
                    <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-trash"></i> Eliminar</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Línea de tiempo</div>
            <div class="card-body small">
                <ul class="list-unstyled mb-0">
                    <li><span class="badge bg-warning me-1">1</span> Pendiente</li>
                    <li><span class="badge bg-success me-1">2</span> Aprobada</li>
                    <li><span class="badge bg-primary me-1">3</span> Recibida</li>
                    <li class="text-muted mt-2"><i class="bi bi-info-circle"></i> Una vez recibida o anulada no se puede cambiar el estado.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
