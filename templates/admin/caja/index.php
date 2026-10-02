<?php
use Perfushopping\Web\Support\Format;

$apertura = $apertura ?? null;
$movimientos = $movimientos ?? [];
$totalesMov = $totalesMov ?? ['total_ingresos' => 0, 'total_egresos' => 0];
$ventasEfectivo = (int)($ventasEfectivo ?? 0);
$ventasTransferencia = (int)($ventasTransferencia ?? 0);
$totalRecibos = (int)($totalRecibos ?? 0);
$arqueos = $arqueos ?? [];
$detalleTurno = $detalleTurno ?? [];
$totalesForma = $totalesForma ?? [];
$totalesTarjetaEquipo = $totalesTarjetaEquipo ?? [];
$egresosTurno = (int)($egresosTurno ?? 0);
$formaLabels = ($formasPagoLabels ?? []) + ['efectivo' => 'Efectivo', 'transferencia' => 'Transf.', 'mercadopago' => 'MercadoPago', 'debito' => 'Débito', 'credito' => 'Crédito', 'tarjeta' => 'Tarjeta', 'tarjeta_credito' => 'Tarj. crédito', 'tarjeta_debito' => 'Tarj. débito', 'cheque' => 'Cheque', 'cuenta_corriente' => 'Cta. cte.'];
$tipoBadges = ['venta' => 'info', 'cobro' => 'primary', 'ingreso' => 'success', 'egreso' => 'danger'];
$historial = $historial ?? [];
$ventasPorPuntoVenta = $ventasPorPuntoVenta ?? [];
$saldoGeneral = (int)($saldoGeneral ?? 0);
$ajustePendiente = $ajustePendiente ?? null;
$esAdmin = (bool)($esAdmin ?? false);
$ajustesPendientesCount = (int)($ajustesPendientesCount ?? 0);
$cajasCerradas = (isset($cajasCerradas) && is_array($cajasCerradas)) ? $cajasCerradas : [];
if (!$cajasCerradas && $historial) {
    $cajasCerradas = array_values(array_filter($historial, static fn($h) => (($h['estado'] ?? '') === 'cerrada')));
}
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Caja</h4>
        <p class="text-muted small">Gestión de caja del turno actual</p>
    </div>
    <div class="d-flex gap-2">
        <?php if ($esAdmin && $ajustesPendientesCount > 0): ?>
            <a class="btn btn-danger btn-sm" href="/admin/caja/ajustes"><i class="bi bi-exclamation-triangle"></i> Aprobaciones (<?= $ajustesPendientesCount ?>)</a>
        <?php endif; ?>
        <?php if (!$apertura): ?>
            <a class="btn btn-accent btn-sm" href="/admin/caja/abrir"><i class="bi bi-cash-stack"></i> Abrir caja</a>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm" href="/admin/caja/movimientos"><i class="bi bi-arrow-left-right"></i> Movimientos</a>
            <a class="btn btn-outline-info btn-sm" href="/admin/caja/arqueo"><i class="bi bi-calculator"></i> Arqueo</a>
            <a class="btn btn-outline-warning btn-sm" href="/admin/caja/cierre"><i class="bi bi-stop-fill"></i> Cerrar caja</a>
            <?php if (empty($arqueos)): ?>
                <span class="small text-warning ms-1 align-self-center" title="El cierre exige un arqueo previo"><i class="bi bi-exclamation-triangle"></i> Requiere arqueo</span>
            <?php endif; ?>
            <?php if (!$ajustePendiente): ?>
                <a class="btn btn-outline-secondary btn-sm" href="/admin/caja/apertura/ajuste"><i class="bi bi-pencil-square"></i> Solicitar corrección</a>
            <?php endif; ?>
            <?php if (!empty($cajasCerradas)): ?>
                <span class="small text-muted ms-1 align-self-center">
                    <i class="bi bi-archive"></i> <?= count($cajasCerradas) ?> caja(s) cerrada(s)
                </span>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php if ($apertura && $ajustePendiente): ?>
<div class="alert alert-warning d-flex justify-content-between align-items-center mb-3">
    <div class="small">
        <strong><i class="bi bi-clock-history"></i> Corrección pendiente de aprobación:</strong>
        <?= Format::moneyFromCents((int)$ajustePendiente['valor_anterior_cents']) ?> → <?= Format::moneyFromCents((int)$ajustePendiente['valor_nuevo_cents']) ?>
        <span class="text-muted">— <?= htmlspecialchars((string)($ajustePendiente['motivo'] ?? '')) ?></span>
        <span class="text-muted">(<?= htmlspecialchars((string)($ajustePendiente['solicitado_por_nombre'] ?? '')) ?>)</span>
    </div>
    <?php if ($esAdmin): ?>
        <a class="btn btn-warning btn-sm" href="/admin/caja/ajustes">Revisar</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!$apertura): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-cash-stack" style="font-size:48px;color:#ccc"></i>
        <h5 class="mt-3">No hay caja abierta</h5>
        <p class="text-muted">Abrí la caja para registrar movimientos y hacer arqueos durante el turno.</p>
        <a class="btn btn-accent" href="/admin/caja/abrir"><i class="bi bi-cash-stack"></i> Abrir caja</a>
    </div>
</div>
<?php if ($cajasCerradas): ?>
<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold">Cajas cerradas del turno</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Turno</th>
                    <th class="text-end">Apertura</th>
                    <th class="text-end">Cierre</th>
                    <th class="text-end">Retirado</th>
                    <th>Estado</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cajasCerradas as $h): ?>
                    <tr>
                        <td class="small"><?= htmlspecialchars((string)($h['fecha'] ?? '')) ?></td>
                        <td class="small"><?= htmlspecialchars($h['turno'] ?? '') ?></td>
                        <td class="text-end small"><?= Format::moneyFromCents((int)($h['monto_inicial_cents'] ?? 0)) ?></td>
                        <td class="text-end small"><?= Format::moneyFromCents((int)($h['monto_cierre_cents'] ?? 0)) ?></td>
                        <td class="text-end small"><?= Format::moneyFromCents((int)($h['monto_retirado_cents'] ?? 0)) ?></td>
                        <td><span class="badge bg-secondary"><?= htmlspecialchars($h['estado'] ?? '') ?></span></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary py-0 px-1" title="Imprimir resumen de cierre" href="/admin/caja/cierre/imprimir?id=<?= (int)$h['id'] ?>" target="_blank"><i class="bi bi-printer"></i></a>
                            <a class="btn btn-sm btn-outline-secondary py-0 px-1" title="Solicitar corrección del cierre" href="/admin/caja/cierre/<?= (int)$h['id'] ?>/ajuste"><i class="bi bi-pencil-square"></i></a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>
<?php else: ?>
<?php
$montoInicial = (int)$apertura['monto_inicial_cents'];
$saldoEsperado = $montoInicial + $ventasEfectivo + (int)$totalesMov['total_ingresos'] - (int)$totalesMov['total_egresos'];
?>
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0"><?= Format::moneyFromCents($montoInicial) ?></div>
            <div class="small text-muted">Apertura</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0 text-success"><?= Format::moneyFromCents($ventasEfectivo) ?></div>
            <div class="small text-muted">Ventas efectivo</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0 text-info"><?= Format::moneyFromCents($ventasTransferencia) ?></div>
            <div class="small text-muted">Transferencia/MP</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0 text-primary"><?= Format::moneyFromCents($totalRecibos) ?></div>
            <div class="small text-muted">Cobrado (recibos)</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0 text-warning"><?= Format::moneyFromCents((int)$totalesMov['total_ingresos']) ?></div>
            <div class="small text-muted">Mov. ingresos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0 text-danger"><?= Format::moneyFromCents((int)$totalesMov['total_egresos']) ?></div>
            <div class="small text-muted">Mov. egresos</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0"><?= Format::moneyFromCents($saldoEsperado) ?></div>
            <div class="small text-muted">Saldo esperado</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0">
                <span class="badge bg-<?= $apertura['estado'] === 'abierta' ? 'success' : 'secondary' ?> fs-6">
                    <?= $apertura['estado'] === 'abierta' ? 'Abierta' : 'Cerrada' ?>
                </span>
            </div>
            <div class="small text-muted">Estado</div>
        </div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Movimientos del turno</span>
                <a class="btn btn-sm btn-outline-secondary py-0" href="/admin/caja/movimientos">Gestionar</a>
            </div>
            <div class="table-responsive" style="max-height:420px;overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Tipo</th>
                            <th>Detalle</th>
                            <th>Forma</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$detalleTurno): ?>
                            <tr><td colspan="5" class="text-muted text-center small">Sin movimientos en el turno</td></tr>
                        <?php else: ?>
                            <?php foreach ($detalleTurno as $d): ?>
                                <tr>
                                    <td class="small"><?= $d['hora'] !== '' ? date('H:i', strtotime($d['hora'])) : '—' ?></td>
                                    <td><span class="badge bg-<?= $tipoBadges[$d['tipo']] ?? 'secondary' ?>"><?= htmlspecialchars($d['tipo']) ?></span></td>
                                    <td class="small"><?= htmlspecialchars((string)$d['detalle']) ?></td>
                                    <td class="small">
                                        <?php if ($d['forma'] !== ''): ?>
                                            <?= htmlspecialchars($formaLabels[$d['forma']] ?? ucfirst(str_replace('_', ' ', $d['forma']))) ?>
                                            <?php if (($d['forma_tipo'] ?? '') === 'tarjeta' && trim((string)($d['equipo_nombre'] ?? '')) !== ''): ?>
                                                <span class="text-muted">· <?= htmlspecialchars((string)$d['equipo_nombre']) ?></span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end small <?= $d['monto'] < 0 ? 'text-danger' : 'text-success' ?>"><?= Format::moneyFromCents((int)$d['monto']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if ($totalesForma || $totalesTarjetaEquipo || $egresosTurno > 0): ?>
            <div class="card-footer bg-white small text-muted">
                <?php foreach ($totalesForma as $forma => $total): ?>
                    <span class="me-2"><?= htmlspecialchars($formaLabels[$forma] ?? ucfirst(str_replace('_', ' ', $forma))) ?>: <strong><?= Format::moneyFromCents((int)$total) ?></strong></span>
                <?php endforeach; ?>
                <?php if ($egresosTurno > 0): ?>
                    <span>Egresos: <strong class="text-danger">−<?= Format::moneyFromCents($egresosTurno) ?></strong></span>
                <?php endif; ?>
                <?php if ($totalesTarjetaEquipo): ?>
                    <div class="mt-1 text-dark">
                        <i class="bi bi-credit-card"></i> <strong>Tarjetas por equipo POS:</strong>
                        <?php foreach ($totalesTarjetaEquipo as $eq => $mt): ?>
                            <span class="me-2"><?= htmlspecialchars($eq) ?>: <strong><?= Format::moneyFromCents((int)$mt) ?></strong></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Arqueos registrados</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th class="text-end">Total contado</th>
                            <th>Obs.</th>
                            <th>Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$arqueos): ?>
                            <tr><td colspan="4" class="text-muted text-center small">Sin arqueos</td></tr>
                        <?php else: ?>
                            <?php foreach ($arqueos as $a): ?>
                                <tr>
                                    <td class="small"><?= date('H:i', strtotime($a['created_at'] ?? '')) ?></td>
                                    <td class="text-end"><?= Format::moneyFromCents((int)($a['total_cents'] ?? 0)) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars(mb_substr((string)($a['observaciones'] ?? ''), 0, 30)) ?></td>
                                    <td class="small"><?= htmlspecialchars((string)($a['created_by_nombre'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($historial): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Historial de cierres</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Turno</th>
                            <th class="text-end">Apertura</th>
                            <th class="text-end">Cierre</th>
                            <th class="text-end">Retirado</th>
                            <th>Estado</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($historial as $h): ?>
                            <tr>
                                <td class="small"><?= htmlspecialchars((string)($h['fecha'] ?? '')) ?></td>
                                <td class="small"><?= htmlspecialchars($h['turno'] ?? '') ?></td>
                                <td class="text-end small"><?= Format::moneyFromCents((int)($h['monto_inicial_cents'] ?? 0)) ?></td>
                                <td class="text-end small"><?= Format::moneyFromCents((int)($h['monto_cierre_cents'] ?? 0)) ?></td>
                                <td class="text-end small"><?= Format::moneyFromCents((int)($h['monto_retirado_cents'] ?? 0)) ?></td>
                                <td><span class="badge bg-<?= ($h['estado'] ?? '') === 'cerrada' ? 'secondary' : 'success' ?>"><?= htmlspecialchars($h['estado'] ?? '') ?></span></td>
                                <td>
                                    <a class="btn btn-sm btn-outline-primary py-0 px-1" title="Imprimir resumen de cierre" href="/admin/caja/cierre/imprimir?id=<?= (int)$h['id'] ?>" target="_blank"><i class="bi bi-printer"></i></a>
                                    <?php if (($h['estado'] ?? '') === 'cerrada'): ?>
                                    <a class="btn btn-sm btn-outline-secondary py-0 px-1" title="Solicitar corrección del cierre" href="/admin/caja/cierre/<?= (int)$h['id'] ?>/ajuste"><i class="bi bi-pencil-square"></i></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($ventasPorPuntoVenta): ?>
<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Ventas del día por punto de venta</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Punto de venta</th>
                            <th class="text-end">Efectivo</th>
                            <th class="text-end">Transferencia/MP</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ventasPorPuntoVenta as $vp): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($vp['sucursal_nombre'] ?? 'PV #' . ($vp['punto_venta'] ?? 0))) ?></td>
                                <td class="text-end"><?= Format::moneyFromCents((int)($vp['total_efectivo'] ?? 0)) ?></td>
                                <td class="text-end"><?= Format::moneyFromCents((int)($vp['total_transferencia'] ?? 0)) ?></td>
                                <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($vp['total'] ?? 0)) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-3 mt-1">
    <div class="col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Caja General</span>
                <span class="fw-bold">Saldo: <?= Format::moneyFromCents($saldoGeneral) ?></span>
            </div>
            <div class="card-body text-center">
                <a class="btn btn-outline-primary btn-sm" href="/admin/caja/general"><i class="bi bi-cash-stack"></i> Ver movimientos</a>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
