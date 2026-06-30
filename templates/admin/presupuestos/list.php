<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$estado = (string)($estado ?? '');
$estados = ['' => 'Todos', 'pendiente' => 'Pendiente', 'aprobado' => 'Aprobado', 'rechazado' => 'Rechazado', 'vencido' => 'Vencido'];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Presupuestos</h4>
        <p class="text-muted small">Cotizaciones y presupuestos a clientes</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/presupuestos/nuevo"><i class="bi bi-plus-lg"></i> Nuevo</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/presupuestos" class="row g-2">
            <div class="col-lg-5">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por código, cliente o CUIT" />
            </div>
            <div class="col-lg-3">
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
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/presupuestos">Limpiar</a>
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
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Válido hasta</th>
                    <th>Items</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th>Creado por</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="9" class="text-muted text-center">Sin presupuestos.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $p): ?>
                        <?php
                            $now = new DateTime();
                            $venc = ($p['valido_hasta'] ?? '') ? new DateTime($p['valido_hasta']) : null;
                            $vencido = $venc && $venc < $now && $p['estado'] === 'pendiente';
                            if ($vencido && $p['estado'] === 'pendiente') {
                                (new \Perfushopping\Web\Repo\PresupuestoRepo())->updateEstado((int)$p['id'], 'vencido');
                                $p['estado'] = 'vencido';
                            }
                        ?>
                        <tr class="<?= $p['estado'] === 'rechazado' || $p['estado'] === 'vencido' ? 'table-light text-muted' : '' ?>">
                            <td><strong><?= htmlspecialchars((string)($p['codigo'] ?? '')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($p['cliente_nombre'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['fecha'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['valido_hasta'] ?? '-')) ?></td>
                            <td class="text-center"><?= (int)($p['items_count'] ?? 0) ?></td>
                            <td class="text-end fw-bold"><?= htmlspecialchars(Format::moneyFromCents((int)($p['total_cents'] ?? 0))) ?></td>
                            <td>
                                <?php $badge = ['pendiente' => 'warning', 'aprobado' => 'success', 'rechazado' => 'danger', 'vencido' => 'secondary']; ?>
                                <span class="badge bg-<?= $badge[$p['estado'] ?? 'pendiente'] ?? 'secondary' ?>"><?= htmlspecialchars($p['estado'] ?? 'pendiente') ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($p['created_by_nombre'] ?? '-')) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/presupuestos/<?= (int)($p['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
