<?php
$turnosPresentes = $turnosPresentes ?? [];
$horasManuales = (float)($horasManuales ?? 0);
$horasTurnos = (float)($horasTurnos ?? 0);
$turnosIncluidos = (int)($turnosIncluidos ?? 0);
?>
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
                 <span>Horas manuales/adicionales</span>
                 <span class="badge bg-secondary fs-6">Manual: <?= htmlspecialchars((string)$horasManuales) ?> hs</span>
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
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$horas): ?>
                        <tr><td colspan="5" class="text-muted text-center py-3">Sin horas registradas este período.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($horas as $h): ?>
                        <tr>
                            <td><?= htmlspecialchars($h['fecha'] ?? '') ?></td>
                            <td class="fw-bold"><?= htmlspecialchars((string)$h['horas']) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars($h['concepto'] ?? '-') ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($h['created_by'] ?? '-')) ?></td>
                            <td class="text-end">
                                <button class="btn btn-sm btn-outline-primary py-0 px-1" type="button" title="Modificar horas"
                                    data-id="<?= (int)$h['id'] ?>"
                                    data-fecha="<?= htmlspecialchars((string)$h['fecha'], ENT_QUOTES) ?>"
                                    data-horas="<?= htmlspecialchars((string)$h['horas'], ENT_QUOTES) ?>"
                                    data-concepto="<?= htmlspecialchars((string)($h['concepto'] ?? ''), ENT_QUOTES) ?>"
                                    onclick="editarHoras(this)"><i class="bi bi-pencil"></i></button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Turnos con asistencia marcada (4 horas por turno)</span>
                <span class="badge bg-info"><?= $turnosIncluidos ?> turno(s) computados · <?= htmlspecialchars((string)$horasTurnos) ?> h</span>
            </div>
            <div class="card-body p-0">
                <?php if ($horasManuales > 0): ?><div class="small text-muted px-3 py-2">Los turnos de fechas con horas ingresadas manualmente se muestran aquí, pero no se vuelven a sumar.</div><?php endif; ?>
                <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>Fecha</th><th>Turno</th><th>Sucursal</th><th class="text-end">Horas</th></tr></thead>
                    <tbody>
                        <?php if (!$turnosPresentes): ?>
                        <tr><td colspan="4" class="text-muted text-center py-3">No hay turnos marcados como presente en este período.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($turnosPresentes as $turno): ?>
                        <tr>
                            <td><?= htmlspecialchars($turno['fecha'] ?? '') ?></td>
                            <td><?= ($turno['turno'] ?? '') === 'manana' ? 'Mañana' : 'Tarde' ?></td>
                            <td><?= htmlspecialchars($turno['sucursal_nombre'] ?? ('Sucursal #' . (int)($turno['sucursal_id'] ?? 0))) ?></td>
                            <td class="text-end fw-semibold"><?= htmlspecialchars((string)($turno['horas'] ?? 4)) ?> h</td>
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
                    <span>Horas por turnos:</span>
                    <strong><?= htmlspecialchars((string)$horasTurnos) ?> hs</strong>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Horas manuales/adicionales:</span>
                    <strong><?= htmlspecialchars((string)$horasManuales) ?> hs</strong>
                </div>
                <div class="d-flex justify-content-between small">
                    <span>Total horas trabajadas:</span>
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

<?php if ($vendedorId > 0 && $config): ?>
<div class="modal fade" id="modalEditarHoras" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/admin/empleados/horas/actualizar">
                <div class="modal-header">
                    <h5 class="modal-title">Modificar horas cargadas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="id" id="editarHorasId" />
                    <input type="hidden" name="admin_user_id" value="<?= $vendedorId ?>" />
                    <input type="hidden" name="periodo_actual" value="<?= htmlspecialchars($periodo) ?>" />
                    <div class="mb-3">
                        <label class="form-label small">Fecha</label>
                        <input class="form-control" type="date" name="fecha" id="editarHorasFecha" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small">Horas</label>
                        <input class="form-control" type="number" name="horas" id="editarHorasCantidad" min="0.5" max="24" step="0.5" required />
                    </div>
                    <div>
                        <label class="form-label small">Concepto</label>
                        <input class="form-control" name="concepto" id="editarHorasConcepto" maxlength="200" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-accent">Guardar cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
function editarHoras(button) {
    document.getElementById('editarHorasId').value = button.dataset.id || '';
    document.getElementById('editarHorasFecha').value = button.dataset.fecha || '';
    document.getElementById('editarHorasCantidad').value = button.dataset.horas || '';
    document.getElementById('editarHorasConcepto').value = button.dataset.concepto || '';
    bootstrap.Modal.getOrCreateInstance(document.getElementById('modalEditarHoras')).show();
}
</script>
<?php endif; ?>
