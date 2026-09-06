<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Bancos</h4>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#bancoModal" onclick="openBancoModal()"><i class="bi bi-plus-lg"></i> Nuevo banco</button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead><tr><th>ID</th><th>Nombre</th><th>Número</th><th style="width:140px"></th></tr></thead>
            <tbody>
            <?php if (!$list): ?><tr><td colspan="4" class="text-center text-muted">Sin bancos</td></tr><?php else: foreach ($list as $b): ?>
                <tr>
                    <td><?= (int)$b['idban'] ?></td>
                    <td><?= htmlspecialchars($b['nombanc'] ?? '') ?></td>
                    <td><?= htmlspecialchars((string)($b['numbanc'] ?? '')) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-secondary" onclick='editBanco(<?= json_encode($b, JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="bi bi-pencil"></i></button>
                        <form method="post" action="/admin/bancos/delete" style="display:inline" onsubmit="return confirm('¿Eliminar banco?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="idban" value="<?= (int)$b['idban'] ?>" />
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="bancoModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/bancos/save" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="idban" id="bancoId" value="0" />
            <div class="modal-header"><h5 class="modal-title" id="bancoModalTitle">Nuevo banco</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                <div class="mb-2"><label class="form-label small">Nombre (nombanc) *</label><input type="text" name="nombanc" id="bancoNombre" class="form-control form-control-sm" required maxlength="45" /></div>
                <div class="mb-2"><label class="form-label small">Número (numbanc)</label><input type="number" name="numbanc" id="bancoNumero" class="form-control form-control-sm" /></div>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-accent btn-sm">Guardar</button></div>
        </form>
    </div>
</div>

<script>
function openBancoModal(){ document.getElementById('bancoId').value='0'; document.getElementById('bancoNombre').value=''; document.getElementById('bancoNumero').value=''; document.getElementById('bancoModalTitle').textContent='Nuevo banco'; }
function editBanco(b){ document.getElementById('bancoId').value=b.idban||0; document.getElementById('bancoNombre').value=b.nombanc||''; document.getElementById('bancoNumero').value=b.numbanc||''; document.getElementById('bancoModalTitle').textContent='Editar banco'; new bootstrap.Modal(document.getElementById('bancoModal')).show(); }
</script>
