<?php
$list = $list ?? [];
$sucursales = $sucursales ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Impresoras de tickets</h4>
        <p class="text-muted small">Una impresora por punto de venta: imprime sola cada factura que se emite</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/admin/impresion/spooler"><i class="bi bi-printer"></i> Spooler</a>
        <button class="btn btn-accent btn-sm" onclick="abrirModal(null)"><i class="bi bi-plus-lg"></i> Nueva</button>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Punto de venta</th>
                    <th>Sucursal</th>
                    <th>Token (agente local)</th>
                    <th>Activa</th>
                    <th style="width:130px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No hay impresoras. Agregá una por cada punto de venta.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $imp): ?>
                    <tr>
                        <td class="fw-semibold"><?= htmlspecialchars((string)($imp['nombre'] ?? '')) ?></td>
                        <td><span class="badge bg-primary">PV <?= (int)($imp['punto_venta'] ?? 0) ?></span></td>
                        <td class="small"><?= htmlspecialchars((string)($imp['sucursal_nombre'] ?? '—')) ?></td>
                        <td class="small"><code><?= htmlspecialchars(substr((string)($imp['token'] ?? ''), 0, 8)) ?>…</code></td>
                        <td>
                            <?php if (!empty($imp['activo'])): ?>
                                <span class="badge bg-success">Sí</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" onclick="abrirModal(<?= (int)$imp['id'] ?>)" title="Editar"><i class="bi bi-pencil"></i></button>
                            <form method="post" action="/admin/impresion/impresora/toggle" class="d-inline">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$imp['id'] ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-warning" title="Activar/Desactivar"><i class="bi bi-power"></i></button>
                            </form>
                            <form method="post" action="/admin/impresion/impresora/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar esta impresora?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$imp['id'] ?>" />
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
    Dejá abierta la página <a href="/admin/impresion/spooler"><strong>Spooler</strong></a> en la PC del punto de venta para impresión automática.
    Para un agente local dedicado usá el token completo de la impresora con la API (<code>GET /api/print/jobs?token=...</code>).
</div>

<div class="modal fade" id="modalImpresora" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/impresion/impresora/guardar" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" id="inputId" value="" />
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva impresora</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="inputNombre" class="form-control" required placeholder="Ej: Ticket mostrador" />
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Punto de venta (ARCA)</label>
                        <input type="number" name="punto_venta" id="inputPv" class="form-control" min="1" required />
                    </div>
                    <div class="col">
                        <label class="form-label">Sucursal</label>
                        <select name="sucursal_id" id="inputSucursal" class="form-select">
                            <option value="">—</option>
                            <?php foreach ($sucursales as $s): ?>
                            <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars((string)($s['nomsuc'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="activo" id="inputActivo" class="form-check-input" value="1" checked />
                    <label class="form-check-label" for="inputActivo">Activa</label>
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
const impresoras = <?= json_encode(array_map(function($imp) {
    return [
        'id' => (int)$imp['id'],
        'nombre' => $imp['nombre'] ?? '',
        'punto_venta' => (int)($imp['punto_venta'] ?? 0),
        'sucursal_id' => (int)($imp['sucursal_id'] ?? 0),
        'activo' => !empty($imp['activo']),
    ];
}, $list)) ?>;

function abrirModal(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalImpresora'));
    document.getElementById('inputId').value = '';
    document.getElementById('inputNombre').value = '';
    document.getElementById('inputPv').value = '';
    document.getElementById('inputSucursal').value = '';
    document.getElementById('inputActivo').checked = true;
    document.getElementById('modalTitle').textContent = 'Nueva impresora';

    if (id) {
        const imp = impresoras.find(x => x.id === id);
        if (imp) {
            document.getElementById('inputId').value = imp.id;
            document.getElementById('inputNombre').value = imp.nombre;
            document.getElementById('inputPv').value = imp.punto_venta;
            document.getElementById('inputSucursal').value = imp.sucursal_id || '';
            document.getElementById('inputActivo').checked = imp.activo;
            document.getElementById('modalTitle').textContent = 'Editar impresora';
        }
    }
    modal.show();
}
</script>
