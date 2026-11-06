<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Liquidar sueldo</h4>
        <p class="text-muted small">Calculá sueldo + horas + comisiones por período</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/empleados/liquidaciones">Ver liquidaciones</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" class="row g-2 align-items-end">
            <div class="col-auto">
                <label class="form-label small">Vendedor</label>
                <select class="form-select form-select-sm" name="vendedor_id" onchange="this.form.submit()">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($vendedores as $v): ?>
                    <option value="<?= (int)$v['id'] ?>" <?= (int)$v['id'] === $vendedorId ? 'selected' : '' ?>>
                        <?= htmlspecialchars($v['nombre'] ?? $v['username'] ?? '') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-auto">
                <label class="form-label small">Período</label>
                <input class="form-control form-control-sm" type="month" name="periodo" value="<?= htmlspecialchars($periodo) ?>" onchange="this.form.submit()" />
            </div>
            <?php if ($vendedorId > 0 && $config): ?>
            <div class="col-auto">
                <label class="form-label small">Gastos desde</label>
                <input class="form-control form-control-sm" type="date" name="gastos_desde" value="<?= htmlspecialchars($gastosDesde ?? '') ?>" required />
            </div>
            <div class="col-auto">
                <label class="form-label small">Gastos hasta</label>
                <input class="form-control form-control-sm" type="date" name="gastos_hasta" value="<?= htmlspecialchars($gastosHasta ?? '') ?>" required />
            </div>
            <div class="col-auto"><button class="btn btn-outline-secondary btn-sm" type="submit">Aplicar período de gastos</button></div>
            <?php endif; ?>
        </form>
    </div>
</div>

<?php if ($vendedorId > 0 && $config && $resultado): ?>
<form method="post" action="/admin/empleados/liquidar/guardar" id="liquidacionForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
    <input type="hidden" name="vendedor_id" value="<?= $vendedorId ?>" />
    <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>" />
    <input type="hidden" name="gastos_desde" value="<?= htmlspecialchars($gastosDesde ?? '') ?>" />
    <input type="hidden" name="gastos_hasta" value="<?= htmlspecialchars($gastosHasta ?? '') ?>" />
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Gastos y adelantos disponibles para descontar</div>
            <div class="card-body p-0">
                <?php if (!$adelantos): ?>
                    <p class="text-muted text-center py-4 mb-0">No hay gastos de sueldo en el período seleccionado.</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead><tr><th>Incluir</th><th>Fecha</th><th>Detalle</th><th class="text-end">Importe</th></tr></thead>
                        <tbody>
                        <?php foreach ($adelantos as $adelanto): ?>
                            <tr>
                                <td><input class="form-check-input adelanto-check" type="checkbox" name="adelanto_ids[]" value="<?= (int)$adelanto['id'] ?>" data-cents="<?= (int)$adelanto['importe_cents'] ?>" checked aria-label="Incluir gasto"></td>
                                <td class="text-nowrap"><?= htmlspecialchars($adelanto['fecha'] ?? '') ?></td>
                                <td><?= htmlspecialchars($adelanto['descripcion'] ?? 'Adelanto de sueldo') ?></td>
                                <td class="text-end text-nowrap"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$adelanto['importe_cents']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php if ($adelantos): ?>
            <div class="card-footer bg-white small text-muted">Marcá los gastos que correspondan descontar de esta liquidación. El período se puede cambiar arriba.</div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Liquidación</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-6">Empleado:</dt>
                    <dd class="col-sm-6"><?= htmlspecialchars($config['nombre'] ?? '') ?></dd>
                    <dt class="col-sm-6">Período:</dt>
                    <dd class="col-sm-6"><?= htmlspecialchars($periodo) ?></dd>
                    <dt class="col-sm-6">Tipo:</dt>
                    <dd class="col-sm-6"><?= htmlspecialchars($config['tipo'] ?? '') ?></dd>
                    <dt class="col-sm-6">Turnos automáticos computados:</dt>
                    <dd class="col-sm-6"><?= (int)($resultado['turnos_presentes'] ?? 0) ?> × 4 h = <?= htmlspecialchars((string)($resultado['horas_turnos'] ?? 0)) ?> h</dd>
                    <dt class="col-sm-6">Horas manuales:</dt>
                    <dd class="col-sm-6"><?= htmlspecialchars((string)($resultado['horas_manuales'] ?? 0)) ?> h</dd>
                    <dt class="col-sm-6">Horas totales:</dt>
                    <dd class="col-sm-6 fw-semibold"><?= htmlspecialchars((string)($resultado['total_horas'] ?? 0)) ?> h</dd>
                </dl>
                <hr />
                <dl class="row small mb-0">
                    <dt class="col-sm-6">Sueldo base:</dt>
                    <dd class="col-sm-6 text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($resultado['sueldo_base_cents'] ?? 0)) ?></dd>
                    <?php if (($resultado['horas_cents'] ?? 0) > 0): ?>
                    <dt class="col-sm-6">Horas:</dt>
                    <dd class="col-sm-6 text-end"><?= htmlspecialchars((string)($resultado['total_horas'] ?? 0)) ?> h × <?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($resultado['valor_hora_cents'] ?? 0)) ?> = <?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($resultado['horas_cents'] ?? 0)) ?></dd>
                    <?php endif; ?>
                    <?php if (($resultado['comision_cents'] ?? 0) > 0): ?>
                    <dt class="col-sm-6">Comisiones:</dt>
                    <dd class="col-sm-6 text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($resultado['comision_cents'] ?? 0)) ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-6">Gastos seleccionados:</dt>
                    <dd class="col-sm-6 text-end text-danger" id="adelantoTotal">− <?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($resultado['adelanto_cents'] ?? 0)) ?></dd>
                </dl>
                <hr />
                <div class="d-flex justify-content-between fw-bold" style="font-size:20px">
                    <span>SALDO A PAGAR:</span>
                    <span id="liquidacionTotal"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($resultado['total_cents'] ?? 0)) ?></span>
                </div>

                <button class="btn btn-accent w-100 py-2 fw-bold mt-3" type="submit" onclick="return confirm('Guardar esta liquidación?')">
                    <i class="bi bi-calculator"></i> Guardar liquidación
                </button>
            </div>
        </div>
    </div>
</div>
</form>
<script>
(function(){
    const gross = <?= (int)($resultado['sueldo_base_cents'] ?? 0) + (int)($resultado['horas_cents'] ?? 0) + (int)($resultado['comision_cents'] ?? 0) ?>;
    const fmt = new Intl.NumberFormat('es-AR', {style:'currency', currency:'ARS'});
    const update = function(){
        let deductions = 0;
        document.querySelectorAll('.adelanto-check:checked').forEach(function(input){ deductions += Number(input.dataset.cents || 0); });
        document.getElementById('adelantoTotal').textContent = '− ' + fmt.format(deductions / 100);
        document.getElementById('liquidacionTotal').textContent = fmt.format((gross - deductions) / 100);
    };
    document.querySelectorAll('.adelanto-check').forEach(function(input){ input.addEventListener('change', update); });
})();
</script>
<?php elseif ($vendedorId > 0 && !$config): ?>
<div class="alert alert-warning">Este vendedor no tiene configuración salarial. <a href="/admin/empleados/<?= $vendedorId ?>">Configurar ahora</a></div>
<?php endif; ?>
