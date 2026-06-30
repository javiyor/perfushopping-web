<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Horas trabajadas</h4>
        <p class="text-muted small">Registrá horas por vendedor y período</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/empleados">Volver</a>
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

<?php if ($vendedorId > 0 && $config): ?>
<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                <span>Registro de horas</span>
                <span class="badge bg-info fs-6">Total: <?= htmlspecialchars((string)$totalHoras) ?> hs</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Horas</th>
                            <th>Concepto</th>
                            <th>Cargó</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$horas): ?>
                        <tr><td colspan="4" class="text-muted text-center py-3">Sin horas registradas este período.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($horas as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['fecha'] ?? '') ?></td>
                            <td class="fw-bold"><?= htmlspecialchars((string)$h['horas']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($h['concepto'] ?? '-') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($h['created_by'] ?? '-')) ?></td>
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
            <div class="card-header bg-white fw-semibold">Cargar horas</div>
            <div class="card-body">
                <form method="post" action="/admin/empleados/horas/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="admin_user_id" value="<?= $vendedorId ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fecha</label>
                        <input class="form-control form-control-sm" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Horas</label>
                        <input class="form-control form-control-sm" type="number" name="horas" step="0.5" min="0.5" max="24" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Concepto</label>
                        <input class="form-control form-control-sm" name="concepto" placeholder="Ej: Turno mañana" />
                    </div>
                    <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-plus-lg"></i> Registrar horas</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Resumen</div>
            <div class="card-body">
                <div class="d-flex justify-content-between small">
                    <span>Total horas:</span>
                    <strong><?= htmlspecialchars((string)$totalHoras) ?> hs</strong>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Valor hora:</span>
                    <strong><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($config['valor_hora_cents'] ?? 0)) ?></strong>
                </div>
                <hr />
                <div class="d-flex justify-content-between fw-bold">
                    <span>Subtotal horas:</span>
                    <span><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)round($totalHoras * (int)($config['valor_hora_cents'] ?? 0))) ?></span>
                </div>
            </div>
        </div>
    </div>
</div>
<?php elseif ($vendedorId > 0 && !$config): ?>
<div class="alert alert-warning">Este vendedor no tiene configuración salarial. <a href="/admin/empleados/<?= $vendedorId ?>">Configurar ahora</a></div>
<?php endif; ?>
