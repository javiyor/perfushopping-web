<?php
$list = $list ?? [];
$q = (string)($q ?? '');
$tipo = (string)($tipo ?? '');
$estado = (string)($estado ?? '');
$tipos = ['' => 'Todos', 'salida' => 'Salida', 'entrada' => 'Entrada'];
$estados = ['' => 'Todos', 'pendiente' => 'Pendiente', 'completado' => 'Completado', 'anulado' => 'Anulado'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Remitos</h4>
        <p class="text-muted small">Remitos de entrada y salida</p>
    </div>
    <div class="dropdown">
        <button class="btn btn-accent btn-sm dropdown-toggle" data-bs-toggle="dropdown"><i class="bi bi-plus-lg"></i> Nuevo</button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="/admin/remitos/nuevo?tipo=salida"><i class="bi bi-box-arrow-right"></i> Remito de salida</a></li>
            <li><a class="dropdown-item" href="/admin/remitos/nuevo?tipo=entrada"><i class="bi bi-box-arrow-in-left"></i> Remito de entrada</a></li>
        </ul>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/remitos" class="row g-2">
            <div class="col-lg-4">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por código o cliente/proveedor" />
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="tipo">
                    <?php foreach ($tipos as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $tipo === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
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
                <?php if ($q !== '' || $tipo !== '' || $estado !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/remitos">Limpiar</a>
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
                    <th>Tipo</th>
                    <th>Cliente / Proveedor</th>
                    <th>Fecha</th>
                    <th>Items</th>
                    <th>Estado</th>
                    <th>Creado por</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin remitos.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $r): ?>
                        <tr class="<?= $r['estado'] === 'anulado' ? 'table-light text-muted' : '' ?>">
                            <td><strong><?= htmlspecialchars((string)($r['codigo'] ?? '')) ?></strong></td>
                            <td>
                                <?php if (($r['tipo'] ?? '') === 'entrada'): ?>
                                    <span class="badge bg-info text-dark"><i class="bi bi-box-arrow-in-left"></i> Entrada</span>
                                <?php else: ?>
                                    <span class="badge bg-primary"><i class="bi bi-box-arrow-right"></i> Salida</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($r['cliente_nombre'] ?: $r['proveedor_nombre'] ?: '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($r['fecha'] ?? '-')) ?></td>
                            <td class="text-center"><?= (int)($r['items_count'] ?? 0) ?></td>
                            <td>
                                <?php $badge = ['pendiente' => 'warning', 'completado' => 'success', 'anulado' => 'secondary']; ?>
                                <span class="badge bg-<?= $badge[$r['estado'] ?? 'pendiente'] ?? 'secondary' ?>"><?= htmlspecialchars($r['estado'] ?? 'pendiente') ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($r['created_by_nombre'] ?? '-')) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/remitos/<?= (int)($r['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
