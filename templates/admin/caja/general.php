<?php
use Perfushopping\Web\Support\Format;
$movimientos = $movimientos ?? [];
$totales = $totales ?? ['total_ingresos' => 0, 'total_egresos' => 0];
$totalesControl = $totalesControl ?? ['ing_controlado' => 0, 'ing_no_controlado' => 0, 'egr_controlado' => 0, 'egr_no_controlado' => 0];
$cierres = $cierres ?? [];
$gastosPorForma = $gastosPorForma ?? [];
$saldo = (int)($saldo ?? 0);
$tipo = (string)($tipo ?? '');
$desde = (string)($desde ?? '');
$hasta = (string)($hasta ?? '');
$q = (string)($q ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Caja General</h4>
        <p class="text-muted small">Movimientos globales de dinero — ingresos y egresos</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver a Caja</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-4">
        <div class="card-dashboard text-center p-3">
            <div class="h5 fw-bold mb-0 text-success"><?= Format::moneyFromCents((int)$totales['total_ingresos']) ?></div>
            <div class="small text-muted">Total ingresos</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card-dashboard text-center p-3">
            <div class="h5 fw-bold mb-0 text-danger"><?= Format::moneyFromCents((int)$totales['total_egresos']) ?></div>
            <div class="small text-muted">Total egresos</div>
        </div>
    </div>
    <div class="col-4">
        <div class="card-dashboard text-center p-3">
            <div class="h5 fw-bold mb-0 <?= $saldo < 0 ? 'text-danger' : 'text-primary' ?>"><?= Format::moneyFromCents($saldo) ?></div>
            <div class="small text-muted">Saldo actual</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center p-3">
            <div class="h5 fw-bold mb-0 text-success"><?= Format::moneyFromCents((int)$totalesControl['ing_controlado']) ?></div>
            <div class="small text-muted">Ingresos controlados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center p-3">
            <div class="h5 fw-bold mb-0 text-warning"><?= Format::moneyFromCents((int)$totalesControl['ing_no_controlado']) ?></div>
            <div class="small text-muted">Ingresos no controlados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center p-3">
            <div class="h5 fw-bold mb-0 text-success"><?= Format::moneyFromCents((int)$totalesControl['egr_controlado']) ?></div>
            <div class="small text-muted">Egresos controlados</div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-dashboard text-center p-3">
            <div class="h5 fw-bold mb-0 text-warning"><?= Format::moneyFromCents((int)$totalesControl['egr_no_controlado']) ?></div>
            <div class="small text-muted">Egresos no controlados</div>
        </div>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Resumen de cajas</span>
        <span class="badge bg-secondary"><?= count($cierres) ?> cierre(s)</span>
    </div>
    <div class="table-responsive" style="max-height:700px;overflow-y:auto">
        <table class="table table-sm mb-0" style="font-size:.8rem">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Caja</th>
                    <th class="text-end">Ingr. efectivo</th>
                    <th class="text-end">Ingr. tarjeta</th>
                    <th class="text-end">Ingr. transferencia</th>
                    <th class="text-end">Total</th>
                    <th>Control</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$cierres): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin cierres de caja en el período</td></tr>
                <?php else: ?>
                    <?php foreach ($cierres as $c): ?>
                        <?php $rs = $c['resumen'] ?? ['efectivo' => 0, 'tarjeta' => 0, 'transferencia' => 0, 'total' => 0]; ?>
                        <?php $fh = strtotime($c['updated_at'] ?? $c['created_at'] ?? ''); ?>
                        <tr>
                            <td><?= $fh ? date('d/m/Y H:i', $fh) : htmlspecialchars((string)($c['fecha'] ?? '')) ?></td>
                            <td>Caja #<?= (int)($c['id'] ?? 0) ?> · <?= htmlspecialchars((string)($c['sucursal_nombre'] ?? '')) ?> (<?= htmlspecialchars((string)($c['turno'] ?? '')) ?>) · PV <?= (int)($c['pto_vta'] ?? 0) ?: '—' ?></td>
                            <td class="text-end text-success"><?= Format::moneyFromCents((int)$rs['efectivo']) ?></td>
                            <td class="text-end text-success"><?= Format::moneyFromCents((int)$rs['tarjeta']) ?></td>
                            <td class="text-end text-success"><?= Format::moneyFromCents((int)$rs['transferencia']) ?></td>
                            <td class="text-end fw-bold"><?= Format::moneyFromCents((int)$rs['total']) ?></td>
                            <td>
                                <?php if (!empty($c['controlado_por'])): ?>
                                    <span class="badge bg-success"><i class="bi bi-check-lg"></i> OK</span>
                                    <div class="small text-muted"><?= htmlspecialchars((string)($c['controlado_por_nombre'] ?? '')) ?></div>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">Pendiente</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-nowrap">
                                <a class="btn btn-outline-primary btn-sm py-0 px-1" title="Imprimir resumen de cierre" href="/admin/caja/cierre/imprimir?id=<?= (int)($c['id'] ?? 0) ?>" target="_blank"><i class="bi bi-printer"></i></a>
                                <form method="post" action="/admin/caja/cierre/controlar" style="display:inline">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($c['id'] ?? 0) ?>" />
                                    <?php if (!empty($c['controlado_por'])): ?>
                                        <input type="hidden" name="accion" value="descontrolar" />
                                        <button class="btn btn-outline-warning btn-sm py-0 px-1" title="Descontrolar"><i class="bi bi-x-lg"></i></button>
                                    <?php else: ?>
                                        <input type="hidden" name="accion" value="controlar" />
                                        <button class="btn btn-outline-success btn-sm py-0 px-1" title="Imputar como correcto"><i class="bi bi-check-lg"></i></button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($gastosPorForma): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Gastos pagados con caja general por forma de pago</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Forma de pago</th>
                    <th class="text-center">Cantidad</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php $tg = 0; foreach ($gastosPorForma as $g): $tg += (int)($g['total'] ?? 0); ?>
                    <tr>
                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars((string)($g['forma_pago'] ?? '')) ?></span></td>
                        <td class="text-center"><?= (int)($g['cantidad'] ?? 0) ?></td>
                        <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($g['total'] ?? 0)) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr class="table-light">
                    <td class="fw-bold" colspan="2">Total gastos caja general</td>
                    <td class="text-end fw-bold"><?= Format::moneyFromCents($tg) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Nuevo movimiento directo</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/general/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tipo</label>
                        <select class="form-select" name="tipo" required>
                            <option value="ingreso">Ingreso</option>
                            <option value="egreso">Egreso</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Concepto</label>
                        <input class="form-control" name="concepto" required placeholder="Ej: Gasto general, depósito..." />
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_cents" type="number" required min="0.01" step="0.01" />
                        </div>
                        <div class="form-text">En pesos (ej: 200 = $200,00)</div>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Registrar</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Filtros</div>
            <div class="card-body">
                <form method="get" action="/admin/caja/general">
                    <div class="mb-2">
                        <label class="small">Tipo</label>
                        <select class="form-select form-select-sm" name="tipo">
                            <option value="">Todos</option>
                            <option value="ingreso" <?= $tipo === 'ingreso' ? 'selected' : '' ?>>Ingresos</option>
                            <option value="egreso" <?= $tipo === 'egreso' ? 'selected' : '' ?>>Egresos</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="small">Desde</label>
                        <input class="form-control form-control-sm" name="desde" type="date" value="<?= htmlspecialchars($desde) ?>" />
                    </div>
                    <div class="mb-2">
                        <label class="small">Hasta</label>
                        <input class="form-control form-control-sm" name="hasta" type="date" value="<?= htmlspecialchars($hasta) ?>" />
                    </div>
                    <div class="mb-2">
                        <label class="small">Buscar</label>
                        <input class="form-control form-control-sm" name="q" placeholder="Concepto u origen" value="<?= htmlspecialchars($q) ?>" />
                    </div>
                    <button class="btn btn-outline-primary btn-sm w-100" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
                    <a class="btn btn-outline-secondary btn-sm w-100 mt-1" href="/admin/caja/general">Limpiar</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Movimientos</span>
                <span class="badge bg-secondary"><?= count($movimientos) ?> registro(s)</span>
            </div>
            <div class="table-responsive" style="max-height:500px;overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Origen</th>
                            <th>Concepto</th>
                            <th class="text-end">Monto</th>
                            <th>Controlado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$movimientos): ?>
                            <tr><td colspan="7" class="text-muted text-center">Sin movimientos</td></tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                                <?php $origenLabels = ['cierre_caja'=>'Cierre caja','directo'=>'Directo']; ?>
                                <tr>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime($m['created_at'] ?? '')) ?></td>
                                    <td><span class="badge bg-<?= $m['tipo'] === 'ingreso' ? 'success' : 'danger' ?>"><?= htmlspecialchars($m['tipo']) ?></span></td>
                                    <td class="small"><?= htmlspecialchars($origenLabels[$m['origen'] ?? ''] ?? $m['origen'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars((string)($m['concepto'] ?? '')) ?></td>
                                    <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($m['monto_cents'] ?? 0)) ?></td>
                                    <td>
                                        <?php if ($m['controlado']): ?>
                                            <span class="badge bg-success"><i class="bi bi-check-lg"></i> Controlado</span>
                                            <div class="small text-muted"><?= htmlspecialchars((string)($m['controlado_por_nombre'] ?? '')) ?></div>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="post" action="/admin/caja/general/controlar" style="display:inline">
                                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>" />
                                            <?php if ($m['controlado']): ?>
                                                <input type="hidden" name="accion" value="descontrolar" />
                                                <button class="btn btn-outline-warning btn-sm" title="Descontrolar"><i class="bi bi-x-lg"></i></button>
                                            <?php else: ?>
                                                <input type="hidden" name="accion" value="controlar" />
                                                <button class="btn btn-outline-success btn-sm" title="Controlar"><i class="bi bi-check-lg"></i></button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
