<?php
$list = $list ?? [];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Departamentos</h4>
        <p class="text-muted small">Categorías de productos del sistema ERP (tabla <code>departa</code>)</p>
    </div>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#deptoModal" onclick="resetDeptoForm()">
        <i class="bi bi-plus-lg"></i> Nuevo
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Nombre</th>
                    <th>Productos</th>
                    <th style="width:90px">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="4" class="text-muted text-center">Sin departamentos.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $d): ?>
                        <tr>
                            <td><?= (int)($d['codepar'] ?? 0) ?></td>
                            <td><strong><?= htmlspecialchars((string)($d['nomdepar'] ?? '')) ?></strong></td>
                            <td><?= (int)($d['product_count'] ?? 0) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-secondary" onclick='editDepto(<?= json_encode($d, JSON_HEX_APOS) ?>)' data-bs-toggle="modal" data-bs-target="#deptoModal">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <form method="post" action="/admin/departamentos/delete" style="display:inline" onsubmit="return confirm('Eliminar? Los productos se desvincularán.')">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                    <input type="hidden" name="id" value="<?= (int)($d['codepar'] ?? 0) ?>" />
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

<div class="modal fade" id="deptoModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post" action="/admin/departamentos/save">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                <input type="hidden" name="id" id="deptoId" value="0" />
                <div class="modal-header">
                    <h5 class="modal-title" id="deptoModalTitle">Nuevo departamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input class="form-control" name="nombre" id="deptoNombre" required />
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
function resetDeptoForm() {
    document.getElementById('deptoModalTitle').textContent = 'Nuevo departamento';
    document.getElementById('deptoId').value = '0';
    document.getElementById('deptoNombre').value = '';
}
function editDepto(d) {
    document.getElementById('deptoModalTitle').textContent = 'Editar departamento';
    document.getElementById('deptoId').value = d.codepar;
    document.getElementById('deptoNombre').value = d.nomdepar || '';
}
</script>
