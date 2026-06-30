<?php
$list = $list ?? [];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Proveedores</h4>
        <p class="text-muted small">Gestión de proveedores del sistema ERP (tabla <code>proveedo</code>)</p>
    </div>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#provModal" onclick="resetProvForm()">
        <i class="bi bi-plus-lg"></i> Nuevo
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Código</th>
                    <th>Razón social</th>
                    <th>CUIT</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Localidad</th>
                    <th>Productos</th>
                    <th>Estado</th>
                    <th style="width:90px">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="10" class="text-muted text-center">Sin proveedores.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $p): ?>
                        <tr class="<?= empty($p['activo']) ? 'table-light text-muted' : '' ?>">
                            <td><?= (int)($p['idprovee'] ?? 0) ?></td>
                            <td><code><?= htmlspecialchars((string)($p['codprove'] ?? '')) ?></code></td>
                            <td><strong><?= htmlspecialchars((string)($p['razon'] ?? '')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($p['cuit'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($p['tele'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($p['mail'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($p['Localidad'] ?? '-')) ?></td>
                            <td class="text-center"><?= (int)($p['product_count'] ?? 0) ?></td>
                            <td>
                                <?php if (!empty($p['activo'])): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Inactivo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" onclick='editProv(<?= json_encode($p, JSON_HEX_APOS) ?>)' data-bs-toggle="modal" data-bs-target="#provModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="/admin/proveedores/delete" style="display:inline" onsubmit="return confirm('Eliminar? Los productos se desvincularán.')">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($p['idprovee'] ?? 0) ?>" />
                                    <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="provModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/admin/proveedores/save">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                <input type="hidden" name="id" id="provId" value="0" />
                <div class="modal-header">
                    <h5 class="modal-title" id="provModalTitle">Nuevo proveedor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label">Código <span class="text-danger">*</span></label>
                            <input class="form-control" name="codprove" id="provCodigo" required />
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Razón social <span class="text-danger">*</span></label>
                            <input class="form-control" name="razon" id="provRazon" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">CUIT</label>
                            <input class="form-control" name="cuit" id="provCuit" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono</label>
                            <input class="form-control" name="tele" id="provTele" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Email</label>
                            <input class="form-control" name="mail" id="provMail" type="email" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Localidad</label>
                            <input class="form-control" name="localidad" id="provLocalidad" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Dirección</label>
                            <input class="form-control" name="direc" id="provDirec" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Cód. Postal</label>
                            <input class="form-control" name="codpost" id="provCodpost" />
                        </div>
                    </div>
                    <div class="form-check mt-3">
                        <input class="form-check-input" type="checkbox" name="activo" id="provActivo" checked />
                        <label class="form-check-label">Activo</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button class="btn btn-accent" type="submit">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetProvForm() {
    document.getElementById('provModalTitle').textContent = 'Nuevo proveedor';
    document.getElementById('provId').value = '0';
    ['provCodigo','provRazon','provCuit','provTele','provMail','provLocalidad','provDirec','provCodpost'].forEach(id => document.getElementById(id).value = '');
    document.getElementById('provActivo').checked = true;
}
function editProv(p) {
    document.getElementById('provModalTitle').textContent = 'Editar proveedor';
    document.getElementById('provId').value = p.idprovee;
    document.getElementById('provCodigo').value = p.codprove || '';
    document.getElementById('provRazon').value = p.razon || '';
    document.getElementById('provCuit').value = p.cuit || '';
    document.getElementById('provTele').value = p.tele || '';
    document.getElementById('provMail').value = p.mail || '';
    document.getElementById('provLocalidad').value = p.Localidad || '';
    document.getElementById('provDirec').value = p.direc || '';
    document.getElementById('provCodpost').value = p.codpost || '';
    document.getElementById('provActivo').checked = p.activo == 1;
}
</script>
