<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($pageTitle ?? '') ?></h4>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/empleados">Volver</a>
</div>

<form method="post" action="/admin/empleados/guardar">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

    <div class="row g-3">
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Datos del empleado</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Vendedor</label>
                        <select class="form-select" name="admin_user_id" <?= $editId > 0 ? 'disabled' : 'required' ?>>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($vendedores as $v): ?>
                            <option value="<?= (int)$v['id'] ?>" <?= (int)$v['id'] === $editId ? 'selected' : '' ?>>
                                <?= htmlspecialchars($v['nombre'] ?? $v['username'] ?? '') ?> (<?= htmlspecialchars($v['rol'] ?? '') ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <?php if ($editId > 0): ?>
                        <input type="hidden" name="admin_user_id" value="<?= $editId ?>" />
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tipo de liquidación</label>
                        <select class="form-select" name="tipo" required>
                            <option value="fijo" <?= ($config['tipo'] ?? '') === 'fijo' ? 'selected' : '' ?>>Sueldo fijo mensual</option>
                            <option value="horas" <?= ($config['tipo'] ?? '') === 'horas' ? 'selected' : '' ?>>Por horas trabajadas</option>
                            <option value="comision" <?= ($config['tipo'] ?? '') === 'comision' ? 'selected' : '' ?>>Solo comisión</option>
                            <option value="mixto" <?= ($config['tipo'] ?? '') === 'mixto' ? 'selected' : '' ?>>Mixto (fijo + horas + comisión)</option>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Sueldo base mensual ($)</label>
                            <input class="form-control form-control-sm" name="sueldo_base" type="number" value="<?= (int)(($config['sueldo_base_cents'] ?? 0) / 100) ?>" min="0" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Valor hora ($)</label>
                            <input class="form-control form-control-sm" name="valor_hora" type="number" value="<?= (int)(($config['valor_hora_cents'] ?? 0) / 100) ?>" min="0" />
                        </div>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="activo" value="1" id="chkActivo" <?= ($config['activo'] ?? 1) ? 'checked' : '' ?> />
                        <label class="form-check-label small" for="chkActivo">Empleado activo</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Datos bancarios / CUIL</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">CUIL</label>
                        <input class="form-control form-control-sm" name="cuil" value="<?= htmlspecialchars($config['cuil'] ?? '') ?>" maxlength="20" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Banco</label>
                        <input class="form-control form-control-sm" name="banco" value="<?= htmlspecialchars($config['banco'] ?? '') ?>" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">CBU</label>
                        <input class="form-control form-control-sm" name="cbu" value="<?= htmlspecialchars($config['cbu'] ?? '') ?>" maxlength="22" />
                    </div>
                </div>
            </div>

            <?php if ($editId > 0): ?>
            <div class="card shadow-sm mt-3">
                <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                    <span>Comisiones por marca</span>
                    <button class="btn btn-sm btn-outline-primary" type="button" onclick="agregarComision()"><i class="bi bi-plus"></i></button>
                </div>
                <div class="card-body" id="comisionesWrap">
                    <?php if (!$comisiones): ?>
                    <p class="text-muted small mb-0">Sin comisiones configuradas.</p>
                    <?php endif; ?>
                    <?php foreach ($comisiones as $c): ?>
                    <div class="d-flex justify-content-between align-items-center mb-1 comision-row">
                        <span class="small"><?= htmlspecialchars($c['nomsub'] ?? 'Marca #' . $c['codsub']) ?>:</span>
                        <span class="fw-semibold small"><?= htmlspecialchars((string)$c['porcentaje']) ?>%</span>
                        <button class="btn btn-sm btn-outline-danger py-0 px-1" type="button" onclick="eliminarComision(<?= (int)$c['codsub'] ?>)">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="mt-3">
        <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Guardar configuración</button>
    </div>
</form>

<?php if ($editId > 0 && $marcas): ?>
<div id="comisionModal" class="modal fade" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Agregar comisión</h6>
                <button class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">Marca</label>
                    <select class="form-select form-select-sm" id="nuevaMarca">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($marcas as $m): ?>
                        <option value="<?= (int)$m['codsub'] ?>"><?= htmlspecialchars($m['nomsub'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small">% comisión</label>
                    <input class="form-control form-control-sm" type="number" id="nuevoPct" value="0" step="0.01" min="0" max="100" />
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-sm btn-accent" onclick="guardarComision()">Guardar</button>
            </div>
        </div>
    </div>
</div>

<script>
const editId = <?= $editId ?>;
const csrf = '<?= htmlspecialchars($csrf ?? '') ?>';

function agregarComision() {
    const m = new bootstrap.Modal(document.getElementById('comisionModal'));
    m.show();
}

function guardarComision() {
    const codsub = parseInt(document.getElementById('nuevaMarca').value);
    const pct = parseFloat(document.getElementById('nuevoPct').value);
    if (!codsub) { alert('Seleccioná una marca'); return; }
    if (pct <= 0) { alert('Ingresá un % válido'); return; }

    fetch('/admin/empleados/comisiones/guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ _csrf: csrf, admin_user_id: editId, codsub, porcentaje: pct }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) location.reload();
        else alert(res.error);
    });
}

function eliminarComision(codsub) {
    if (!confirm('Eliminar esta comisión?')) return;
    fetch('/admin/empleados/comisiones/eliminar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({ _csrf: csrf, admin_user_id: editId, codsub }),
    })
    .then(r => r.json())
    .then(res => {
        if (res.ok) location.reload();
        else alert(res.error);
    });
}
</script>
<?php endif; ?>
