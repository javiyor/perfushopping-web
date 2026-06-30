<?php
use Perfushopping\Web\Support\Format;

$recibo = $recibo ?? null;
$pagos = $pagos ?? [];
if (!$recibo):
?>
    <div class="alert alert-warning">Recibo no encontrado.</div>
<?php return; endif;

$formaPagoLabels = [
    'efectivo'=>'Efectivo', 'transferencia'=>'Transferencia', 'tarjeta_credito'=>'Tarjeta crédito',
    'tarjeta_debito'=>'Tarjeta débito', 'mercadopago'=>'Mercado Pago', 'cuenta_corriente'=>'Cta. cte.',
];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/recibos">Recibos</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars((string)($recibo['codigo'] ?? '')) ?></li>
    </ol>
</nav>

<div class="d-flex gap-2 mb-3">
    <span class="badge bg-<?= ($recibo['estado'] ?? '') === 'anulado' ? 'secondary' : 'success' ?> fs-6">
        <?= htmlspecialchars($recibo['estado'] ?? 'emitido') ?>
    </span>
    <?php if ($recibo['forma_pago'] ?? ''): ?>
    <span class="badge bg-info fs-6"><?= htmlspecialchars($formaPagoLabels[$recibo['forma_pago']] ?? $recibo['forma_pago']) ?></span>
    <?php endif; ?>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Acciones</span>
            </div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="/admin/recibos/imprimir/<?= (int)($recibo['id'] ?? 0) ?>" target="_blank">
                        <i class="bi bi-printer"></i> Imprimir
                    </a>
                    <?php if (($recibo['estado'] ?? '') !== 'anulado'): ?>
                        <form method="post" action="/admin/recibos/estado" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)($recibo['id'] ?? 0) ?>" />
                            <input type="hidden" name="estado" value="anulado" />
                            <button class="btn btn-outline-danger btn-sm" onclick="return confirm('Anular este recibo?')">
                                <i class="bi bi-x-lg"></i> Anular
                            </button>
                        </form>
                    <?php endif; ?>
                    <form method="post" action="/admin/recibos/delete" onsubmit="return confirm('Eliminar este recibo?')" style="display:inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                        <input type="hidden" name="id" value="<?= (int)($recibo['id'] ?? 0) ?>" />
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
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($recibo['cliente_nombre'] ?? '-')) ?></dd>
                    <?php if ($recibo['cliente_cuit'] ?? ''): ?>
                    <dt class="col-sm-5">CUIT</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars($recibo['cliente_cuit']) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Detalles</div>
            <div class="card-body">
                <dl class="row mb-0 small">
                    <dt class="col-sm-5">Código</dt>
                    <dd class="col-sm-7"><strong><?= htmlspecialchars((string)($recibo['codigo'] ?? '')) ?></strong></dd>
                    <dt class="col-sm-5">Fecha</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($recibo['fecha'] ?? '-')) ?></dd>
                    <dt class="col-sm-5">Concepto</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($recibo['concepto'] ?? '-')) ?></dd>
                    <dt class="col-sm-5">Creado por</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($recibo['created_by_nombre'] ?? '-')) ?></dd>
                    <dt class="col-sm-5">Creado</dt>
                    <dd class="col-sm-7"><?= htmlspecialchars((string)($recibo['created_at'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Facturas canceladas</span>
                <span class="badge bg-secondary"><?= count($pagos) ?> pago(s)</span>
            </div>
            <?php if ($pagos): ?>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Factura</th>
                            <th class="text-end">Monto pagado</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pagos as $pg): ?>
                            <tr>
                                <td>
                                    <?php if ($pg['factura_id']): ?>
                                        <a href="/admin/facturas/<?= (int)$pg['factura_id'] ?>">
                                    <?php endif; ?>
                                    <?php if ($pg['factura_codigo'] ?? ''): ?>
                                        <?= htmlspecialchars($pg['factura_codigo']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Pago a cuenta</span>
                                    <?php endif; ?>
                                    <?php if ($pg['factura_id']): ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end fw-bold"><?= htmlspecialchars(Format::moneyFromCents((int)($pg['monto_cents'] ?? 0))) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
            <?php else: ?>
            <div class="card-body text-muted text-center small">Sin facturas asociadas</div>
            <?php endif; ?>
            <div class="card-footer bg-white text-end">
                <div class="d-flex justify-content-between fw-bold" style="font-size:20px">
                    <span>Total recibido:</span>
                    <span><?= htmlspecialchars(Format::moneyFromCents((int)($recibo['monto_cents'] ?? 0))) ?></span>
                </div>
            </div>
        </div>

        <?php if (($recibo['notas'] ?? '') !== ''): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Notas</div>
            <div class="card-body">
                <p class="small mb-0"><?= nl2br(htmlspecialchars((string)$recibo['notas'])) ?></p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
