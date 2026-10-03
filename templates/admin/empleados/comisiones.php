<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Comisiones ganadas</h4>
        <p class="text-muted small">Comisión por marca sobre ventas del mes (facturas emitidas con CAE)</p>
    </div>
    <?php if (!empty($esAdmin)): ?>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/empleados">Volver</a>
    <?php endif; ?>
</div>

<form method="get" class="row g-2 align-items-end mb-3">
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Mes</label>
        <input type="month" class="form-control form-control-sm" name="periodo" value="<?= htmlspecialchars($periodo ?? '') ?>" />
    </div>
    <?php if (!empty($esAdmin)): ?>
    <div class="col-auto">
        <label class="form-label small fw-semibold mb-1">Empleado</label>
        <select class="form-select form-select-sm" name="uid">
            <option value="0">— Todos —</option>
            <?php foreach (($empleadosSel ?? []) as $e): ?>
            <option value="<?= (int)$e['admin_user_id'] ?>" <?= (int)$e['admin_user_id'] === (int)($uidSel ?? 0) ? 'selected' : '' ?>>
                <?= htmlspecialchars($e['nombre'] ?? '') ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>
    <div class="col-auto">
        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-search"></i> Consultar</button>
    </div>
</form>

<?php if (empty($informe)): ?>
<div class="alert alert-secondary">
    <?= !empty($esAdmin) ? 'Sin empleados configurados para este filtro.' : 'Todavía no tenés configuración de empleado.' ?>
</div>
<?php endif; ?>

<?php $granTotal = 0; ?>
<?php foreach (($informe ?? []) as $emp): $granTotal += (int)$emp['total_cents']; ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold"><i class="bi bi-person-badge"></i> <?= htmlspecialchars($emp['nombre']) ?>
            <span class="badge bg-secondary text-uppercase"><?= htmlspecialchars($emp['tipo']) ?></span>
        </span>
        <span class="fw-bold">Total: <?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$emp['total_cents']) ?></span>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Marca</th>
                    <th class="text-end">%</th>
                    <th class="text-end">Ventas del mes</th>
                    <th class="text-end">Comisión ganada</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$emp['marcas']): ?>
                <tr><td colspan="4" class="text-muted text-center py-3">Sin comisiones configuradas.</td></tr>
                <?php endif; ?>
                <?php foreach ($emp['marcas'] as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['marca']) ?></td>
                    <td class="text-end"><?= htmlspecialchars(rtrim(rtrim(number_format((float)$m['porcentaje'], 2, '.', ','), '0'), '.')) ?>%</td>
                    <td class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$m['ventas_cents']) ?></td>
                    <td class="text-end fw-semibold"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$m['comision_cents']) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-light">
                    <th colspan="3" class="text-end">Total <?= htmlspecialchars($emp['nombre']) ?></th>
                    <th class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$emp['total_cents']) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
<?php endforeach; ?>

<?php if (!empty($esAdmin) && count($informe ?? []) > 1): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
        <span>Total general (<?= count($informe) ?> empleados)</span>
        <span><?= \Perfushopping\Web\Support\Format::moneyFromCents($granTotal) ?></span>
    </div>
</div>
<?php endif; ?>
