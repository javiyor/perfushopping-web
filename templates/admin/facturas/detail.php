<?php
use Perfushopping\Web\Support\Format;

$factura = $factura ?? null;
$items = $items ?? [];
$pagos = $pagos ?? [];
$arcaComprobante = $arcaComprobante ?? null;
if (!$factura):
?>
    <div class="alert alert-warning">Factura no encontrada.</div>
<?php return; endif;

$tipoLabels = ['FACT-A'=>'Factura A','FACT-B'=>'Factura B','FACT-C'=>'Factura C','NC'=>'Nota de Crédito','ND'=>'Nota de Débito'];
$tipoBadges = ['FACT-A'=>'primary','FACT-B'=>'success','FACT-C'=>'secondary','NC'=>'warning','ND'=>'danger'];
$estadoBadges = ['pendiente'=>'warning','emitida'=>'success','anulada'=>'secondary'];
$formaPagoLabels = [
    'efectivo'=>'Efectivo', 'transferencia'=>'Transferencia', 'tarjeta_credito'=>'Tarjeta crédito',
    'tarjeta_debito'=>'Tarjeta débito', 'mercadopago'=>'Mercado Pago', 'cuenta_corriente'=>'Cta. cte.',
    'cheque'=>'Cheque',
];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/facturas">Facturación</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars((string)($factura['codigo'] ?? '')) ?></li>
    </ol>
</nav>

<div class="d-flex gap-2 mb-3">
    <span class="badge bg-<?= $tipoBadges[$factura['tipo_comprobante'] ?? 'FACT-B'] ?? 'secondary' ?> fs-6">
        <?= htmlspecialchars($tipoLabels[$factura['tipo_comprobante'] ?? 'FACT-B'] ?? $factura['tipo_comprobante'] ?? '') ?>
    </span>
    <span class="badge bg-<?= $estadoBadges[$factura['estado'] ?? 'pendiente'] ?? 'secondary' ?> fs-6">
        <?= htmlspecialchars($factura['estado'] ?? 'pendiente') ?>
    </span>
    <span class="badge bg-info fs-6"><?= htmlspecialchars($formaPagoLabels[$factura['forma_pago'] ?? ''] ?? $factura['forma_pago'] ?? '') ?></span>
</div>

<div class="row g-3">
    <div class="col-lg-8">
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
                            <th class="text-end">IVA</th>
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
                                <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($it['iva_cents'] ?? 0))) ?></td>
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
                            <span><?= htmlspecialchars(Format::moneyFromCents((int)($factura['subtotal_cents'] ?? 0))) ?></span>
                        </div>
                        <div class="d-flex justify-content-between small">
                            <span>IVA:</span>
                            <span><?= htmlspecialchars(Format::moneyFromCents((int)($factura['iva_cents'] ?? 0))) ?></span>
                        </div>
                        <div class="d-flex justify-content-between fw-bold" style="font-size:20px">
                            <span>Total:</span>
                            <span><?= htmlspecialchars(Format::moneyFromCents((int)($factura['total_cents'] ?? 0))) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Acciones</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="/admin/facturas/imprimir/<?= (int)($factura['id'] ?? 0) ?>" target="_blank">
                        <i class="bi bi-printer"></i> Imprimir
                    </a>
                    <?php if (($factura['cliente_mail'] ?? '') !== '' && ($factura['estado'] ?? '') !== 'anulada'): ?>
                        <button class="btn btn-outline-info btn-sm" onclick="enviarEmail(<?= (int)($factura['id'] ?? 0) ?>)">
                            <i class="bi bi-envelope"></i> Enviar email
                        </button>
                    <?php endif; ?>
                    <?php if (($factura['estado'] ?? '') !== 'anulada'): ?>
                        <form method="post" action="/admin/facturas/estado" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)($factura['id'] ?? 0) ?>" />
                            <input type="hidden" name="estado" value="anulada" />
                            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Anular esta factura?')">
                                <i class="bi bi-x-lg"></i> Anular
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="/admin/facturas/delete" onsubmit="return confirm('Eliminar esta factura?')" style="display:inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                        <input type="hidden" name="id" value="<?= (int)($factura['id'] ?? 0) ?>" />
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i> Eliminar</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Cliente</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-5">Nombre</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($factura['cliente_nombre'] ?? '-')) ?></dd>
                    <?php if ($factura['cliente_cuit'] ?? ''): ?>
                    <dt class="col-sm-5">CUIT</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($factura['cliente_cuit']) ?></dd>
                    <?php endif; ?>
                    <?php if ($factura['cliente_condicion_iva'] ?? ''): ?>
                    <dt class="col-sm-5">Cond. IVA</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($factura['cliente_condicion_iva']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <?php if ($pagos): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Pagos</div>
            <div class="card-body">
                <?php foreach ($pagos as $pg): ?>
                <div class="d-flex justify-content-between small mb-1">
                    <span>
                        <?php if (($pg['forma_pago'] ?? '') === 'cheque'): ?>
                            <span class="badge bg-info">Cheque</span>
                            <?php if ($pg['cheque_banco'] ?? ''): ?>
                                <div class="text-muted" style="font-size:11px"><?= htmlspecialchars($pg['cheque_banco']) ?> N°<?= htmlspecialchars($pg['numero_cheque'] ?? '') ?></div>
                            <?php endif; ?>
                            <a href="/admin/cheques/<?= (int)($pg['cheque_id'] ?? 0) ?>" class="small">Ver cheque</a>
                        <?php else: ?>
                            <?= htmlspecialchars($formaPagoLabels[$pg['forma_pago'] ?? ''] ?? $pg['forma_pago'] ?? '') ?>
                        <?php endif; ?>
                    </span>
                    <span class="fw-bold"><?= htmlspecialchars(Format::moneyFromCents((int)($pg['monto_cents'] ?? 0))) ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($arcaComprobante || ($factura['cae'] ?? '')): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cloud-check"></i> ARCA</span>
                <span class="badge bg-<?= (($arcaComprobante['resultado'] ?? 'A') === 'A') ? 'success' : 'danger' ?>">
                    <?= (($arcaComprobante['resultado'] ?? 'A') === 'A') ? 'Aprobado' : 'Rechazado' ?>
                </span>
            </div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-5">CAE</dt>
                    <dd class="col-sm-7"><code><?= htmlspecialchars((string)($factura['cae'] ?? '-')) ?></code></dd>
                    <?php if ($factura['cae_vto'] ?? ''): ?>
                    <dt class="col-sm-5">Vto. CAE</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($factura['cae_vto']) ?></dd>
                    <?php endif; ?>
                    <?php if ($arcaComprobante && ($arcaComprobante['observaciones'] ?? '')): ?>
                    <dt class="col-sm-12" style="margin-top:6px">Obs.</dt>
                    <dd class="col-sm-12 text-muted" style="font-size:11px"><?= nl2br(htmlspecialchars((string)$arcaComprobante['observaciones'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>
        <?php elseif (($factura['estado'] ?? '') !== 'anulada'): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold"><i class="bi bi-cloud-arrow-up"></i> ARCA</div>
            <div class="card-body">
                <p class="small text-muted mb-2">Esta factura aún no se envió a ARCA.</p>
                <form method="post" action="/admin/arca/reenviar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="factura_id" value="<?= (int)($factura['id'] ?? 0) ?>" />
                    <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-send"></i> Enviar a ARCA</button>
                </form>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Detalles</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-5">Código</dt>
                    <dd class="col-sm-7"><strong><?= htmlspecialchars((string)($factura['codigo'] ?? '')) ?></strong></dd>
                    <dt class="col-sm-5">Fecha</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($factura['fecha'] ?? '-')) ?></dd>
                    <?php if ($factura['remito_id']): ?>
                    <dt class="col-sm-5">Remito</dt>
                    <dd class="col-sm-7"><a href="/admin/remitos/<?= (int)$factura['remito_id'] ?>">#<?= (int)$factura['remito_id'] ?></a></dd>
                    <?php endif; ?>
                    <?php if ($factura['vendedor_nombre'] ?? ''): ?>
                    <dt class="col-sm-5">Vendedor</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($factura['vendedor_nombre']) ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-5">Creado por</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($factura['created_by_nombre'] ?? '-')) ?></dd>
                    <dt class="col-sm-5">Creado</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($factura['created_at'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>

        <?php if (($factura['notas'] ?? '') !== ''): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Notas</div>
            <div class="card-body">
                <p class="small mb-0"><?= nl2br(htmlspecialchars((string)$factura['notas'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if (($factura['cliente_mail'] ?? '') !== ''): ?>
<script>
function enviarEmail(id) {
    if (!confirm('Enviar esta factura por email a <?= htmlspecialchars($factura['cliente_mail'] ?? '') ?>?')) return;
    const btn = event.target.closest('button');
    btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
    const f = document.createElement('form');
    f.method = 'POST'; f.action = '/admin/facturas/' + id + '/enviar-email';
    const c = document.createElement('input'); c.type = 'hidden'; c.name = '_csrf'; c.value = '<?= htmlspecialchars($csrf ?? '') ?>';
    f.appendChild(c);
    document.body.appendChild(f);
    fetch(f.action, { method: 'POST', body: new FormData(f) })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                alert('✅ ' + (res.message || 'Enviado'));
            } else {
                alert('❌ ' + (res.error || 'Error'));
                btn.disabled = false; btn.innerHTML = '<i class="bi bi-envelope"></i> Enviar email';
            }
        })
        .catch(() => { alert('Error de conexión'); btn.disabled = false; btn.innerHTML = '<i class="bi bi-envelope"></i> Enviar email'; });
    f.remove();
}
</script>
<?php endif; ?>
