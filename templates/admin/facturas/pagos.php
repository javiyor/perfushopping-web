<?php
use Perfushopping\Web\Support\Format;

$pagos = $pagos ?? [];
$q = (string)($q ?? '');
$formaPago = (string)($formaPago ?? '');
$fecha = (string)($fecha ?? date('Y-m-d'));
$counts = $counts ?? [];
$totalCents = (int)($totalCents ?? 0);
$puedeEditar = (bool)($puedeEditar ?? false);
$formaPagoLabels = $formaPagoLabels ?? [];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/facturas">Facturación</a></li>
        <li class="breadcrumb-item active">Listado de pagos</li>
    </ol>
</nav>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <h5 class="mb-0">Listado de pagos de facturas</h5>
        <span class="text-muted small"><?= count($pagos) ?> pagos · Total <?= Format::moneyRoundedFromCents($totalCents) ?></span>
    </div>
    <div class="card-body">
        <form method="get" class="row g-2 mb-3">
            <div class="col-lg-3">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar factura o cliente..." />
            </div>
            <div class="col-lg-3">
                <input class="form-control form-control-sm" type="date" name="fecha" value="<?= htmlspecialchars($fecha) ?>" />
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="forma_pago">
                    <option value="">Todas las formas de pago</option>
                    <?php foreach ($formaPagoLabels as $codigo => $label): ?>
                        <option value="<?= htmlspecialchars((string)$codigo) ?>" <?= $formaPago === (string)$codigo ? 'selected' : '' ?>><?= htmlspecialchars((string)$label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-1">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Filtrar</button>
            </div>
            <div class="col-lg-2">
                <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/facturas/pagos">
                    <i class="bi bi-x"></i> Limpiar filtros
                </a>
            </div>
        </form>

        <?php if ($pagos): ?>
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead>
                    <tr>
                        <th>Factura</th>
                        <th>Cliente</th>
                        <th>Fecha</th>
                        <th style="width:150px">Forma de pago</th>
                        <th class="text-end" style="width:120px">Monto</th>
                        <th>Detalle</th>
                        <th style="width:150px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pagos as $pg): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)($pg['codigo'] ?? '')) ?></strong>
                            <small class="text-muted">#<?= (int)($pg['factura_id'] ?? 0) ?></small>
                        </td>
                        <td class="small"><?= htmlspecialchars((string)($pg['cliente_nombre'] ?? '—')) ?></td>
                        <td><?= $pg['fecha'] ? date('d/m/Y', strtotime($pg['fecha'])) : '—' ?></td>
                        <td>
                            <span class="badge bg-<?= $pg['forma_pago'] === 'cheque' ? 'info' : ($pg['forma_pago'] === 'cuenta_corriente' ? 'warning text-dark' : ($pg['forma_pago'] === 'tarjeta' ? 'primary' : 'info')) ?>">
                                <?= htmlspecialchars($formaPagoLabels[$pg['forma_pago'] ?? ''] ?? $pg['forma_pago'] ?? '') ?>
                            </span>
                        </td>
                        <td class="text-end">
                            $<?= Format::moneyRoundedFromCents((int)($pg['monto_cents'] ?? 0)) ?>
                        </td>
                        <td class="small text-muted">
                            <?php if ($pg['forma_pago'] === 'cheque'): ?>
                                Cheque N°<?= htmlspecialchars($pg['numero_cheque'] ?? '') ?>
                                <?php if ($pg['banco_nombre']): ?> - <?= htmlspecialchars($pg['banco_nombre']) ?><?php endif; ?>
                            <?php elseif ($pg['forma_pago'] === 'cuenta_corriente'): ?>
                                <?= $pg['plazo_descripcion'] ?? '' ?><?= $pg['plazo_descripcion'] && $pg['plazo_cuotas'] > 1 ? ' (cuotas: ' . (int)$pg['plazo_cuotas'] . ')' : '' ?>
                            <?php elseif ($pg['forma_pago'] === 'tarjeta'): ?>
                                Cupón N°<?= htmlspecialchars($pg['cupon_numero'] ?? '') ?>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary" href="/admin/facturas/detail/<?= (int)($pg['factura_id'] ?? 0) ?>">Ver factura</a>
                            <?php if ($puedeEditar && empty($pg['cae']) && ($pg['factura_estado'] ?? '') !== 'anulada'): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="/admin/facturas/editar/<?= (int)($pg['factura_id'] ?? 0) ?>">Editar factura</a>
                            <?php elseif ($puedeEditar): ?>
                                <span class="small text-muted">Bloqueado (ARCA/anulada)</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="mt-3">
            <?php if (isset($counts) && !empty($counts)): ?>
            <small class="text-muted">
                Distribución por forma de pago:
                <?php foreach ($counts as $forma => $total): ?>
                    <?= ' ' . htmlspecialchars($forma) . ': ' . (int)$total . ($total > 1 ? ' pagos' : ' pago'); ?>
                <?php endforeach; ?>
            </small>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="alert alert-info mb-0">
            No hay pagos registrados con los filtros aplicados.
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Filtrado en tiempo real por forma de pago (opcional)
    const formaSelect = document.querySelector('select[name="forma_pago"]');
    if (formaSelect) {
        formaSelect.addEventListener('change', function() {
            const form = this.closest('form');
            if (form) {
                form.submit();
            }
        });
    }
});
</script>
