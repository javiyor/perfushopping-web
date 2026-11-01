<?php
use Perfushopping\Web\Support\Format;

$solicitudes = $solicitudes ?? [];
$csrfToken = (string)($csrf ?? '');
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/facturas">Facturación</a></li>
        <li class="breadcrumb-item active">Autorizaciones pendientes</li>
    </ol>
</nav>

    <div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Autorizaciones de ediciones de facturas</h4>
        <p class="text-muted small mb-0">Las solicitudes pendientes no cambian la factura. Las que requieren revisión manual se muestran también aquí.</p>
    </div>
    <span class="badge bg-danger fs-6"><?= count($solicitudes) ?> pendiente(s)</span>
</div>

<?php if (!$solicitudes): ?>
    <div class="alert alert-info">No hay ediciones de facturas pendientes de autorización.</div>
<?php else: ?>
    <?php foreach ($solicitudes as $solicitud): ?>
        <?php
            $propuesta = is_array($solicitud['propuesta'] ?? null) ? $solicitud['propuesta'] : [];
            $original = is_array($solicitud['original'] ?? null) ? $solicitud['original'] : [];
            $facturaAnterior = is_array($original['factura'] ?? null) ? $original['factura'] : [];
            $clientePropuesto = is_array($propuesta['cliente'] ?? null) ? $propuesta['cliente'] : [];
            $itemsPropuestos = is_array($propuesta['items'] ?? null) ? $propuesta['items'] : [];
            $pagosPropuestos = is_array($propuesta['pagos'] ?? null) ? $propuesta['pagos'] : [];
            $id = (int)($solicitud['id'] ?? 0);
        ?>
        <section class="card shadow-sm mb-3" id="solicitud-edicion-<?= $id ?>">
            <?php $status = (string)($solicitud['status'] ?? 'pendiente'); $statusClass = $status === 'error' ? 'danger' : ($status === 'procesando' ? 'info' : 'warning text-dark'); ?>
            <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <strong>Solicitud #<?= $id ?> · <?= htmlspecialchars((string)($solicitud['factura_codigo'] ?? 'Factura')) ?></strong>
                    <div class="small text-muted">Solicitada por <?= htmlspecialchars((string)($solicitud['requested_by_nombre'] ?? 'Admin')) ?> · <?= htmlspecialchars((string)($solicitud['created_at'] ?? '')) ?></div>
                </div>
                <div class="d-flex gap-2 align-items-center">
                    <span class="badge bg-<?= $statusClass ?>"><?= htmlspecialchars($status) ?></span>
                    <a class="btn btn-sm btn-outline-secondary" href="/admin/facturas/<?= (int)($solicitud['factura_id'] ?? 0) ?>">Ver factura actual</a>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-warning py-2 small">
                    <strong>Motivo:</strong> <?= nl2br(htmlspecialchars((string)($solicitud['motivo'] ?? ''))) ?>
                </div>
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Datos actuales</div>
                            <div><strong><?= htmlspecialchars((string)($facturaAnterior['cliente_nombre'] ?? $solicitud['cliente_nombre'] ?? '')) ?></strong></div>
                            <div class="small">Tipo: <?= htmlspecialchars((string)($facturaAnterior['tipo_comprobante'] ?? '')) ?> · Fecha: <?= htmlspecialchars((string)($facturaAnterior['fecha'] ?? '')) ?></div>
                            <div class="fw-semibold">Total: <?= htmlspecialchars(Format::moneyFromCents((int)($facturaAnterior['total_cents'] ?? 0))) ?></div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="border rounded p-2 h-100">
                            <div class="small text-muted">Propuesta solicitada</div>
                            <div><strong><?= htmlspecialchars((string)($clientePropuesto['nombre'] ?? 'Consumidor Final')) ?></strong></div>
                            <div class="small">Tipo: <?= htmlspecialchars((string)($propuesta['tipo_comprobante'] ?? '')) ?> · Fecha: <?= htmlspecialchars((string)($propuesta['fecha'] ?? '')) ?></div>
                            <div class="small">CUIT: <?= htmlspecialchars((string)($clientePropuesto['cuit'] ?? '—')) ?> · Descuento: <?= htmlspecialchars(Format::moneyFromCents((int)($propuesta['descuento_cents'] ?? 0))) ?></div>
                            <div class="fw-semibold">Ítems propuestos: <?= count($itemsPropuestos) ?> · Pagos propuestos: <?= count($pagosPropuestos) ?></div>
                        </div>
                    </div>
                </div>

                <details class="mb-3">
                    <summary class="small fw-semibold">Revisar productos y pagos propuestos</summary>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered mb-2">
                            <thead><tr><th>Producto</th><th>Variedad</th><th class="text-end">Cantidad</th><th class="text-end">Precio unitario</th><th class="text-end">IVA %</th></tr></thead>
                            <tbody>
                                <?php foreach ($itemsPropuestos as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($item['producto'] ?? '')) ?></td>
                                        <td><?= htmlspecialchars((string)($item['variedad'] ?? '')) ?></td>
                                        <td class="text-end"><?= (int)($item['qty'] ?? 0) ?></td>
                                        <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($item['unit_price_cents'] ?? 0))) ?></td>
                                        <td class="text-end"><?= htmlspecialchars((string)($item['iva_rate'] ?? 0)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0">
                            <thead><tr><th>Forma de pago</th><th class="text-end">Importe</th><th>Referencia</th></tr></thead>
                            <tbody>
                                <?php foreach ($pagosPropuestos as $pago): ?>
                                    <?php $cheque = is_array($pago['cheque'] ?? null) ? $pago['cheque'] : []; ?>
                                    <tr>
                                        <td><?= htmlspecialchars((string)($pago['forma_pago'] ?? '')) ?></td>
                                        <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($pago['monto_cents'] ?? 0))) ?></td>
                                        <td class="small">
                                            <?= htmlspecialchars((string)($pago['cupon_numero'] ?? '')) ?>
                                            <?= htmlspecialchars((string)($cheque['numero'] ?? '')) ?>
                                            <?= htmlspecialchars((string)($pago['moneda'] ?? '')) ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </details>

                <?php if ($status === 'pendiente' || $status === 'error'): ?>
                    <div class="d-flex flex-wrap gap-2 align-items-center">
                        <?php if ($status === 'pendiente'): ?>
                        <button class="btn btn-accent btn-sm" type="button" onclick="aprobarEdicionFactura(<?= $id ?>)">
                            <i class="bi bi-check-lg"></i> Aprobar y aplicar
                        </button>
                        <?php endif; ?>
                        <form method="post" action="/admin/facturas/ediciones/rechazar" class="d-flex gap-2" onsubmit="return confirm('¿<?= $status === 'error' ? 'Cerrar la revisión manual' : 'Rechazar esta edición' ?>?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                            <input type="hidden" name="solicitud_id" value="<?= $id ?>" />
                            <input class="form-control form-control-sm" name="decision_note" maxlength="500" placeholder="Motivo de rechazo (opcional)" />
                            <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-x-lg"></i> <?= $status === 'error' ? 'Cerrar revisión' : 'Rechazar' ?></button>
                        </form>
                    </div>
                <?php elseif ($status === 'procesando'): ?>
                    <div class="alert alert-info small mb-0">Esta solicitud se está aplicando. Actualizá la página en unos instantes.</div>
                <?php else: ?>
                    <div class="alert alert-danger small mb-0">
                        La solicitud requiere revisión manual y no puede volver a aplicarse automáticamente. <?= htmlspecialchars((string)($solicitud['decision_note'] ?? '')) ?>
                    </div>
                <?php endif; ?>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>

<script>
async function aprobarEdicionFactura(id){
    if (!confirm('¿Aprobar y aplicar esta edición de factura?')) return;
    try {
        const response = await fetch('/admin/facturas/actualizar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({_csrf: <?= json_encode($csrfToken) ?>, aprobacion_solicitud_id: id})
        });
        const result = await response.json();
        if (!response.ok || !result.ok) throw new Error(result.error || 'No se pudo aprobar la solicitud.');
        alert('Edición aprobada y aplicada a la factura ' + (result.codigo || '') + '.');
        window.location.reload();
    } catch (error) {
        alert(error.message || 'No se pudo aprobar la solicitud.');
        window.location.reload();
    }
}
</script>
