<?php
$compra = $compra ?? null;
$items = $items ?? [];
if (!$compra) { echo '<div class="alert alert-warning">Factura no encontrada.</div>'; return; }

$mon = static fn ($v) => number_format((float)$v, 2, ',', '.');
$origen = ['manual' => 'Manual', 'excel' => 'Excel', 'qr' => 'QR'];
$badge = ['pendiente' => 'warning', 'completa' => 'success'];
$estado = (string)($compra['estado'] ?? 'pendiente');
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/compras">Facturas de compra</a></li>
        <li class="breadcrumb-item active">#<?= (int)$compra['id'] ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>
                    <?= htmlspecialchars((string)($compra['tipo'] ?? '-')) ?>
                    <span class="text-muted"><?= htmlspecialchars((string)($compra['punto_venta'] ?? '') . '-' . $compra['numero_desde']) ?></span>
                </span>
                <span>
                    <span class="badge bg-<?= $badge[$estado] ?? 'secondary' ?> fs-6"><?= htmlspecialchars($estado) ?></span>
                    <span class="badge bg-light text-dark"><?= htmlspecialchars((string)($origen[$compra['origen'] ?? ''] ?? $compra['origen'] ?? '')) ?></span>
                </span>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-3">Fecha</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)($compra['fecha'] ?? '-')) ?></dd>
                    <dt class="col-sm-3">Proveedor</dt>
                    <dd class="col-sm-9 fw-bold"><?= htmlspecialchars((string)($compra['razon_proveedor'] ?? '-')) ?></dd>
                    <dt class="col-sm-3">CUIT</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)($compra['cuit_proveedor'] ?? '-')) ?></dd>
                    <dt class="col-sm-3">Cód. autorización</dt>
                    <dd class="col-sm-9"><?= $compra['cod_autorizacion'] ? htmlspecialchars((string)$compra['cod_autorizacion']) : '<span class="text-muted">—</span>' ?></dd>
                    <dt class="col-sm-3">Moneda / cambio</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)($compra['moneda'] ?? 'PES')) ?> / <?= $mon($compra['tipo_cambio'] ?? 1) ?></dd>
                    <dt class="col-sm-3">Cuenta contable</dt>
                    <dd class="col-sm-9"><?= !empty($compra['cuenta_nombre']) ? htmlspecialchars((string)($compra['cuenta_grupo'] ?? '') . ' › ' . $compra['cuenta_nombre']) : '<span class="text-muted">Sin asignar</span>' ?></dd>
                    <dt class="col-sm-3">Depósito</dt>
                    <dd class="col-sm-9"><?= $compra['deposito_nombre'] ? htmlspecialchars((string)$compra['deposito_nombre']) : '<span class="text-muted">—</span>' ?></dd>
                    <dt class="col-sm-3">Creado por</dt>
                    <dd class="col-sm-9 text-muted"><?= htmlspecialchars((string)($compra['created_by_nombre'] ?? '-')) ?></dd>
                    <?php if (!empty($compra['observaciones'])): ?>
                        <dt class="col-sm-3">Observaciones</dt>
                        <dd class="col-sm-9"><?= nl2br(htmlspecialchars((string)$compra['observaciones'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Ítems</span>
                <span class="badge bg-secondary"><?= count($items) ?> items</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Variedad</th>
                            <th class="text-center">Cant.</th>
                            <th class="text-end">Costo unit.</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$items): ?>
                            <tr><td colspan="5" class="text-muted text-center">Sin ítems cargados</td></tr>
                        <?php else: ?>
                            <?php foreach ($items as $it): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($it['product_name'] ?? '-')) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($it['nomgusto'] ?? '-')) ?></td>
                                    <td class="text-center"><?= (float)($it['qty'] ?? 0) ?></td>
                                    <td class="text-end">$<?= $mon($it['unit_cost']) ?></td>
                                    <td class="text-end fw-bold">$<?= $mon($it['line_total']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">Neto gravado</td>
                            <td class="text-end">$<?= $mon($compra['imp_neto_gravado'] ?? 0) ?></td>
                        </tr>
                        <tr>
                            <td colspan="4" class="text-end">IVA</td>
                            <td class="text-end">$<?= $mon($compra['imp_iva'] ?? 0) ?></td>
                        </tr>
                        <tr class="fw-bold">
                            <td colspan="4" class="text-end">Total</td>
                            <td class="text-end fs-6">$<?= $mon($compra['imp_total'] ?? 0) ?></td>
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
                <div class="d-grid gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="/admin/compras/<?= (int)$compra['id'] ?>/editar"><i class="bi bi-pencil"></i> Editar</a>
                    <form method="post" action="/admin/compras/delete" onsubmit="return confirm('¿Eliminar esta factura de compra?')">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                        <input type="hidden" name="id" value="<?= (int)$compra['id'] ?>" />
                        <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-trash"></i> Eliminar</button>
                    </form>
                </div>
                <hr />
                <p class="text-muted small mb-0">
                    <i class="bi bi-info-circle"></i> La factura está <?= $estado === 'completa' ? '<strong>completa</strong>: sus ítems ya actualizaron stock y precios.' : '<strong>pendiente</strong>: cargá ítems desde editar para aplicar stock y precios.' ?>
                </p>
            </div>
        </div>
    </div>
</div>
