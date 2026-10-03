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
<?php return; endif;

$mov = $movimientos ?? ['facturas' => 0, 'pedidos' => 0, 'ctacte' => 0, 'puntos' => 0, 'total' => 0];
$erpCols = $erpCols ?? [];
$erpCondIvas = [
    'consumidor_final' => 'Consumidor Final',
    'monotributista' => 'Monotributista',
    'responsable_inscripto' => 'Responsable Inscripto',
    'exento' => 'Exento',
];
$erpCategorias = ['minorista' => 'Minorista', 'mayorista' => 'Mayorista', 'profesional' => 'Profesional'];
?>

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
                <span class="d-flex align-items-center gap-2">
                    <?php if (!empty($customer['disabled_at'])): ?>
                        <span class="badge bg-secondary">Bloqueado</span>
                    <?php else: ?>
                        <span class="badge bg-success">Activo</span>
                    <?php endif; ?>
                    <button class="btn btn-sm btn-outline-primary" type="button" data-bs-toggle="modal" data-bs-target="#editarClienteModal">
                        <i class="bi bi-pencil"></i> Editar
                    </button>
                    <?php if ((int)$mov['total'] === 0): ?>
                        <button class="btn btn-sm btn-outline-danger" type="button" onclick="eliminarCliente()">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    <?php else: ?>
                        <button class="btn btn-sm btn-outline-danger" type="button" disabled title="Tiene movimientos: no se puede eliminar">
                            <i class="bi bi-trash"></i> Eliminar
                        </button>
                    <?php endif; ?>
                </span>
            </div>
            <div class="card-body">
                <div class="alert alert-<?= (int)$mov['total'] > 0 ? 'warning' : 'success' ?> py-2 px-2 small mb-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span>
                        <i class="bi bi-clipboard-check"></i>
                        <strong>Chequeo de movimientos:</strong>
                        <?= (int)$mov['facturas'] ?> factura(s) ·
                        <?= (int)$mov['pedidos'] ?> pedido(s) ·
                        <?= (int)$mov['ctacte'] ?> mov. cta. cte. ·
                        <?= (int)$mov['puntos'] ?> mov. puntos ·
                        <?= (int)($mov['recibos'] ?? 0) ?> recibo(s)
                    </span>
                    <?php if ((int)$mov['total'] > 0): ?>
                        <span class="badge bg-warning text-dark">No se puede eliminar</span>
                    <?php else: ?>
                        <span class="badge bg-success">Sin movimientos: se puede eliminar</span>
                    <?php endif; ?>
                </div>
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

<form method="post" action="/admin/clientes/eliminar" id="eliminarClienteForm" class="d-none">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
    <input type="hidden" name="user_id" value="<?= (int)($customer['id'] ?? 0) ?>" />
</form>

<div class="modal fade" id="editarClienteModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" action="/admin/clientes/editar">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                <input type="hidden" name="user_id" value="<?= (int)($customer['id'] ?? 0) ?>" />
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-pencil"></i> Editar cliente</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 px-2 small">
                        <i class="bi bi-clipboard-check"></i>
                        Chequeo de movimientos previo:
                        <strong><?= (int)$mov['facturas'] ?></strong> factura(s),
                        <strong><?= (int)$mov['pedidos'] ?></strong> pedido(s),
                        <strong><?= (int)$mov['ctacte'] ?></strong> mov. cta. cte.,
                        <strong><?= (int)$mov['puntos'] ?></strong> mov. puntos,
                        <strong><?= (int)($mov['recibos'] ?? 0) ?></strong> recibo(s).
                        Los comprobantes ya emitidos conservan sus propios datos (copia por factura).
                    </div>

                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Datos de la cuenta web</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Nombre *</label>
                            <input type="text" class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)($customer['name'] ?? '')) ?>" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Email *</label>
                            <input type="email" class="form-control form-control-sm" name="email" value="<?= htmlspecialchars((string)($customer['email'] ?? '')) ?>" required />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Teléfono</label>
                            <input type="text" class="form-control form-control-sm" name="phone" value="<?= htmlspecialchars((string)($customer['phone'] ?? '')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Dirección</label>
                            <input type="text" class="form-control form-control-sm" name="address" value="<?= htmlspecialchars((string)($customer['address'] ?? '')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Ciudad</label>
                            <input type="text" class="form-control form-control-sm" name="city" value="<?= htmlspecialchars((string)($customer['city'] ?? '')) ?>" />
                        </div>
                        <div class="col-3">
                            <label class="form-label small">C.P.</label>
                            <input type="text" class="form-control form-control-sm" name="postal_code" value="<?= htmlspecialchars((string)($customer['postal_code'] ?? '')) ?>" />
                        </div>
                        <div class="col-3">
                            <label class="form-label small">Categoría</label>
                            <select class="form-select form-select-sm" name="customer_category">
                                <?php foreach ($customerCategories as $ck => $cl): ?>
                                    <option value="<?= htmlspecialchars($ck) ?>" <?= (($customer['customer_category'] ?? 'none') === $ck) ? 'selected' : '' ?>><?= htmlspecialchars($cl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <?php if ($clienteErp): ?>
                    <hr class="my-2" />
                    <h6 class="fw-bold small text-uppercase text-muted mb-2">Datos de facturación (ERP)</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small">Razón social / Nombre facturado</label>
                            <input type="text" class="form-control form-control-sm" name="erp_razon" value="<?= htmlspecialchars((string)($clienteErp['razon'] ?? '')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">CUIT / DNI</label>
                            <input type="text" class="form-control form-control-sm" name="erp_cuit" maxlength="11" value="<?= htmlspecialchars((string)($clienteErp['cuit'] ?? '')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Dirección</label>
                            <input type="text" class="form-control form-control-sm" name="erp_direc" value="<?= htmlspecialchars((string)($clienteErp['direc'] ?? '')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Localidad</label>
                            <input type="text" class="form-control form-control-sm" name="erp_localidad" value="<?= htmlspecialchars((string)($clienteErp['Localidad'] ?? '')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Teléfono</label>
                            <input type="text" class="form-control form-control-sm" name="erp_tele" value="<?= htmlspecialchars((string)($clienteErp['tele'] ?? '')) ?>" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small">Email</label>
                            <input type="text" class="form-control form-control-sm" name="erp_mail" value="<?= htmlspecialchars((string)($clienteErp['mail'] ?? '')) ?>" />
                        </div>
                        <?php if (!empty($erpCols['condicion_iva'])): ?>
                        <div class="col-6">
                            <label class="form-label small">Condición frente al IVA</label>
                            <select class="form-select form-select-sm" name="erp_condicion_iva">
                                <?php foreach ($erpCondIvas as $ck => $cl): ?>
                                    <option value="<?= htmlspecialchars($ck) ?>" <?= (($clienteErp['condicion_iva'] ?? 'consumidor_final') === $ck) ? 'selected' : '' ?>><?= htmlspecialchars($cl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($erpCols['categoria'])): ?>
                        <div class="col-6">
                            <label class="form-label small">Categoría comercial</label>
                            <select class="form-select form-select-sm" name="erp_categoria">
                                <?php foreach ($erpCategorias as $ck => $cl): ?>
                                    <option value="<?= htmlspecialchars($ck) ?>" <?= (($clienteErp['categoria'] ?? 'minorista') === $ck) ? 'selected' : '' ?>><?= htmlspecialchars($cl) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if (!empty($erpCols['precio_mayorista'])): ?>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="erp_precio_mayorista" value="1" id="erpPrecioMayorista" <?= !empty($clienteErp['precio_mayorista']) ? 'checked' : '' ?> />
                                <label class="form-check-label small" for="erpPrecioMayorista">Aplica precios mayoristas</label>
                            </div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($erpCols['especialidad'])): ?>
                        <div class="col-12">
                            <label class="form-label small">Especialidad</label>
                            <input type="text" class="form-control form-control-sm" name="erp_especialidad" value="<?= htmlspecialchars((string)($clienteErp['especialidad'] ?? '')) ?>" />
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-accent"><i class="bi bi-check-lg"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function eliminarCliente() {
    const ok = confirm('¿Eliminar este usuario web?\n\nNo tiene movimientos asociados. Esta acción no se puede deshacer.');
    if (ok) document.getElementById('eliminarClienteForm').submit();
}
</script>
