<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Movimientos bancarios</h4>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/admin/banco-cuentas"><i class="bi bi-wallet2"></i> Cuentas propias</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/caja/general"><i class="bi bi-arrow-left"></i> Caja general</a>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small mb-0">Desde</label>
                <input type="date" name="desde" class="form-control form-control-sm" value="<?= htmlspecialchars($desde) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Hasta</label>
                <input type="date" name="hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($hasta) ?>">
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Cuenta</label>
                <select name="cuenta" class="form-select form-select-sm">
                    <option value="0">— Todas —</option>
                    <?php foreach ($cuentas as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= $cuenta === (int)$c['id'] ? 'selected' : '' ?>><?= htmlspecialchars(trim(($c['banco'] ?? '') . ' ' . ($c['numero_cuenta'] ?? ''))) ?><?= empty($c['activo']) ? ' (inactiva)' : '' ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small mb-0">Tipo</label>
                <select name="tipo" class="form-select form-select-sm">
                    <option value="">— Todos —</option>
                    <option value="credito" <?= $tipo === 'credito' ? 'selected' : '' ?>>Crédito (ingreso)</option>
                    <option value="debito" <?= $tipo === 'debito' ? 'selected' : '' ?>>Débito (egreso)</option>
                </select>
            </div>
            <div class="col-auto">
                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-funnel"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Total débitos</div>
                <div class="fw-bold text-danger fs-5">-<?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$totales['debitos']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Total créditos</div>
                <div class="fw-bold text-success fs-5">+<?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$totales['creditos']) ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-body py-3">
                <div class="text-muted small">Neto del período</div>
                <div class="fw-bold fs-5"><?= ((int)$totales['neto']) < 0 ? '-' : '+' ?><?= \Perfushopping\Web\Support\Format::moneyFromCents(abs((int)$totales['neto'])) ?></div>
            </div>
        </div>
    </div>
</div>

<?php if ($cuentas): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Saldos por cuenta</div>
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead><tr><th>Cuenta</th><th class="text-end">Saldo</th></tr></thead>
            <tbody>
            <?php foreach ($cuentas as $c): ?>
                <tr>
                    <td><?= htmlspecialchars(trim(($c['banco'] ?? '') . ' ' . ($c['numero_cuenta'] ?? ''))) ?><?= empty($c['activo']) ? ' <span class="badge bg-secondary">inactiva</span>' : '' ?></td>
                    <td class="text-end fw-semibold"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($saldos[(int)$c['id']] ?? 0)) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php
$origenLabels = ['factura' => 'Factura', 'gasto' => 'Gasto', 'deposito' => 'Depósito', 'retiro' => 'Retiro'];
?>
<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Detalle de movimientos (máx. 500)</div>
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0 align-middle">
            <thead><tr><th>Fecha</th><th>Cuenta</th><th>Tipo</th><th>Origen</th><th>Concepto</th><th class="text-end">Monto</th><th>Usuario</th></tr></thead>
            <tbody>
            <?php if (!$movimientos): ?>
                <tr><td colspan="7" class="text-center text-muted">Sin movimientos en el período seleccionado</td></tr>
            <?php else: foreach ($movimientos as $m): $esCredito = ($m['tipo'] ?? '') === 'credito'; ?>
                <tr>
                    <td class="text-nowrap"><?= htmlspecialchars((string)($m['fecha'] ?? '')) ?></td>
                    <td><?= htmlspecialchars(trim(($m['banco_nombre'] ?? '') . ' ' . ($m['numero_cuenta'] ?? ''))) ?></td>
                    <td><?= $esCredito ? '<span class="badge bg-success">Crédito</span>' : '<span class="badge bg-danger">Débito</span>' ?></td>
                    <td class="small"><?= htmlspecialchars($origenLabels[$m['origen'] ?? ''] ?? (string)($m['origen'] ?? '')) ?><?= !empty($m['origen_id']) ? ' #' . (int)$m['origen_id'] : '' ?></td>
                    <td class="small"><?= htmlspecialchars((string)($m['concepto'] ?? '')) ?></td>
                    <td class="text-end text-nowrap <?= $esCredito ? 'text-success' : 'text-danger' ?>"><?= $esCredito ? '+' : '-' ?><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($m['monto_cents'] ?? 0)) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($m['created_by_nombre'] ?? '')) ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
