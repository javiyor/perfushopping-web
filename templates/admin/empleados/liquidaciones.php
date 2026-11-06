<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Liquidaciones</h4>
        <p class="text-muted small">Historial de sueldos liquidados</p>
    </div>
    <div class="d-flex gap-2">
        <form method="get" class="d-flex gap-2 align-items-center">
            <input class="form-control form-control-sm" type="month" name="periodo" value="<?= htmlspecialchars($periodo) ?>" onchange="this.form.submit()" style="width:160px" />
            <a class="btn btn-accent btn-sm" href="/admin/empleados/liquidar"><i class="bi bi-calculator"></i> Nueva liquidación</a>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Período</th>
                    <th>Empleado</th>
                    <th class="text-end">Sueldo base</th>
                    <th class="text-end">Horas</th>
                    <th class="text-end">Comisión</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                <tr><td colspan="9" class="text-muted text-center py-3">Sin liquidaciones.</td></tr>
                <?php endif; ?>
                <?php foreach ($list as $liq): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($liq['periodo'] ?? '') ?></td>
                    <td><?= htmlspecialchars($liq['nombre'] ?? $liq['username'] ?? '') ?></td>
                    <td class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($liq['sueldo_base_cents'] ?? 0)) ?></td>
                    <td class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($liq['horas_cents'] ?? 0)) ?></td>
                    <td class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($liq['comision_cents'] ?? 0)) ?></td>
                    <td class="text-end fw-bold"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($liq['total_cents'] ?? 0)) ?></td>
                    <td>
                        <span class="badge bg-<?= $liq['estado'] === 'pagada' ? 'success' : ($liq['estado'] === 'anulada' ? 'secondary' : 'warning') ?>">
                            <?= htmlspecialchars($liq['estado'] ?? '') ?>
                        </span>
                    </td>
                    <td class="small text-muted"><?= htmlspecialchars(substr($liq['created_at'] ?? '', 0, 10)) ?></td>
                    <td class="text-end">
                        <a class="btn btn-sm btn-outline-primary py-0 px-1" href="/admin/empleados/liquidaciones/<?= (int)$liq['id'] ?>/imprimir" target="_blank" title="Imprimir recibo de sueldo"><i class="bi bi-printer"></i></a>
                        <?php if ($liq['estado'] === 'calculada'): ?>
                        <form method="post" action="/admin/empleados/liquidaciones/pagar" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$liq['id'] ?>" />
                            <button class="btn btn-sm btn-outline-success py-0 px-1" onclick="return confirm('Marcar como pagada?')" title="Pagar"><i class="bi bi-check-lg"></i></button>
                        </form>
                        <form method="post" action="/admin/empleados/liquidaciones/anular" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$liq['id'] ?>" />
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="return confirm('Anular esta liquidación?')" title="Anular"><i class="bi bi-x-lg"></i></button>
                        </form>
                        <?php endif; ?>
                        <?php if ($liq['estado'] === 'anulada'): ?>
                        <form method="post" action="/admin/empleados/liquidaciones/eliminar" style="display:inline">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$liq['id'] ?>" />
                            <button class="btn btn-sm btn-outline-danger py-0 px-1" onclick="return confirm('Eliminar definitivamente esta liquidación anulada?')" title="Eliminar liquidación anulada"><i class="bi bi-trash"></i></button>
                        </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
