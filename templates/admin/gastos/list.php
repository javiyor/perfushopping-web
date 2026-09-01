<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Gastos varios</h4>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#gastoModal"><i class="bi bi-plus-lg"></i> Nuevo gasto</button>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2" method="get" action="/admin/gastos">
            <div class="col-md-4"><input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" placeholder="Buscar por descripción o cuenta" /></div>
            <div class="col-md-3"><input class="form-control form-control-sm" type="date" name="desde" value="<?= htmlspecialchars($desde ?? $_GET['desde'] ?? $today ?? date('Y-m-d')) ?>" /></div>
            <div class="col-md-3"><input class="form-control form-control-sm" type="date" name="hasta" value="<?= htmlspecialchars($hasta ?? $_GET['hasta'] ?? $today ?? date('Y-m-d')) ?>" /></div>
            <div class="col-md-2 d-flex gap-1"><button class="btn btn-sm btn-outline-secondary flex-fill" type="submit">Filtrar</button><a class="btn btn-sm btn-outline-secondary" href="/admin/gastos" title="Ver todos"><i class="bi bi-x-lg"></i></a></div>
        </form>
        <?php if (!empty($desde) && !empty($hasta) && $desde === $hasta): ?><div class="small text-muted mt-1">Mostrando gastos del día <?= htmlspecialchars($desde) ?> — <a href="/admin/gastos">Ver todos</a></div><?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead><tr><th>Fecha</th><th>Cuenta</th><th>Descripción</th><th class="text-end">Importe</th><th>Forma pago</th><th>Banco/Caja</th><th>Usuario</th></tr></thead>
            <tbody>
            <?php if (!$list): ?><tr><td colspan="7" class="text-center text-muted">Sin gastos</td></tr><?php else: foreach ($list as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['fecha'] ?? '') ?></td>
                    <td class="small"><?= htmlspecialchars(($g['cuenta_grupo'] ?? '').' / '.($g['cuenta_nombre'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($g['descripcion'] ?? '') ?></td>
                    <td class="text-end fw-bold"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($g['importe_cents'] ?? 0)) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($g['forma_pago'] ?? '') ?></span></td>
                    <td class="small"><?= htmlspecialchars($g['banco_nombre'] ?? ($g['caja_destino'] ?? '')) ?> <?= htmlspecialchars($g['numero_cheque'] ?? '') ?></td>
                    <td class="small"><?= htmlspecialchars($g['created_by_nombre'] ?? '') ?></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="gastoModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/gastos/guardar" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <div class="modal-header"><h5 class="modal-title">Nuevo gasto</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Fecha</label>
                    <input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="mb-2">
                    <label class="form-label small">Cuenta contable</label>
                    <select name="idcta1" class="form-select form-select-sm" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($cuentas as $c): ?>
                            <option value="<?= (int)$c['idcta1'] ?>"><?= htmlspecialchars($c['nomcta'].' / '.$c['nomcta1']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Descripción</label>
                    <input type="text" name="descripcion" class="form-control form-control-sm" required />
                </div>
                <div class="mb-2">
                    <label class="form-label small">Importe</label>
                    <input type="number" step="0.01" name="importe" class="form-control form-control-sm" required />
                </div>
                <div class="mb-2">
                    <label class="form-label small">Forma de pago</label>
                    <select name="forma_pago" id="gastoForma" class="form-select form-select-sm" onchange="onGastoForma()">
                        <option value="efectivo">Efectivo</option>
                        <option value="transferencia">Transferencia</option>
                        <option value="cheque">Cheque</option>
                    </select>
                </div>
                <div class="mb-2" id="gastoCajaBox">
                    <label class="form-label small">Pagar desde</label>
                    <select name="caja_destino" class="form-select form-select-sm">
                        <option value="chica">Caja chica (punto de venta)</option>
                        <option value="general">Caja general</option>
                    </select>
                </div>
                <div class="mb-2" id="gastoBancoBox" style="display:none">
                    <label class="form-label small">Banco</label>
                    <select name="banco_cuenta_id" class="form-select form-select-sm">
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['banco'].' - '.$b['numero_cuenta']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="gastoChequeBox" style="display:none">
                    <div class="mb-2"><label class="form-label small">Banco emisor</label><input type="text" name="banco_emisor" class="form-control form-control-sm" /></div>
                    <div class="row g-2 mb-2">
                        <div class="col-6"><label class="form-label small">Número cheque</label><input type="text" name="numero_cheque" class="form-control form-control-sm" /></div>
                        <div class="col-6"><label class="form-label small">Vencimiento</label><input type="date" name="fecha_vencimiento" class="form-control form-control-sm" /></div>
                    </div>
                    <div class="mb-2"><label class="form-label small">Titular</label><input type="text" name="titular" class="form-control form-control-sm" /></div>
                </div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-accent btn-sm">Guardar</button></div>
        </form>
    </div>
</div>

<script>
function onGastoForma(){
    const f=document.getElementById('gastoForma').value;
    document.getElementById('gastoCajaBox').style.display = (f==='efectivo' ? '' : 'none');
    document.getElementById('gastoBancoBox').style.display = (f!=='efectivo' ? '' : 'none');
    document.getElementById('gastoChequeBox').style.display = (f==='cheque' ? '' : 'none');
}
</script>
