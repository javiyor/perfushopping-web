<?php
use Perfushopping\Web\Support\Format;

$presupuesto = $presupuesto ?? null;
$items = $items ?? [];
if (!$presupuesto):
?>
    <div class="alert alert-warning">Presupuesto no encontrado.</div>
<?php return; endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/presupuestos">Presupuestos</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars((string)($presupuesto['codigo'] ?? '')) ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Productos</span>
                <span class="badge bg-secondary"><?= count($items) ?> item(s)</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
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
                                <td><?= htmlspecialchars((string)($it['producto'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($it['variedad'] ?? '')) ?: '<span class="text-muted">—</span>' ?></td>
                                <td class="text-center"><?= (int)($it['qty'] ?? 0) ?></td>
                                <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($it['unit_price_cents'] ?? 0))) ?></td>
                                <td class="text-end fw-bold"><?= htmlspecialchars(Format::moneyFromCents((int)($it['total_cents'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <div class="card-footer bg-white text-end">
                <div class="row">
                    <div class="col-md-5 offset-md-7">
                        <div class="d-flex justify-content-between small">
                            <span>Subtotal:</span>
                            <span><?= htmlspecialchars(Format::moneyFromCents((int)($presupuesto['subtotal_cents'] ?? 0))) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>IVA:</span>
                            <span><?= htmlspecialchars(Format::moneyFromCents((int)($presupuesto['iva_cents'] ?? 0))) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold" style="font-size:18px">
                            <span>Total:</span>
                            <span><?= htmlspecialchars(Format::moneyFromCents((int)($presupuesto['total_cents'] ?? 0))) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Estado</span>
                <?php $badge = ['pendiente'=>'warning','aprobado'=>'success','rechazado'=>'danger','vencido'=>'secondary']; ?>
                <span class="badge bg-<?= $badge[$presupuesto['estado'] ?? 'pendiente'] ?? 'secondary' ?> fs-6">
                    <?= htmlspecialchars($presupuesto['estado'] ?? 'pendiente') ?>
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php if (($presupuesto['estado'] ?? '') === 'pendiente'): ?>
                        <form method="post" action="/admin/presupuestos/estado" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)($presupuesto['id'] ?? 0) ?>" />
                            <input type="hidden" name="estado" value="aprobado" />
                            <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-check-lg"></i> Aprobar</button>
                        </form>
                        <form method="post" action="/admin/presupuestos/estado" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)($presupuesto['id'] ?? 0) ?>" />
                            <input type="hidden" name="estado" value="rechazado" />
                            <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-x-lg"></i> Rechazar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Cliente</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Nombre</dt>
                    <dd class="col-sm-8 fw-bold"><?= htmlspecialchars((string)($presupuesto['cliente_nombre'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">CUIT</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['cliente_cuit'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Teléfono</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['cliente_tele'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Email</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['cliente_mail'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Dirección</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['cliente_direc'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Detalles</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Código</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars((string)($presupuesto['codigo'] ?? '')) ?></strong></dd>
                    <dt class="col-sm-4">Fecha</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['fecha'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Válido hasta</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['valido_hasta'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Creado por</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['created_by_nombre'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Creado</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($presupuesto['created_at'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>

        <?php if (($presupuesto['notas'] ?? '') !== ''): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Notas</div>
            <div class="card-body">
                <p class="small mb-0"><?= nl2br(htmlspecialchars((string)$presupuesto['notas'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <form method="post" action="/admin/presupuestos/delete" onsubmit="return confirm('Eliminar este presupuesto?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                <input type="hidden" name="id" value="<?= (int)($presupuesto['id'] ?? 0) ?>" />
                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Eliminar</button>
            </form>
        </div>
    </div>
</div>
