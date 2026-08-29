<div class="page-title d-flex justify-content-between align-items-center">
    <h2><i class="bi bi-building"></i> Sucursales</h2>
    <button class="btn btn-accent btn-sm" onclick="abrirModal(null)"><i class="bi bi-plus-lg"></i> Nueva</button>
</div>

<div class="card-dashboard">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0" id="tblSucursales">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Núm. Suc.</th>
                    <th>Ptos. Venta</th>
                    <th>Depósito</th>
                    <th>Activo</th>
                    <th style="width:80px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $s): ?>
                <tr>
                    <td class="text-muted"><?= (int)$s['id'] ?></td>
                    <td><?= htmlspecialchars((string)($s['nomsuc'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($s['direccion'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($s['telefono'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($s['email'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($s['numsuc'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($s['puntos_venta_csv'] ?? ($s['punto_venta'] ?? ''))) ?></td>
                    <td>
                        <?php
                        $depoId = (int)($s['iddepo'] ?? 0);
                        $depoNombre = '';
                        foreach ($depositos as $d) {
                            if ((int)$d['iddepo'] === $depoId) {
                                $depoNombre = $d['nomdepo'];
                                break;
                            }
                        }
                        echo htmlspecialchars($depoNombre ?: '—');
                        ?>
                    </td>
                    <td>
                        <?php if (!empty($s['activo'])): ?>
                            <span class="badge bg-success">Sí</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">No</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" onclick="abrirModal(<?= (int)$s['id'] ?>)"><i class="bi bi-pencil"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$list): ?>
                <tr><td colspan="10" class="text-center text-muted py-4">No hay sucursales registradas.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="modalSucursal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/sucursales/save" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="id" id="inputId" value="" />
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nomsuc" id="inputNomsuc" class="form-control" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="direccion" id="inputDireccion" class="form-control" />
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="telefono" id="inputTelefono" class="form-control" />
                    </div>
                    <div class="col">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="inputEmail" class="form-control" />
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Núm. Sucursal</label>
                        <input type="text" name="numsuc" id="inputNumsuc" class="form-control" />
                    </div>
                    <div class="col">
                        <label class="form-label">Puntos de venta</label>
                        <input type="text" name="puntos_venta" id="inputPuntosVenta" class="form-control" value="1" placeholder="Ej: 1, 3, 7" required />
                        <small class="text-muted">Podés cargar uno o varios, separados por coma. Deben ser únicos entre sucursales.</small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Depósito asociado</label>
                    <select name="iddepo" id="inputIddepo" class="form-select">
                        <option value="">Sin depósito</option>
                        <?php foreach ($depositos as $d): ?>
                        <option value="<?= (int)$d['iddepo'] ?>"><?= htmlspecialchars((string)($d['nomdepo'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
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
const sucursales = <?= json_encode(array_map(function($s) {
    return [
        'id' => (int)$s['id'],
        'nomsuc' => $s['nomsuc'] ?? '',
        'direccion' => $s['direccion'] ?? '',
        'telefono' => $s['telefono'] ?? '',
        'email' => $s['email'] ?? '',
        'numsuc' => $s['numsuc'] ?? '',
        'puntos_venta_csv' => (string)($s['puntos_venta_csv'] ?? ($s['punto_venta'] ?? '')),
        'iddepo' => (int)($s['iddepo'] ?? 0),
        'activo' => !empty($s['activo']),
    ];
}, $list)) ?>;

function abrirModal(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalSucursal'));
    document.getElementById('inputId').value = '';
    document.getElementById('inputNomsuc').value = '';
    document.getElementById('inputDireccion').value = '';
    document.getElementById('inputTelefono').value = '';
    document.getElementById('inputEmail').value = '';
    document.getElementById('inputNumsuc').value = '';
    document.getElementById('inputPuntosVenta').value = '1';
    document.getElementById('inputIddepo').value = '';
    document.getElementById('inputActivo').checked = true;
    document.getElementById('modalTitle').textContent = 'Nueva sucursal';

    if (id) {
        const s = sucursales.find(x => x.id === id);
        if (s) {
            document.getElementById('inputId').value = s.id;
            document.getElementById('inputNomsuc').value = s.nomsuc;
            document.getElementById('inputDireccion').value = s.direccion;
            document.getElementById('inputTelefono').value = s.telefono;
            document.getElementById('inputEmail').value = s.email;
            document.getElementById('inputNumsuc').value = s.numsuc;
            document.getElementById('inputPuntosVenta').value = s.puntos_venta_csv || '1';
            document.getElementById('inputIddepo').value = s.iddepo || '';
            document.getElementById('inputActivo').checked = s.activo;
            document.getElementById('modalTitle').textContent = 'Editar sucursal';
        }
    }
    modal.show();
}
</script>
