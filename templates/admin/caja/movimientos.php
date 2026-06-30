<?php
use Perfushopping\Web\Support\Format;
$apertura = $apertura ?? null;
$movimientos = $movimientos ?? [];
$totalesMov = $totalesMov ?? ['total_ingresos' => 0, 'total_egresos' => 0];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Movimientos de caja</h4>
        <p class="text-muted small">Ingresos y egresos extra no facturables</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Nuevo movimiento</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/movimientos/guardar">
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
                        <input class="form-control" name="concepto" required placeholder="Ej: Pago proveedor, gastos diarios, retiro..." />
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_cents" type="number" required min="1" step="1" />
                        </div>
                        <div class="form-text">En centavos (ej: 20000 = $200,00)</div>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Registrar</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="row g-2 mb-3">
            <div class="col-6">
                <div class="card-dashboard text-center p-3">
                    <div class="h5 fw-bold mb-0 text-success"><?= Format::moneyFromCents((int)$totalesMov['total_ingresos']) ?></div>
                    <div class="small text-muted">Total ingresos</div>
                </div>
            </div>
            <div class="col-6">
                <div class="card-dashboard text-center p-3">
                    <div class="h5 fw-bold mb-0 text-danger"><?= Format::moneyFromCents((int)$totalesMov['total_egresos']) ?></div>
                    <div class="small text-muted">Total egresos</div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Movimientos</div>
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th>Tipo</th>
                            <th>Concepto</th>
                            <th class="text-end">Monto</th>
                            <th>Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$movimientos): ?>
                            <tr><td colspan="5" class="text-muted text-center">Sin movimientos</td></tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td class="small"><?= date('H:i', strtotime($m['created_at'] ?? '')) ?></td>
                                    <td><span class="badge bg-<?= $m['tipo'] === 'ingreso' ? 'success' : 'danger' ?>"><?= htmlspecialchars($m['tipo']) ?></span></td>
                                    <td><?= htmlspecialchars((string)($m['concepto'] ?? '')) ?></td>
                                    <td class="text-end"><?= Format::moneyFromCents((int)($m['monto_cents'] ?? 0)) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($m['created_by_nombre'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
