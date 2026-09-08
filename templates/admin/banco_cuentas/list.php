<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Cuentas propias</h4>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#cuentaModal" onclick="openCuentaModal()"><i class="bi bi-plus-lg"></i> Nueva cuenta</button>
</div>

<div class="card shadow-sm mb-4">
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead><tr><th>ID</th><th>Banco</th><th>Tipo</th><th>Número</th><th>CBU</th><th>Titular</th><th>Saldo inicial</th><th>Activa</th><th style="width:120px"></th></tr></thead>
            <tbody>
            <?php if (!$list): ?><tr><td colspan="9" class="text-center text-muted">Sin cuentas</td></tr><?php else: foreach ($list as $c): ?>
                <tr>
                    <td><?= (int)$c['id'] ?></td>
                    <td><?= htmlspecialchars($c['banco'] ?? '') ?></td>
                    <td><?= htmlspecialchars($c['tipo_cuenta'] ?? '') ?></td>
                    <td><?= htmlspecialchars($c['numero_cuenta'] ?? '') ?></td>
                    <td class="small"><?= htmlspecialchars($c['cbu'] ?? '') ?></td>
                    <td><?= htmlspecialchars($c['titular'] ?? '') ?></td>
                    <td><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($c['saldo_inicial_cents'] ?? 0)) ?></td>
                    <td><?= !empty($c['activo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" onclick='editCuenta(<?= json_encode($c, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
                        <form method="post" action="/admin/banco-cuentas/delete" style="display:inline" onsubmit="return confirm('¿Eliminar cuenta?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>" />
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Cuenta predeterminada para Transferencias</div>
            <div class="card-body">
                <form method="post" action="/admin/banco-cuentas/cobro/save" class="d-flex gap-2 align-items-end">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                    <input type="hidden" name="tipo" value="transferencia" />
                    <div class="flex-fill">
                        <label class="form-label small">Cuenta</label>
                        <select name="banco_cuenta_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($list as $c): if(empty($c['activo'])) continue; ?>
                                <option value="<?= (int)$c['id'] ?>" <?= (int)($transferCuentaId ?? 0)===(int)$c['id']?'selected':'' ?>><?= htmlspecialchars($c['banco'].' - '.$c['numero_cuenta']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button class="btn btn-accent btn-sm" type="submit">Guardar</button>
                </form>
                <?php if ($transferCuentaId): ?><div class="small text-muted mt-2">Actual: <?= htmlspecialchars($transferCuentaId) ?></div><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Cuentas por Tarjeta</div>
            <div class="card-body">
                <form method="post" action="/admin/banco-cuentas/cobro/save" class="row g-2 align-items-end mb-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                    <input type="hidden" name="tipo" value="tarjeta" />
                    <div class="col-5">
                        <label class="form-label small">Tarjeta</label>
                        <select name="idtarje" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($tarjetas as $t): ?>
                                <option value="<?= (int)$t['idtarje'] ?>"><?= htmlspecialchars($t['nomtar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-5">
                        <label class="form-label small">Cuenta destino</label>
                        <select name="banco_cuenta_id" class="form-select form-select-sm" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($list as $c): if(empty($c['activo'])) continue; ?>
                                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['banco'].' - '.$c['numero_cuenta']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-2"><button class="btn btn-accent btn-sm w-100" type="submit">Guardar</button></div>
                </form>
                <div class="table-responsive">
                    <table class="table table-sm table-admin mb-0">
                        <thead><tr><th>Tarjeta</th><th>Cuenta</th><th></th></tr></thead>
                        <tbody>
                        <?php foreach ($cobros as $co): if($co['tipo']!=='tarjeta') continue; ?>
                            <tr>
                                <td><?= htmlspecialchars($co['nomtar'] ?? $co['idtarje']) ?></td>
                                <td><?= htmlspecialchars(($co['banco'] ?? '').' - '.($co['numero_cuenta'] ?? '')) ?></td>
                                <td>
                                    <form method="post" action="/admin/banco-cuentas/cobro/delete-tarjeta" style="display:inline" onsubmit="return confirm('¿Eliminar mapeo?')">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                                        <input type="hidden" name="idtarje" value="<?= (int)$co['idtarje'] ?>" />
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cuentaModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/banco-cuentas/save" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="id" id="cuentaId" value="0" />
            <div class="modal-header"><h5 class="modal-title" id="cuentaModalTitle">Nueva cuenta</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Banco *</label><input type="text" name="banco" id="cuentaBanco" class="form-control form-control-sm" required /></div>
                <div class="row g-2 mb-2">
                    <div class="col-6"><label class="form-label small">Tipo cuenta</label><select name="tipo_cuenta" id="cuentaTipo" class="form-select form-select-sm"><option value="corriente">Corriente</option><option value="ahorro">Ahorro</option></select></div>
                    <div class="col-6"><label class="form-label small">Activa</label><div class="form-check mt-2"><input type="checkbox" name="activo" id="cuentaActivo" class="form-check-input" checked /><label class="form-check-label small" for="cuentaActivo">Activa</label></div></div>
                </div>
                <div class="mb-2"><label class="form-label small">Número cuenta</label><input type="text" name="numero_cuenta" id="cuentaNumero" class="form-control form-control-sm" /></div>
                <div class="mb-2"><label class="form-label small">CBU</label><input type="text" name="cbu" id="cuentaCbu" class="form-control form-control-sm" /></div>
                <div class="mb-2"><label class="form-label small">Titular</label><input type="text" name="titular" id="cuentaTitular" class="form-control form-control-sm" /></div>
                <div class="mb-2"><label class="form-label small">Saldo inicial</label><input type="number" step="0.01" name="saldo_inicial" id="cuentaSaldo" class="form-control form-control-sm" value="0" /></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-accent btn-sm">Guardar</button></div>
        </form>
    </div>
</div>

<script>
function openCuentaModal(){ document.getElementById('cuentaId').value='0'; document.getElementById('cuentaBanco').value=''; document.getElementById('cuentaNumero').value=''; document.getElementById('cuentaCbu').value=''; document.getElementById('cuentaTitular').value=''; document.getElementById('cuentaSaldo').value='0'; document.getElementById('cuentaActivo').checked=true; document.getElementById('cuentaModalTitle').textContent='Nueva cuenta'; }
function editCuenta(c){ document.getElementById('cuentaId').value=c.id||0; document.getElementById('cuentaBanco').value=c.banco||''; document.getElementById('cuentaTipo').value=c.tipo_cuenta||'corriente'; document.getElementById('cuentaNumero').value=c.numero_cuenta||''; document.getElementById('cuentaCbu').value=c.cbu||''; document.getElementById('cuentaTitular').value=c.titular||''; document.getElementById('cuentaSaldo').value=((c.saldo_inicial_cents||0)/100).toFixed(2); document.getElementById('cuentaActivo').checked=!!c.activo; document.getElementById('cuentaModalTitle').textContent='Editar cuenta'; new bootstrap.Modal(document.getElementById('cuentaModal')).show(); }
</script>
