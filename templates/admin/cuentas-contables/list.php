<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0">Cuentas contables</h4>
        <p class="text-muted small mb-0">Cuentas principales (<code>contable</code>) y subcuentas (<code>contable1</code>) usadas en compras y gastos</p>
    </div>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#cuentaModal" onclick="openCuentaModal()"><i class="bi bi-plus-lg"></i> Nueva cuenta</button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead><tr><th style="width:60px">ID</th><th>Cuenta / Subcuenta</th><th style="width:200px"></th></tr></thead>
            <tbody>
            <?php if (!$grupos): ?><tr><td colspan="3" class="text-center text-muted">Sin cuentas</td></tr><?php else: foreach ($grupos as $g): ?>
                <tr class="table-light">
                    <td class="fw-bold"><?= (int)$g['idcta'] ?></td>
                    <td class="fw-bold"><?= htmlspecialchars($g['nomcta'] ?? '') ?></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-success" title="Agregar subcuenta" onclick='openSubcuentaModal(<?= (int)$g['idcta'] ?>)'><i class="bi bi-plus-lg"></i></button>
                        <button class="btn btn-sm btn-outline-secondary" title="Editar cuenta" onclick='editCuenta(<?= json_encode($g, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
                        <form method="post" action="/admin/cuentas-contables/eliminar-cuenta" style="display:inline" onsubmit="return confirm('¿Eliminar cuenta? Solo si no tiene subcuentas.')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="idcta" value="<?= (int)$g['idcta'] ?>" />
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php $subs = $porGrupo[(int)$g['idcta']] ?? []; ?>
                <?php if (!$subs): ?>
                    <tr><td></td><td colspan="2" class="text-muted small">Sin subcuentas</td></tr>
                <?php else: foreach ($subs as $s): ?>
                    <tr>
                        <td class="text-muted"><?= (int)$s['idcta1'] ?></td>
                        <td><span class="text-muted">↳</span> <?= htmlspecialchars($s['nomcta1'] ?? '') ?></td>
                        <td class="text-end">
                            <button class="btn btn-sm btn-outline-secondary" title="Editar subcuenta" onclick='editSubcuenta(<?= json_encode($s, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
                            <form method="post" action="/admin/cuentas-contables/eliminar-subcuenta" style="display:inline" onsubmit="return confirm('¿Eliminar subcuenta? Solo si no está usada en comprobantes o gastos.')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                                <input type="hidden" name="idcta1" value="<?= (int)$s['idcta1'] ?>" />
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; endif; ?>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="cuentaModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/cuentas-contables/guardar-cuenta" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="idcta" id="cuentaId" value="0" />
            <div class="modal-header"><h5 class="modal-title" id="cuentaModalTitle">Nueva cuenta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Nombre de la cuenta (nomcta) *</label><input type="text" name="nomcta" id="cuentaNombre" class="form-control form-control-sm" required maxlength="100" placeholder="Ej: Pérdidas, Mercadería menor" /></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-accent btn-sm">Guardar</button></div>
        </form>
    </div>
</div>

<div class="modal fade" id="subcuentaModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/cuentas-contables/guardar-subcuenta" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="idcta1" id="subcuentaId" value="0" />
            <div class="modal-header"><h5 class="modal-title" id="subcuentaModalTitle">Nueva subcuenta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Cuenta principal (idcta) *</label>
                    <select name="idcta" id="subcuentaGrupo" class="form-select form-select-sm" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($grupos as $g): ?>
                            <option value="<?= (int)$g['idcta'] ?>"><?= htmlspecialchars($g['nomcta'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2"><label class="form-label small">Nombre de la subcuenta (nomcta1) *</label><input type="text" name="nomcta1" id="subcuentaNombre" class="form-control form-control-sm" required maxlength="100" /></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-accent btn-sm">Guardar</button></div>
        </form>
    </div>
</div>

<script>
function openCuentaModal(){ document.getElementById('cuentaId').value='0'; document.getElementById('cuentaNombre').value=''; document.getElementById('cuentaModalTitle').textContent='Nueva cuenta'; }
function editCuenta(g){ document.getElementById('cuentaId').value=g.idcta||0; document.getElementById('cuentaNombre').value=g.nomcta||''; document.getElementById('cuentaModalTitle').textContent='Editar cuenta'; new bootstrap.Modal(document.getElementById('cuentaModal')).show(); }
function openSubcuentaModal(idcta){ document.getElementById('subcuentaId').value='0'; document.getElementById('subcuentaNombre').value=''; document.getElementById('subcuentaGrupo').value=idcta||''; document.getElementById('subcuentaModalTitle').textContent='Nueva subcuenta'; new bootstrap.Modal(document.getElementById('subcuentaModal')).show(); }
function editSubcuenta(s){ document.getElementById('subcuentaId').value=s.idcta1||0; document.getElementById('subcuentaNombre').value=s.nomcta1||''; document.getElementById('subcuentaGrupo').value=s.idcta||''; document.getElementById('subcuentaModalTitle').textContent='Editar subcuenta'; new bootstrap.Modal(document.getElementById('subcuentaModal')).show(); }
</script>
