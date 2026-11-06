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
        </form>
    </div>
</div>

<?php if ($vendedorId > 0 && $config && $resultado): ?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Ventas del período (facturas emitidas)</div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Comprobante</th>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th class="text-end">Total</th>
                            <th>ARCA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$facturas): ?>
                        <tr><td colspan="5" class="text-muted text-center py-3">Sin ventas facturadas en este período.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($facturas as $f): ?>
                        <tr>
                            <td><a href="/admin/facturas/<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['codigo'] ?? '') ?></a><div class="small text-muted"><?= htmlspecialchars($f['tipo_comprobante'] ?? '') ?></div></td>
                            <td><?= htmlspecialchars($f['fecha'] ?? '') ?></td>
                            <td class="small"><?= htmlspecialchars($f['cliente_nombre'] ?? '-') ?></td>
                            <td class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($f['total_cents'] ?? 0)) ?></td>
                            <td><code class="small"><?= htmlspecialchars($f['cae'] ?? '') ?></code><?= empty($f['cae']) ? '<div class="small text-warning">Pendiente</div>' : '' ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
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
                    <?php if (($resultado['adelanto_cents'] ?? 0) > 0): ?>
                    <dt class="col-sm-6">Adelantos desde la última liquidación pagada:</dt>
                    <dd class="col-sm-6 text-end text-danger">− <?= \Perfushopping\Web\Support\Format::moneyFromCents((int)$resultado['adelanto_cents']) ?></dd>
                    <?php endif; ?>
                </dl>
                <hr />
                <div class="d-flex justify-content-between fw-bold" style="font-size:20px">
                    <span>TOTAL:</span>
                    <span><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($resultado['total_cents'] ?? 0)) ?></span>
                </div>

                <?php if ($comisiones): ?>
                <hr />
                <p class="small fw-semibold mb-1">Comisiones por marca:</p>
                <?php foreach ($comisiones as $c): ?>
                <div class="d-flex justify-content-between small">
                    <span><?= htmlspecialchars($c['nomsub'] ?? 'Marca #' . $c['codsub']) ?>:</span>
                    <span><?= htmlspecialchars((string)$c['porcentaje']) ?>%</span>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>

                <form method="post" action="/admin/empleados/liquidar/guardar" class="mt-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="vendedor_id" value="<?= $vendedorId ?>" />
                    <input type="hidden" name="periodo" value="<?= htmlspecialchars($periodo) ?>" />
                    <button class="btn btn-accent w-100 py-2 fw-bold" type="submit" onclick="return confirm('Guardar esta liquidación?')">
                        <i class="bi bi-calculator"></i> Guardar liquidación
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php elseif ($vendedorId > 0 && !$config): ?>
<div class="alert alert-warning">Este vendedor no tiene configuración salarial. <a href="/admin/empleados/<?= $vendedorId ?>">Configurar ahora</a></div>
<?php endif; ?>
