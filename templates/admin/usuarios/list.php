<?php
use Perfushopping\Web\Repo\AdminUserRepo;

$list = $list ?? [];
$rolOptions = AdminUserRepo::rolOptions();
$adminRol = $adminUser['rol'] ?? '';
$isSuper = $adminRol === 'superadmin';
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Usuarios administradores</h4>
        <p class="text-muted small">Gestión de accesos al panel</p>
    </div>
    <?php if ($isSuper): ?>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#userModal" onclick="resetForm()">
        <i class="bi bi-plus-lg"></i> Nuevo
    </button>
    <?php endif; ?>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Activo</th>
                    <th>Último login</th>
                    <?php if ($isSuper): ?>
                    <th style="width:100px">Acción</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="<?= $isSuper ? 8 : 7 ?>" class="text-muted text-center">Sin usuarios.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $u): ?>
                    <tr>
                        <td><?= (int)($u['id'] ?? 0) ?></td>
                        <td><strong><?= htmlspecialchars((string)($u['username'] ?? '')) ?></strong></td>
                        <td><?= htmlspecialchars((string)($u['nombre'] ?? '')) ?></td>
                        <td><?= htmlspecialchars((string)($u['email'] ?? '-')) ?></td>
                        <td>
                            <span class="admin-badge badge-<?= htmlspecialchars((string)($u['rol'] ?? '')) ?>"><?= htmlspecialchars($rolOptions[$u['rol'] ?? ''] ?? $u['rol'] ?? '') ?></span>
                        </td>
                        <td><?= !empty($u['activo']) ? '<span class="text-success"><i class="bi bi-check-circle"></i></span>' : '<span class="text-danger"><i class="bi bi-x-circle"></i></span>' ?></td>
                        <td class="small text-muted"><?= htmlspecialchars((string)($u['last_login_at'] ?? '-')) ?></td>
                        <?php if ($isSuper): ?>
                        <td>
                            <button class="btn btn-sm btn-outline-secondary" onclick='editUser(<?= json_encode($u, JSON_HEX_APOS) ?>)' data-bs-toggle="modal" data-bs-target="#userModal">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <?php if ((int)($u['id'] ?? 0) !== (int)($adminUser['id'] ?? 0)): ?>
                            <form method="post" action="/admin/usuarios/delete" style="display:inline" onsubmit="return confirm('Eliminar este admin?')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                <input type="hidden" name="id" value="<?= (int)($u['id'] ?? 0) ?>" />
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                            </form>
                            <?php endif; ?>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if ($isSuper): ?>
<!-- Modal -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/admin/usuarios/save">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                <input type="hidden" name="id" id="userId" value="0" />
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nuevo admin</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input class="form-control" name="username" id="userUsername" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input class="form-control" name="nombre" id="userNombre" required />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input class="form-control" name="email" id="userEmail" type="email" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select class="form-select" name="rol" id="userRol">
                            <?php foreach ($rolOptions as $val => $label): ?>
                                <option value="<?= htmlspecialchars($val) ?>"><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="activo" id="userActivo" checked />
                            <label class="form-check-label">Activo</label>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clave <small class="text-muted">(dejar vacío para no cambiar)</small></label>
                        <input class="form-control" type="password" name="password" id="userPassword" minlength="6" />
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
function resetForm() {
    document.getElementById('modalTitle').textContent = 'Nuevo admin';
    document.getElementById('userId').value = '0';
    document.getElementById('userUsername').value = '';
    document.getElementById('userNombre').value = '';
    document.getElementById('userEmail').value = '';
    document.getElementById('userRol').value = 'ventas';
    document.getElementById('userActivo').checked = true;
    document.getElementById('userPassword').value = '';
    document.getElementById('userUsername').readOnly = false;
}
function editUser(u) {
    document.getElementById('modalTitle').textContent = 'Editar admin';
    document.getElementById('userId').value = u.id;
    document.getElementById('userUsername').value = u.username || '';
    document.getElementById('userNombre').value = u.nombre || '';
    document.getElementById('userEmail').value = u.email || '';
    document.getElementById('userRol').value = u.rol || 'ventas';
    document.getElementById('userActivo').checked = u.activo == 1;
    document.getElementById('userPassword').value = '';
    document.getElementById('userUsername').readOnly = true;
}
</script>
<?php endif; ?>
