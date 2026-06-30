<?php
$remito = $remito ?? null;
$items = $items ?? [];
if (!$remito):
?>
    <div class="alert alert-warning">Remito no encontrado.</div>
<?php return; endif; ?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/remitos">Remitos</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars((string)($remito['codigo'] ?? '')) ?></li>
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
                            <th class="text-center">Cantidad</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($it['producto'] ?? '')) ?></td>
                                <td><?= htmlspecialchars((string)($it['variedad'] ?? '')) ?: '<span class="text-muted">—</span>' ?></td>
                                <td class="text-center fw-bold"><?= (int)($it['qty'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Estado</span>
                <?php $badge = ['pendiente'=>'warning','completado'=>'success','anulado'=>'secondary']; ?>
                <span class="badge bg-<?= $badge[$remito['estado'] ?? 'pendiente'] ?? 'secondary' ?> fs-6">
                    <?= htmlspecialchars($remito['estado'] ?? 'pendiente') ?>
                </span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <?php if (($remito['estado'] ?? '') === 'pendiente'): ?>
                        <form method="post" action="/admin/remitos/estado" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)($remito['id'] ?? 0) ?>" />
                            <input type="hidden" name="estado" value="completado" />
                            <button class="btn btn-success btn-sm" type="submit"><i class="bi bi-check-lg"></i> Completar</button>
                        </form>
                        <form method="post" action="/admin/remitos/estado" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)($remito['id'] ?? 0) ?>" />
                            <input type="hidden" name="estado" value="anulado" />
                            <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-x-lg"></i> Anular</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Información</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-4">Código</dt>
                    <dd class="col-sm-8"><strong><?= htmlspecialchars((string)($remito['codigo'] ?? '')) ?></strong></dd>
                    <dt class="col-sm-4">Tipo</dt>
                    <dd class="col-sm-8">
                        <?php if (($remito['tipo'] ?? '') === 'entrada'): ?>
                            <span class="badge bg-info text-dark">Entrada</span>
                        <?php else: ?>
                            <span class="badge bg-primary">Salida</span>
                        <?php endif; ?>
                    </dd>
                    <dt class="col-sm-4"><?= ($remito['tipo'] ?? '') === 'entrada' ? 'Proveedor' : 'Cliente' ?></dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($remito['cliente_nombre'] ?: $remito['proveedor_nombre'] ?: '-')) ?></dd>
                    <dt class="col-sm-4">Fecha</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($remito['fecha'] ?? '-')) ?></dd>
                    <?php if ($remito['presupuesto_id']): ?>
                    <dt class="col-sm-4">Presupuesto</dt>
                    <dd class="col-sm-8"><a href="/admin/presupuestos/<?= (int)$remito['presupuesto_id'] ?>">#<?= (int)$remito['presupuesto_id'] ?></a></dd>
                    <?php endif; ?>
                    <dt class="col-sm-4">Creado por</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($remito['created_by_nombre'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Creado</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($remito['created_at'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>

        <?php if (($remito['notas'] ?? '') !== ''): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Notas</div>
            <div class="card-body">
                <p class="small mb-0"><?= nl2br(htmlspecialchars((string)$remito['notas'])) ?></p>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex gap-2">
            <form method="post" action="/admin/remitos/delete" onsubmit="return confirm('Eliminar este remito?')">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                <input type="hidden" name="id" value="<?= (int)($remito['id'] ?? 0) ?>" />
                <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i> Eliminar</button>
            </form>
        </div>
    </div>
</div>
