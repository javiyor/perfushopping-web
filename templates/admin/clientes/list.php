<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$customerCategories = [
    'none' => 'Sin categoría', 'peluquero' => 'Peluquero/a', 'cosmetologa' => 'Cosmetóloga',
    'esteticista' => 'Esteticista', 'manicura' => 'Manicura/o', 'masajista' => 'Masajista',
    'barbero' => 'Barbero/a', 'maquillador' => 'Maquillador/a', 'spa' => 'Spa / centro estético',
    'revendedor' => 'Revendedor/a', 'otro' => 'Otro profesional',
];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Clientes</h4>
        <p class="text-muted small">Usuarios registrados en la web con historial de compras</p>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/clientes" class="row g-2">
            <div class="col-lg-8">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, email o teléfono" />
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
            <div class="col-lg-2">
                <?php if ($q !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/clientes"><i class="bi bi-x-lg"></i> Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Categoría</th>
                    <th>Pedidos</th>
                    <th>Total gastado</th>
                    <th>Último pedido</th>
                    <th>Registro</th>
                    <th>Estado</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="11" class="text-muted text-center py-4">No se encontraron clientes.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $u): ?>
                        <?php $isBlocked = !empty($u['disabled_at']); ?>
                        <tr class="<?= $isBlocked ? 'table-light text-muted' : '' ?>">
                            <td><?= (int)($u['id'] ?? 0) ?></td>
                            <td>
                                <strong><?= htmlspecialchars((string)($u['name'] ?? 'Sin nombre')) ?></strong>
                                <?php if (($u['wholesale_status'] ?? '') === 'approved'): ?>
                                    <span class="badge bg-info">Mayorista</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($u['email'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($u['phone'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars($customerCategories[$u['customer_category'] ?? 'none'] ?? 'Sin categoría') ?></td>
                            <td class="text-center"><?= (int)($u['order_count'] ?? 0) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($u['total_spent_cents'] ?? 0))) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($u['last_order_at'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($u['created_at'] ?? '-')) ?></td>
                            <td>
                                <?php if ($isBlocked): ?>
                                    <span class="badge bg-secondary">Bloqueado</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary" href="/admin/clientes/<?= (int)($u['id'] ?? 0) ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($list && count($list) >= 60): ?>
        <div class="card-footer text-muted small text-center">Mostrando hasta 60 resultados. Refiná la búsqueda si no encontrás lo que buscás.</div>
    <?php endif; ?>
</div>
