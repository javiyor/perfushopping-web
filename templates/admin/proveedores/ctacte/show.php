<?php
use Perfushopping\Web\Support\Format;

$movimientos = $movimientos ?? [];
$comprobantes = $comprobantes ?? [];
$proveedorNombre = (string)($proveedorNombre ?? '');
$proveedorId = $proveedorId ?? null;
$saldo = (int)($saldo ?? 0);
$q = (string)($q ?? '');
$filtro = (string)($filtro ?? 'pendientes');
$mon = static fn ($v) => number_format((float)$v, 2, ',', '.');
$filtroQs = $q !== '' ? '&q=' . urlencode($q) : '';
?>
<style>
.ctacte-compact .table { font-size: 0.62rem; }
.ctacte-compact .table > :not(caption) > * > * { padding: 0.06rem 0.2rem; line-height: 1.04; }
.ctacte-compact .ctacte-data-table th,
.ctacte-compact .ctacte-data-table td { border-right: 1px solid #e6e8eb; border-bottom-color: #eceef0; }
.ctacte-compact .ctacte-data-table tr > :last-child { border-right: 0; }
.ctacte-compact .ctacte-data-table thead th { background: #f8f9fa; white-space: nowrap; }
.ctacte-compact .table td { vertical-align: middle; }
.ctacte-compact .table .small { font-size: 0.54rem; line-height: 1; }
.ctacte-compact .ctacte-installment { white-space: nowrap; margin-right: 0.14rem; }
.ctacte-compact .table .badge { font-size: 0.54rem; padding: 0.1em 0.28em; }
.ctacte-compact .table .btn-sm { padding: 0.02rem 0.18rem; font-size: 0.58rem; }
.ctacte-compact .table input[type="checkbox"] { width: 0.9em; height: 0.9em; }
.ctacte-compact .card-header h5 { font-size: 0.95rem; }
.ctacte-compact .ctacte-vencido-resumen { font-size: 0.78rem; }
</style>
<div class="ctacte-compact">
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

<div class="card shadow-sm mb-2 ctacte-vencido-resumen">
    <div class="card-body py-1 px-2 d-flex flex-wrap justify-content-between gap-2">
        <span><span class="text-muted">Vencido bruto:</span> <strong class="text-danger"><?= Format::moneyFromCents($vencidoBrutoCents) ?></strong></span>
        <span><span class="text-muted">Crédito pendiente de imputar:</span> <strong class="text-success">−<?= Format::moneyFromCents($creditoPendienteCents) ?></strong></span>
        <span><span class="text-muted">Vencido neto:</span> <strong class="<?= $vencidoNetoCents > 0 ? 'text-danger' : 'text-success' ?>"><?= Format::moneyFromCents($vencidoNetoCents) ?></strong></span>
    </div>
</div>

<div class="card shadow-sm mb-2">
    <div class="card-body py-2">
        <form method="post" action="/admin/proveedores/ctacte/ajuste/guardar" class="row g-2 align-items-end">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="proveedor_id" value="<?= (int)$proveedorId ?>" />
            <div class="col-auto">
                <label class="form-label small mb-1">Ajuste</label>
                <select class="form-select form-select-sm" name="tipo" required>
                    <option value="debito">Débito (+ deuda)</option>
                    <option value="credito">Crédito (− deuda)</option>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-1">Importe</label>
                <input class="form-control form-control-sm" type="number" name="monto" min="0.01" step="0.01" inputmode="decimal" placeholder="0,00" required />
            </div>
            <div class="col">
                <label class="form-label small mb-1">Concepto / motivo</label>
                <input class="form-control form-control-sm" type="text" name="concepto" maxlength="255" required />
            </div>
            <div class="col-auto">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-sliders"></i> Aplicar ajuste</button>
            </div>
        </form>
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
        <div class="d-flex gap-2 align-items-center">
            <a class="btn btn-sm <?= $filtro === 'pendientes' ? 'btn-accent' : 'btn-outline-secondary' ?>" href="/admin/proveedores/ctacte/<?= (int)$proveedorId ?>?filtro=pendientes<?= $filtroQs ?>">Pendientes</a>
            <a class="btn btn-sm <?= $filtro === 'todos' ? 'btn-accent' : 'btn-outline-secondary' ?>" href="/admin/proveedores/ctacte/<?= (int)$proveedorId ?>?filtro=todos<?= $filtroQs ?>">Todos</a>
            <button class="btn btn-sm btn-outline-primary" type="button" onclick="aplicarCreditosSeleccionados()" title="Imputar créditos a los débitos seleccionados"><i class="bi bi-arrow-left-right"></i> Aplicar créditos</button>
            <button class="btn btn-sm btn-success" type="button" onclick="cargarPagoComprobantes()" title="Pagar los documentos seleccionados (el monto se puede ajustar en la orden de pago)"><i class="bi bi-cash-stack"></i> Cargar pago</button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0 ctacte-data-table">
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
                    <th class="text-end">Vencido</th>
                    <th class="text-end">Saldo acum.</th>
                    <th style="width:110px"></th>
                </tr>
            </thead>
            <tbody>
                <?php $acumPendiente = 0; foreach ($comprobantes as $c): $esCredito = !empty($c['es_credito']); $acumPendiente += ($esCredito ? -1 : 1) * (float)($c['pendiente'] ?? 0); ?>
                    <tr>
                        <td>
                            <?php if ($c['pendiente'] > 0 && (int)($c['movimiento_id'] ?? 0) > 0): ?>
                                <?php if ($esCredito): ?>
                                    <input class="ctacte-doc-check" type="checkbox" name="credito_movimiento_ids[]" value="<?= (int)$c['movimiento_id'] ?>" data-movimiento-id="<?= (int)$c['movimiento_id'] ?>" data-es-credito="1" title="Seleccionar crédito para imputar" />
                                <?php else: ?>
                                    <input class="ctacte-doc-check" type="checkbox" name="<?= !empty($c['es_ajuste']) ? 'ajuste_ids[]' : 'compra_ids[]' ?>" value="<?= (int)$c['id'] ?>" data-movimiento-id="<?= (int)$c['movimiento_id'] ?>" data-es-credito="0" data-pendiente="<?= (float)$c['pendiente'] ?>" title="Seleccionar débito" />
                                <?php endif; ?>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($c['es_ajuste'])): ?>
                                <strong><?= $esCredito ? 'Ajuste crédito' : 'Ajuste débito' ?></strong> <span class="text-muted">#<?= (int)$c['id'] ?></span>
                                <?php if (trim((string)($c['concepto'] ?? '')) !== ''): ?><div class="small text-muted"><?= htmlspecialchars((string)$c['concepto']) ?></div><?php endif; ?>
                            <?php else: ?>
                                <strong><?= htmlspecialchars((string)($c['tipo'] ?? '')) ?></strong>
                                <span class="text-muted"><?= htmlspecialchars((string)($c['punto_venta'] ?? '') . '-' . (string)($c['numero_desde'] ?? '')) ?></span>
                            <?php endif; ?>
                            <div class="small text-muted">
                                <?php if ($c['cronograma']): ?>
                                    <?php foreach ($c['cronograma'] as $cu): ?>
                                        <span class="ctacte-installment">C<?= (int)$cu['cuota'] ?><?= $cu['dias'] > 0 ? ' +' . (int)$cu['dias'] . 'd' : '' ?> <?= $cu['fecha'] ? date('d/m/y', strtotime((string)$cu['fecha'])) : '—' ?> $<?= $mon($cu['monto']) ?></span>
                                    <?php endforeach; ?>
                                <?php elseif ($esCredito): ?>
                                    Crédito sin imputar
                                <?php elseif (!empty($c['es_ajuste'])): ?>
                                    Ajuste sin vencimiento
                                <?php else: ?>
                                    Sin plazo
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="small"><?= $c['fecha'] ? date('d/m/Y', strtotime((string)$c['fecha'])) : '—' ?></td>
                        <td class="small <?= (!empty($c['vencimiento']) && ($c['pendiente'] ?? 0) > 0 && $c['vencimiento'] < date('Y-m-d')) ? 'text-danger fw-bold' : '' ?>"><?= !empty($c['vencimiento']) ? date('d/m/Y', strtotime((string)$c['vencimiento'])) : '—' ?></td>
                        <td class="text-end <?= $esCredito ? 'text-success' : '' ?>"><?= $esCredito ? '−' : '' ?>$<?= $mon($c['imp_total']) ?></td>
                        <td class="text-center"><?= ($esCredito || !empty($c['es_ajuste'])) ? '—' : (count($c['cronograma']) ?: 1) ?></td>
                        <td>
                            <?php $est = ['Pagada' => 'success', 'Parcial' => 'warning', 'Pendiente' => 'danger', 'Crédito pendiente' => 'info']; ?>
                            <span class="badge bg-<?= $est[$c['estado']] ?? 'secondary' ?>"><?= htmlspecialchars((string)$c['estado']) ?></span>
                        </td>
                        <td class="text-end <?= $esCredito ? 'text-success' : ($c['pendiente'] > 0 ? 'text-danger' : 'text-success') ?>"><?= $esCredito ? '−' : '' ?>$<?= $mon($c['pendiente']) ?></td>
                        <td class="text-end <?= ($c['vencido'] ?? 0) > 0 ? 'text-danger fw-bold' : 'text-muted' ?>">$<?= $mon($c['vencido'] ?? 0) ?></td>
                        <td class="text-end fw-bold <?= $acumPendiente > 0 ? 'text-danger' : 'text-success' ?>"><?= $acumPendiente < 0 ? '−' : '' ?>$<?= $mon(abs($acumPendiente)) ?></td>
                        <td class="text-end">
                            <?php if (!$esCredito && $c['pendiente'] > 0): ?>
                                <?php if (!empty($c['es_ajuste'])): ?>
                                <a class="btn btn-sm btn-outline-success" title="Cargar pago al ajuste"
                                   href="/admin/ordenes-pago/nueva?proveedor_id=<?= (int)$proveedorId ?>&proveedor_nombre=<?= urlencode($proveedorNombre) ?>&monto=<?= $c['pendiente'] ?>&ajuste_ids=<?= (int)$c['id'] ?>"><i class="bi bi-cash-stack"></i></a>
                                <?php else: ?>
                                <a class="btn btn-sm btn-outline-success" title="Cargar pago a cuenta (monto editable)"
                                   href="/admin/ordenes-pago/nueva?proveedor_id=<?= (int)$proveedorId ?>&proveedor_nombre=<?= urlencode($proveedorNombre) ?>&monto=<?= $c['pendiente'] ?>&compra_ids=<?= (int)$c['id'] ?>"><i class="bi bi-cash-stack"></i></a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <?php if (empty($c['es_ajuste'])): ?><a class="btn btn-sm btn-outline-secondary" title="Ver comprobante" href="/admin/compras/<?= (int)$c['id'] ?>"><i class="bi bi-eye"></i></a><?php endif; ?>
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
        <table class="table table-sm table-hover mb-0 ctacte-data-table">
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
    var compraIds = [];
    var ajusteIds = [];
    if (document.querySelector('input[name="credito_movimiento_ids[]"]:checked')) {
        alert('Hay créditos seleccionados. Usá “Aplicar créditos” primero; los créditos no son un medio de pago.');
        return;
    }
    document.querySelectorAll('input[name="compra_ids[]"]:checked, input[name="ajuste_ids[]"]:checked').forEach(function(cb) {
        n++;
        total += parseFloat(cb.getAttribute('data-pendiente') || '0');
        if (cb.name === 'ajuste_ids[]') ajusteIds.push(cb.value);
        else compraIds.push(cb.value);
    });
    if (!n) {
        alert('Seleccioná al menos un comprobante para pagar.');
        return;
    }
    var params = new URLSearchParams();
    params.set('proveedor_id', '<?= (int)$proveedorId ?>');
    params.set('proveedor_nombre', <?= json_encode($proveedorNombre, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>);
    params.set('monto', total.toFixed(2));
    if (compraIds.length) params.set('compra_ids', compraIds.join(','));
    if (ajusteIds.length) params.set('ajuste_ids', ajusteIds.join(','));
    window.location.href = '/admin/ordenes-pago/nueva?' + params.toString();
}
function aplicarCreditosSeleccionados() {
    var creditos = [];
    var debitos = [];
    document.querySelectorAll('.ctacte-doc-check:checked').forEach(function(cb) {
        var id = cb.dataset.movimientoId;
        if (!id) return;
        if (cb.dataset.esCredito === '1') creditos.push(id);
        else debitos.push(id);
    });
    if (!creditos.length || !debitos.length) {
        alert('Seleccioná al menos un crédito pendiente y un débito pendiente para imputar.');
        return;
    }
    if (!confirm('¿Aplicar los créditos seleccionados a los débitos seleccionados?')) return;
    var form = document.createElement('form');
    form.method = 'post';
    form.action = '/admin/proveedores/ctacte/aplicar-creditos';
    var fields = {
        '_csrf': <?= json_encode($csrf) ?>,
        'proveedor_id': '<?= (int)$proveedorId ?>'
    };
    Object.keys(fields).forEach(function(name) {
        var input = document.createElement('input'); input.type = 'hidden'; input.name = name; input.value = fields[name]; form.appendChild(input);
    });
    creditos.forEach(function(id) {
        var input = document.createElement('input'); input.type = 'hidden'; input.name = 'credito_movimiento_ids[]'; input.value = id; form.appendChild(input);
    });
    debitos.forEach(function(id) {
        var input = document.createElement('input'); input.type = 'hidden'; input.name = 'debito_movimiento_ids[]'; input.value = id; form.appendChild(input);
    });
    document.body.appendChild(form); form.submit();
}
</script>
</div>
