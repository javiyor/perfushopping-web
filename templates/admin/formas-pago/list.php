<?php
$list = $list ?? [];
$tipos = $tipos ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Formas de pago</h4>
        <p class="text-muted small">Métodos disponibles al facturar y su comportamiento en caja</p>
    </div>
    <button class="btn btn-accent btn-sm" onclick="abrirModal(null)"><i class="bi bi-plus-lg"></i> Nueva</button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Código</th>
                    <th>Comportamiento</th>
                    <th>Moneda</th>
                    <th>Orden</th>
                    <th>Activa</th>
                    <th style="width:130px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">No hay formas de pago.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $f): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars((string)($f['nombre'] ?? '')) ?></td>
                        <td><code><?= htmlspecialchars((string)($f['codigo'] ?? '')) ?></code></td>
                        <td class="small"><?= htmlspecialchars($tipos[$f['tipo'] ?? ''] ?? ($f['tipo'] ?? '')) ?></td>
                        <td class="small"><?= htmlspecialchars((string)($f['moneda'] ?? '—')) ?></td>
                        <td class="small"><?= (int)($f['orden'] ?? 0) ?></td>
                        <td>
                            <?php if (!empty($f['activo'])): ?>
                                <span class="badge bg-success">Sí</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" onclick="abrirModal(<?= (int)$f['id'] ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
                            <form method="post" action="/admin/formas-pago/toggle" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="<?= !empty($f['activo']) ? 'Desactivar' : 'Activar' ?>"><i class="bi bi-power"></i></button>
                            </form>
                            <form method="post" action="/admin/formas-pago/delete" class="d-inline" onsubmit="return confirm('¿Eliminar esta forma de pago?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="alert alert-info small mt-3 mb-0">
    <strong>Efectivo</strong> suma al efectivo de caja · <strong>Banco / transferencia</strong> suma a Transferencia/MP ·
    <strong>Tarjeta</strong> pide tarjeta, equipo y cupón · <strong>Cheque</strong> pide datos del cheque ·
    <strong>Cuenta corriente</strong> pide plazo · <strong>Moneda extranjera</strong> pide monto y cotización (se guarda el equivalente en pesos).
</div>

<div class="modal fade" id="modalFormaPago" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/formas-pago/save" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" id="inputId" value="" />
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva forma de pago</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="inputNombre" class="form-control" required placeholder="Ej: Billetera QR" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Código</label>
                    <input type="text" name="codigo" id="inputCodigo" class="form-control" required placeholder="Ej: billetera_qr" />
                    <small class="text-muted">Solo minúsculas, números y guion bajo. Se usa internamente.</small>
                </div>
                <div class="mb-3">
                    <label class="form-label">Comportamiento</label>
                    <select name="tipo" id="inputTipo" class="form-select" onchange="toggleMoneda()">
                        <?php foreach ($tipos as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>"><?= htmlspecialchars($l) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3" id="monedaBox" style="display:none">
                    <label class="form-label">Moneda (código de 3 letras)</label>
                    <input type="text" name="moneda" id="inputMoneda" class="form-control" maxlength="3" placeholder="Ej: USD" />
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Orden</label>
                        <input type="number" name="orden" id="inputOrden" class="form-control" value="0" />
                    </div>
                    <div class="col d-flex align-items-end">
                        <div class="form-check">
                            <input type="checkbox" name="activo" id="inputActivo" class="form-check-input" value="1" checked />
                            <label class="form-check-label" for="inputActivo">Activa</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
const formasPago = <?= json_encode(array_map(function($f) {
    return [
        'id' => (int)$f['id'],
        'codigo' => $f['codigo'] ?? '',
        'nombre' => $f['nombre'] ?? '',
        'tipo' => $f['tipo'] ?? '',
        'moneda' => $f['moneda'] ?? '',
        'orden' => (int)($f['orden'] ?? 0),
        'activo' => !empty($f['activo']),
    ];
}, $list), JSON_UNESCAPED_UNICODE) ?>;

function toggleMoneda() {
    document.getElementById('monedaBox').style.display =
        document.getElementById('inputTipo').value === 'moneda' ? '' : 'none';
}

function abrirModal(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalFormaPago'));
    document.getElementById('inputId').value = '';
    document.getElementById('inputNombre').value = '';
    document.getElementById('inputCodigo').value = '';
    document.getElementById('inputTipo').value = 'efectivo';
    document.getElementById('inputMoneda').value = '';
    document.getElementById('inputOrden').value = '0';
    document.getElementById('inputActivo').checked = true;
    document.getElementById('modalTitle').textContent = 'Nueva forma de pago';
    toggleMoneda();

    if (id) {
        const f = formasPago.find(x => x.id === id);
        if (f) {
            document.getElementById('inputId').value = f.id;
            document.getElementById('inputNombre').value = f.nombre;
            document.getElementById('inputCodigo').value = f.codigo;
            document.getElementById('inputTipo').value = f.tipo;
            document.getElementById('inputMoneda').value = f.moneda || '';
            document.getElementById('inputOrden').value = f.orden;
            document.getElementById('inputActivo').checked = f.activo;
            document.getElementById('modalTitle').textContent = 'Editar forma de pago';
            toggleMoneda();
        }
    }
    modal.show();
}
</script>
