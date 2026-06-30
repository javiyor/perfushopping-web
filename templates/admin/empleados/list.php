<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Configuración de empleados</h4>
        <p class="text-muted small">Asigná tipo de liquidación, sueldo base y comisiones por marca</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/empleados/nuevo"><i class="bi bi-plus-lg"></i> Nueva config</a>
</div>

<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Empleado</th>
                    <th>Rol</th>
                    <th>Tipo</th>
                    <th class="text-end">Sueldo base</th>
                    <th class="text-end">Valor hora</th>
                    <th>CUIL</th>
                    <th>Estado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$empleados): ?>
                <tr><td colspan="8" class="text-muted text-center py-3">Sin configuraciones. Creá una nueva.</td></tr>
                <?php endif; ?>
                <?php foreach ($empleados as $emp): ?>
                <tr>
                    <td class="fw-semibold"><?= htmlspecialchars($emp['nombre'] ?? $emp['username'] ?? '') ?></td>
                    <td><?= htmlspecialchars($emp['rol'] ?? '') ?></td>
                    <td><?= htmlspecialchars($emp['tipo'] ?? '-') ?></td>
                    <td class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($emp['sueldo_base_cents'] ?? 0)) ?></td>
                    <td class="text-end"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($emp['valor_hora_cents'] ?? 0)) ?></td>
                    <td><?= htmlspecialchars($emp['cuil'] ?? '-') ?></td>
                    <td>
                        <span class="badge bg-<?= ($emp['activo'] ?? 0) && ($emp['user_activo'] ?? 0) ? 'success' : 'secondary' ?>">
                            <?= ($emp['activo'] ?? 0) && ($emp['user_activo'] ?? 0) ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td class="text-end">
                        <a class="btn btn-outline-primary btn-sm" href="/admin/empleados/<?= (int)$emp['admin_user_id'] ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>
</div>
