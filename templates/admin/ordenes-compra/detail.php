<?php
use Perfushopping\Web\Support\Format;

$orden = $orden ?? null;
$items = $items ?? [];
if (!$orden) { echo '<div class="alert alert-warning">Orden no encontrada.</div>'; return; }

$badgeMap = ['pendiente' => 'warning', 'aprobada' => 'success', 'recibida' => 'primary', 'anulada' => 'secondary'];
$estados = ['pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'recibida' => 'Recibida', 'anulada' => 'Anulada'];
$noTransition = ['anulada', 'recibida'];
$canChange = !in_array($orden['estado'], $noTransition, true);

$adminUsers = (new \Perfushopping\Web\Repo\OrdenCompraRepo())->listAdminUsers();
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
                    <dt class="col-sm-3">Recepción real</dt>
                    <dd class="col-sm-9"><?= $orden['fecha_recepcion'] ? htmlspecialchars((string)$orden['fecha_recepcion']) : '<span class="text-muted">—</span>' ?></dd>
                    <dt class="col-sm-3">Bultos recibidos</dt>
                    <dd class="col-sm-9"><?= $orden['bultos_recibidos'] !== null ? (int)$orden['bultos_recibidos'] : '<span class="text-muted">—</span>' ?></dd>
                    <dt class="col-sm-3">Controlado por</dt>
                    <dd class="col-sm-9"><?= $orden['controlado_por_nombre'] ? htmlspecialchars((string)$orden['controlado_por_nombre']) : '<span class="text-muted">—</span>' ?></dd>
                    <dt class="col-sm-3">Total mercadería</dt>
                    <dd class="col-sm-9 fw-bold fs-5"><?= Format::moneyFromCents((int)($orden['total_cents'] ?? 0)) ?></dd>
                    <?php if ((int)($orden['valor_declarado_cents'] ?? 0) > 0): ?>
                    <dt class="col-sm-3">Valor declarado</dt>
                    <dd class="col-sm-9"><?= Format::moneyFromCents((int)$orden['valor_declarado_cents']) ?></dd>
                    <?php endif; ?>
                    <?php if ((int)($orden['flete_cents'] ?? 0) > 0): ?>
                    <dt class="col-sm-3">Flete</dt>
                    <dd class="col-sm-9">
                        <?= Format::moneyFromCents((int)$orden['flete_cents']) ?>
                        <?php if ((int)$orden['flete_pagado']): ?>
                            <span class="badge bg-success">Pagado</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">En cta cte</span>
                        <?php endif; ?>
                        <?php if ($orden['flete_comprobante']): ?>
                            <a class="btn btn-sm btn-outline-secondary ms-2" href="/admin/ordenes-compra/descargar-comprobante/<?= (int)$orden['id'] ?>"><i class="bi bi-paperclip"></i> Comprobante</a>
                        <?php endif; ?>
                    </dd>
                    <?php endif; ?>
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
        <?php if ($canChange && $orden['estado'] !== 'pendiente'): ?>
        <div class="card shadow-sm mb-3 border-primary">
            <div class="card-header bg-primary text-white fw-semibold"><i class="bi bi-truck"></i> Recepción</div>
            <div class="card-body">
                <form method="post" action="/admin/ordenes-compra/guardar-recepcion" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="id" value="<?= (int)$orden['id'] ?>" />

                    <div class="mb-2">
                        <label class="form-label small">Fecha de recepción</label>
                        <input class="form-control form-control-sm" name="fecha_recepcion" type="date" value="<?= date('Y-m-d') ?>" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Bultos recibidos</label>
                        <input class="form-control form-control-sm" name="bultos_recibidos" type="number" min="0" placeholder="Cantidad de bultos" />
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Controló</label>
                        <select class="form-select form-select-sm" name="controlado_por">
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($adminUsers as $u): ?>
                                <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars((string)($u['nombre'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Valor declarado ($)</label>
                        <input class="form-control form-control-sm" name="valor_declarado_cents" type="number" min="0" step="1" value="0" placeholder="En centavos" />
                    </div>

                    <hr class="my-2" />
                    <h6 class="fw-semibold small mb-2">Flete</h6>
                    <div class="mb-2">
                        <label class="form-label small">Costo del flete ($)</label>
                        <input class="form-control form-control-sm" name="flete_cents" type="number" min="0" step="1" value="0" placeholder="En centavos" />
                    </div>
                    <div class="mb-2 form-check">
                        <input class="form-check-input" type="checkbox" name="flete_pagado" value="1" id="fletePagado" />
                        <label class="form-check-label small" for="fletePagado">Pagado (registrar como gasto en caja)</label>
                        <div class="form-text smaller">Si no se marca, se registra como deuda en cta cte del proveedor.</div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Comprobante de flete</label>
                        <input class="form-control form-control-sm" type="file" name="flete_comprobante" accept=".pdf,.jpg,.jpeg,.png,.webp" />
                    </div>

                    <button class="btn btn-primary w-100 mt-2" type="submit"><i class="bi bi-check-lg"></i> Confirmar recepción</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Acciones</div>
            <div class="card-body">
                <?php if ($canChange): ?>
                <div class="d-grid gap-2">
                    <?php if ($orden['estado'] === 'pendiente'): ?>
                    <form method="post" action="/admin/ordenes-compra/estado">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                        <input type="hidden" name="id" value="<?= (int)$orden['id'] ?>" />
                        <input type="hidden" name="estado" value="aprobada" />
                        <button class="btn btn-success btn-sm w-100" type="submit"><i class="bi bi-check-circle"></i> Marcar como Aprobada</button>
                    </form>
                    <?php endif; ?>
                    <?php foreach (['recibida', 'anulada'] as $est): ?>
                        <?php if ($est === $orden['estado']) continue; ?>
                        <form method="post" action="/admin/ordenes-compra/estado">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$orden['id'] ?>" />
                            <input type="hidden" name="estado" value="<?= $est ?>" />
                            <button class="btn btn-<?= $est === 'recibida' ? 'primary' : 'danger' ?> btn-sm w-100" type="submit">
                                <i class="bi bi-<?= $est === 'recibida' ? 'truck' : 'x-circle' ?>"></i>
                                Marcar como <?= $estados[$est] ?> (sin recepción)
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
                    <li class="text-muted mt-2"><i class="bi bi-info-circle"></i> Al recibir podés registrar fecha, bultos, control, valor declarado y flete.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
