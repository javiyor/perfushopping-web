<?php
use Perfushopping\Web\Support\Format;

$movimientos = $movimientos ?? [];
$comprobantes = $comprobantes ?? [];
$proveedorNombre = (string)($proveedorNombre ?? '');
$proveedorId = $proveedorId ?? null;
$saldo = (int)($saldo ?? 0);
$q = (string)($q ?? '');
$mon = static fn ($v) => number_format((float)$v, 2, ',', '.');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($proveedorNombre ?: 'Proveedor') ?></h4>
        <p class="text-muted small">Movimientos de cuenta corriente</p>
    </div>
    <div>
        <span class="fw-bold fs-5 me-3 <?= $saldo > 0 ? 'text-danger' : 'text-success' ?>">Saldo: <?= Format::moneyFromCents($saldo) ?></span>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/proveedores/ctacte">Volver</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/proveedores/ctacte/<?= $proveedorId ?>" class="row g-2">
            <div class="col-lg-8">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar en movimientos..." />
            </div>
            <div class="col-lg-4">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($comprobantes): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Comprobantes en cuenta corriente</h5>
        <p class="text-muted small mb-0">Comprobantes generados por facturas de compra con plazos de pago</p>
        <button class="btn btn-sm btn-success" type="button" onclick="cargarPagoComprobantes()" title="Pagar los comprobantes seleccionados (el monto se puede ajustar en la orden de pago)"><i class="bi bi-cash-stack"></i> Cargar pago</button>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th style="width:30px"></th>
                    <th>Comprobante</th>
                    <th>Fecha</th>
                    <th>Vto.</th>
                    <th class="text-end">Total</th>
                    <th>Cuotas</th>
                    <th>Estado</th>
                    <th class="text-end">Pendiente</th>
                    <th style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($comprobantes as $c): ?>
                    <tr>
                        <td>
                            <?php if ($c['pendiente'] > 0): ?>
                                <input type="checkbox" name="compra_ids[]" value="<?= (int)$c['id'] ?>" data-pendiente="<?= (float)$c['pendiente'] ?>" title="Seleccionar para pagar" />
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?= htmlspecialchars((string)($c['tipo'] ?? '')) ?></strong>
                            <span class="text-muted"><?= htmlspecialchars((string)($c['punto_venta'] ?? '') . '-' . (string)($c['numero_desde'] ?? '')) ?></span>
                            <div class="small text-muted">
                                <?php if ($c['cronograma']): ?>
                                    <?php foreach ($c['cronograma'] as $cu): ?>
                                        <div>Cuota <?= (int)$cu['cuota'] ?><?= $cu['dias'] > 0 ? ' a ' . (int)$cu['dias'] . 'd' : ' contado' ?>: <?= $cu['fecha'] ? date('d/m/Y', strtotime((string)$cu['fecha'])) : '—' ?> — $<?= $mon($cu['monto']) ?></div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    Sin plazo
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="small"><?= $c['fecha'] ? date('d/m/Y', strtotime((string)$c['fecha'])) : '—' ?></td>
                        <td class="small <?= (!empty($c['vencimiento']) && ($c['pendiente'] ?? 0) > 0 && $c['vencimiento'] < date('Y-m-d')) ? 'text-danger fw-bold' : '' ?>"><?= !empty($c['vencimiento']) ? date('d/m/Y', strtotime((string)$c['vencimiento'])) : '—' ?></td>
                        <td class="text-end">$<?= $mon($c['imp_total']) ?></td>
                        <td class="text-center"><?= count($c['cronograma']) ?: 1 ?></td>
                        <td>
                            <?php $est = ['Pagada' => 'success', 'Parcial' => 'warning', 'Pendiente' => 'danger']; ?>
                            <span class="badge bg-<?= $est[$c['estado']] ?? 'secondary' ?>"><?= htmlspecialchars((string)$c['estado']) ?></span>
                        </td>
                        <td class="text-end <?= $c['pendiente'] > 0 ? 'text-danger' : 'text-success' ?>">$<?= $mon($c['pendiente']) ?></td>
                        <td class="text-end">
                            <?php if ($c['pendiente'] > 0): ?>
                                <a class="btn btn-sm btn-outline-success" title="Cargar pago a cuenta (monto editable)"
                                   href="/admin/ordenes-pago/nueva?proveedor_id=<?= (int)$proveedorId ?>&proveedor_nombre=<?= urlencode($proveedorNombre) ?>&monto=<?= $c['pendiente'] ?>&compra_ids=<?= (int)$c['id'] ?>"><i class="bi bi-cash-stack"></i></a>
                            <?php endif; ?>
                            <a class="btn btn-sm btn-outline-secondary" title="Ver factura" href="/admin/compras/<?= (int)$c['id'] ?>"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Movimientos</span>
        <span class="badge bg-secondary"><?= count($movimientos) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Origen</th>
                    <th class="text-end">Monto</th>
                    <th class="text-end">Saldo después</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$movimientos): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin movimientos.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td class="small text-muted"><?= (int)($m['id'] ?? 0) ?></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime((string)($m['created_at'] ?? ''))) ?></td>
                            <td>
                                <?php if (($m['tipo'] ?? '') === 'debito'): ?>
                                    <span class="badge bg-danger">Débito</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Crédito</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($m['concepto'] ?? '')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($m['origen'] ?? '') . ($m['origen_id'] ? ' #' . $m['origen_id'] : '')) ?></td>
                            <td class="text-end <?= ($m['tipo'] ?? '') === 'debito' ? 'text-danger' : 'text-success' ?>">
                                <?= Format::moneyFromCents((int)($m['monto_cents'] ?? 0)) ?>
                            </td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($m['saldo_after_cents'] ?? 0)) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($m['created_by_nombre'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function cargarPagoComprobantes() {
    var total = 0;
    var n = 0;
    document.querySelectorAll('input[name="compra_ids[]"]:checked').forEach(function(cb) {
        n++;
        total += parseFloat(cb.getAttribute('data-pendiente') || '0');
    });
    if (!n) {
        alert('Seleccioná al menos un comprobante para pagar.');
        return;
    }
    var url = '/admin/ordenes-pago/nueva?proveedor_id=<?= (int)$proveedorId ?>&proveedor_nombre=<?= urlencode($proveedorNombre) ?>&monto=' + total.toFixed(2);
    window.location.href = url;
}
</script>
