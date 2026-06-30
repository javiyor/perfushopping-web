<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$estado = (string)($estado ?? '');
$estados = ['' => 'Todos', 'pendiente' => 'Pendiente', 'aprobada' => 'Aprobada', 'recibida' => 'Recibida', 'anulada' => 'Anulada'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Órdenes de compra</h4>
        <p class="text-muted small">Pedidos a proveedores</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/ordenes-compra/nueva"><i class="bi bi-plus-lg"></i> Nueva orden</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/ordenes-compra" class="row g-2">
            <div class="col-lg-6">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por código o proveedor" />
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="estado">
                    <?php foreach ($estados as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $estado === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
            <div class="col-lg-2">
                <?php if ($q !== '' || $estado !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/ordenes-compra">Limpiar</a>
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
                    <th>Código</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th>Fecha estimada</th>
                    <th>Items</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th>Creado por</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="9" class="text-muted text-center">Sin órdenes de compra.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $o): ?>
                        <tr class="<?= $o['estado'] === 'anulada' ? 'table-light text-muted' : '' ?>">
                            <td><strong><?= htmlspecialchars((string)($o['codigo'] ?? '')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($o['proveedor_nombre'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($o['fecha'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($o['fecha_estimada'] ?? '-')) ?></td>
                            <td class="text-center"><?= (int)($o['items_count'] ?? 0) ?></td>
                            <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($o['total_cents'] ?? 0)) ?></td>
                            <td>
                                <?php $badge = ['pendiente' => 'warning', 'aprobada' => 'success', 'recibida' => 'primary', 'anulada' => 'secondary']; ?>
                                <span class="badge bg-<?= $badge[$o['estado'] ?? 'pendiente'] ?? 'secondary' ?>"><?= htmlspecialchars($o['estado'] ?? 'pendiente') ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($o['created_by_nombre'] ?? '-')) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/ordenes-compra/<?= (int)($o['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
