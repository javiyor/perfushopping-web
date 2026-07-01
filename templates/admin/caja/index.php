<?php
use Perfushopping\Web\Support\Format;

$apertura = $apertura ?? null;
$movimientos = $movimientos ?? [];
$totalesMov = $totalesMov ?? ['total_ingresos' => 0, 'total_egresos' => 0];
$ventasEfectivo = (int)($ventasEfectivo ?? 0);
$ventasTransferencia = (int)($ventasTransferencia ?? 0);
$totalRecibos = (int)($totalRecibos ?? 0);
$arqueos = $arqueos ?? [];
$historial = $historial ?? [];
$ventasPorPuntoVenta = $ventasPorPuntoVenta ?? [];
$saldoGeneral = (int)($saldoGeneral ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Caja</h4>
        <p class="text-muted small">Gestión de caja del turno actual</p>
    </div>
    <div class="d-flex gap-2">
        <?php if (!$apertura): ?>
            <a class="btn btn-accent btn-sm" href="/admin/caja/abrir"><i class="bi bi-cash-stack"></i> Abrir caja</a>
        <?php else: ?>
            <a class="btn btn-outline-primary btn-sm" href="/admin/caja/movimientos"><i class="bi bi-arrow-left-right"></i> Movimientos</a>
            <a class="btn btn-outline-info btn-sm" href="/admin/caja/arqueo"><i class="bi bi-calculator"></i> Arqueo</a>
            <a class="btn btn-outline-warning btn-sm" href="/admin/caja/cierre"><i class="bi bi-stop-fill"></i> Cerrar caja</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!$apertura): ?>
<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <i class="bi bi-cash-stack" style="font-size:48px;color:#ccc"></i>
        <h5 class="mt-3">No hay caja abierta</h5>
        <p class="text-muted">Abrí la caja para registrar movimientos y hacer arqueos durante el turno.</p>
        <a class="btn btn-accent" href="/admin/caja/abrir"><i class="bi bi-cash-stack"></i> Abrir caja</a>
    </div>
</div>
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
            <div class="card-header bg-white fw-semibold">Últimos movimientos</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$movimientos): ?>
                            <tr><td colspan="4" class="text-muted text-center small">Sin movimientos</td></tr>
                        <?php else: ?>
                            <?php foreach (array_slice(array_reverse($movimientos), 0, 10) as $m): ?>
                                <tr>
                                    <td class="small"><?= date('H:i', strtotime($m['created_at'] ?? '')) ?></td>
                                    <td><span class="badge bg-<?= $m['tipo'] === 'ingreso' ? 'success' : 'danger' ?>"><?= htmlspecialchars($m['tipo']) ?></span></td>
                                    <td class="small"><?= htmlspecialchars((string)($m['concepto'] ?? '')) ?></td>
                                    <td class="text-end small"><?= Format::moneyFromCents((int)($m['monto_cents'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
