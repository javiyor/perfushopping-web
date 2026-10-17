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
$solicitudesPendientes = $solicitudesPendientes ?? [];
$misSolicitudes = $misSolicitudes ?? [];
$esSuperadmin = (bool)($esSuperadmin ?? false);
$adminUser = $adminUser ?? [];
$csrfToken = $csrf ?? '';
$detallePago = static function (array $p, ?array $cheque = null): string {
    $items = [];
    foreach ([
        'cupon_numero' => 'Cupón', 'tarjeta_id' => 'Tarjeta ID', 'equipo_id' => 'POS ID',
        'banco_cuenta_id' => 'Cuenta ID', 'idplazo' => 'Plazo ID', 'banco_id' => 'Banco ID',
    ] as $key => $label) {
        if (($p[$key] ?? '') !== '' && $p[$key] !== null) $items[] = $label . ': ' . (string)$p[$key];
    }
    if (!empty($p['cupon_monto_cents'])) $items[] = 'Cupón $: ' . number_format((int)$p['cupon_monto_cents'] / 100, 2, ',', '.');
    if (!empty($p['moneda'])) $items[] = (string)$p['moneda'] . ': ' . number_format((int)($p['monto_moneda_cents'] ?? 0) / 100, 2, ',', '.') . ' · cotiz. ' . (string)($p['cotizacion'] ?? '');
    $chequeId = (int)($p['cheque_id'] ?? 0);
    if ($chequeId > 0) {
        $items[] = 'Cheque #' . $chequeId;
        if ($cheque) {
            foreach (['banco_emisor', 'numero_cheque', 'titular', 'cuit_titular', 'fecha_vencimiento'] as $key) {
                if (!empty($cheque[$key])) $items[] = (string)$cheque[$key];
            }
        }
    }
    return htmlspecialchars($items ? implode(' · ', $items) : '—');
};
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/facturas">Facturación</a></li>
        <li class="breadcrumb-item active">Listado de pagos</li>
    </ol>
</nav>

<?php if ($esSuperadmin): ?>
<div class="card shadow-sm mb-3" id="solicitudesPagosPendientes">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Solicitudes de corrección pendientes</span><span class="badge bg-danger"><?= count($solicitudesPendientes) ?></span>
    </div>
    <?php if (!$solicitudesPendientes): ?>
        <div class="card-body small text-muted">No hay solicitudes pendientes.</div>
    <?php else: ?>
        <div class="list-group list-group-flush">
            <?php foreach ($solicitudesPendientes as $s): ?>
                <?php
                    $oldSnap = json_decode((string)($s['original_json'] ?? ''), true) ?: [];
                    $oldRows = $oldSnap['pagos'] ?? [];
                    $oldCheques = [];
                    foreach (($oldSnap['cheques'] ?? []) as $chq) $oldCheques[(int)($chq['id'] ?? 0)] = $chq;
                    $newRows = json_decode((string)($s['pagos_json'] ?? ''), true) ?: [];
                    $newCheques = json_decode((string)($s['cheques_json'] ?? ''), true) ?: [];
                    $esPropia = (int)($s['requested_by'] ?? 0) === (int)($adminUser['id'] ?? 0);
                ?>
                <div class="list-group-item">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div><strong><?= htmlspecialchars((string)($s['factura_codigo'] ?? 'Factura')) ?></strong> · Solicitó <?= htmlspecialchars((string)($s['requested_by_nombre'] ?? '')) ?> · <?= htmlspecialchars((string)($s['created_at'] ?? '')) ?></div>
                        <a class="btn btn-sm btn-outline-secondary" href="/admin/facturas/<?= (int)($s['factura_id'] ?? 0) ?>">Ver factura</a>
                    </div>
                    <div class="small mt-1"><strong>Motivo:</strong> <?= nl2br(htmlspecialchars((string)($s['motivo'] ?? ''))) ?></div>
                    <div class="table-responsive mt-2">
                        <table class="table table-sm table-bordered mb-2">
                            <thead><tr><th>#</th><th>Forma anterior</th><th>Detalle anterior</th><th class="text-end">Importe anterior</th><th>Forma solicitada</th><th>Detalle solicitado</th><th class="text-end">Importe solicitado</th></tr></thead>
                            <tbody>
                                <?php foreach ($newRows as $i => $p): $old = $oldRows[$i] ?? []; ?>
                                    <tr>
                                        <td><?= (int)$i + 1 ?></td>
                                        <td><?= htmlspecialchars($formaPagoLabels[(string)($old['forma_pago'] ?? '')] ?? (string)($old['forma_pago'] ?? '—')) ?></td>
                                        <td class="small"><?= $detallePago($old, $oldCheques[(int)($old['cheque_id'] ?? 0)] ?? null) ?></td>
                                        <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($old['monto_cents'] ?? 0))) ?></td>
                                        <td><?= htmlspecialchars($formaPagoLabels[(string)($p['forma_pago'] ?? '')] ?? (string)($p['forma_pago'] ?? '—')) ?></td>
                                        <td class="small"><?= $detallePago($p, $newCheques[(int)($p['cheque_id'] ?? 0)] ?? null) ?></td>
                                        <td class="text-end fw-semibold"><?= htmlspecialchars(Format::moneyFromCents((int)($p['monto_cents'] ?? 0))) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php if ($esPropia): ?>
                        <div class="small text-warning">No podés aprobar tu propia solicitud.</div>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <form method="post" action="/admin/facturas/pagos/aprobar" onsubmit="return confirm('¿Aprobar la corrección de pagos de esta factura?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                                <input type="hidden" name="solicitud_id" value="<?= (int)$s['id'] ?>" />
                                <button class="btn btn-sm btn-accent" type="submit"><i class="bi bi-check-lg"></i> Aprobar y aplicar</button>
                            </form>
                            <form method="post" action="/admin/facturas/pagos/rechazar" class="d-flex gap-1" onsubmit="return confirm('¿Rechazar esta solicitud?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                                <input type="hidden" name="solicitud_id" value="<?= (int)$s['id'] ?>" />
                                <input class="form-control form-control-sm" name="nota_rechazo" maxlength="255" placeholder="Motivo de rechazo" />
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i> Rechazar</button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if ($misSolicitudes): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Mis solicitudes recientes</div>
    <div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>Solicitud</th><th>Factura</th><th>Estado</th><th>Motivo</th><th>Respuesta</th><th>Fecha</th><th></th></tr></thead>
        <tbody><?php foreach ($misSolicitudes as $s): $status = (string)($s['status'] ?? 'pendiente'); $badge = $status === 'aprobada' ? 'success' : ($status === 'rechazada' ? 'danger' : 'warning text-dark'); ?>
            <tr><td>#<?= (int)$s['id'] ?></td><td><?= htmlspecialchars((string)($s['factura_codigo'] ?? '')) ?></td><td><span class="badge bg-<?= $badge ?>"><?= htmlspecialchars($status) ?></span></td><td class="small"><?= htmlspecialchars((string)($s['motivo'] ?? '')) ?></td><td class="small"><?= htmlspecialchars((string)($s['rejection_note'] ?? $s['decided_by_nombre'] ?? '')) ?></td><td class="small"><?= htmlspecialchars((string)($s['updated_at'] ?? $s['created_at'] ?? '')) ?></td><td><?php if ($status === 'pendiente'): ?><a class="btn btn-sm btn-outline-primary" href="/admin/facturas/pagos/editar/<?= (int)$s['factura_id'] ?>?solicitud_id=<?= (int)$s['id'] ?>"><i class="bi bi-pencil"></i> Corregir</a><?php endif; ?></td></tr>
        <?php endforeach; ?></tbody>
    </table></div>
</div>
<?php endif; ?>

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
                            <a class="btn btn-sm btn-outline-primary" href="/admin/facturas/<?= (int)($pg['factura_id'] ?? 0) ?>">Ver factura</a>
                            <?php if ($puedeEditar): ?>
                                <a class="btn btn-sm btn-outline-secondary" href="/admin/facturas/pagos/editar/<?= (int)($pg['factura_id'] ?? 0) ?>">Editar pagos</a>
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
