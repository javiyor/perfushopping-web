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
                    <th>Núm. Suc.</th>
                    <th>Pto. Venta</th>
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
                    <td><?= htmlspecialchars((string)($s['numsuc'] ?? '')) ?></td>
                    <td><?= (int)($s['punto_venta'] ?? 0) ?></td>
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
                <tr><td colspan="7" class="text-center text-muted py-4">No hay sucursales registradas.</td></tr>
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
                <div class="row mb-3">
                    <div class="col">
                        <label class="form-label">Núm. Sucursal</label>
                        <input type="text" name="numsuc" id="inputNumsuc" class="form-control" />
                    </div>
                    <div class="col">
                        <label class="form-label">Punto de venta</label>
                        <input type="number" name="punto_venta" id="inputPuntoVenta" class="form-control" value="1" />
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
        'numsuc' => $s['numsuc'] ?? '',
        'punto_venta' => (int)($s['punto_venta'] ?? 0),
        'iddepo' => (int)($s['iddepo'] ?? 0),
        'activo' => !empty($s['activo']),
    ];
}, $list)) ?>;

function abrirModal(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalSucursal'));
    document.getElementById('inputId').value = '';
    document.getElementById('inputNomsuc').value = '';
    document.getElementById('inputNumsuc').value = '';
    document.getElementById('inputPuntoVenta').value = '1';
    document.getElementById('inputIddepo').value = '';
    document.getElementById('inputActivo').checked = true;
    document.getElementById('modalTitle').textContent = 'Nueva sucursal';

    if (id) {
        const s = sucursales.find(x => x.id === id);
        if (s) {
            document.getElementById('inputId').value = s.id;
            document.getElementById('inputNomsuc').value = s.nomsuc;
            document.getElementById('inputNumsuc').value = s.numsuc;
            document.getElementById('inputPuntoVenta').value = s.punto_venta;
            document.getElementById('inputIddepo').value = s.iddepo || '';
            document.getElementById('inputActivo').checked = s.activo;
            document.getElementById('modalTitle').textContent = 'Editar sucursal';
        }
    }
    modal.show();
}
</script>
