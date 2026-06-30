<?php
$list = $list ?? [];
$q = (string)($q ?? '');
$codepar = (int)($codepar ?? 0);
$stockFilter = (string)($stockFilter ?? '');
$departamentos = $departamentos ?? [];
$stockFilters = ['' => 'Todos', 'sin_stock' => 'Sin stock', 'bajo_stock' => 'Stock bajo (≤5)', 'con_stock' => 'Con stock (>5)'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Stock</h4>
        <p class="text-muted small">Control de inventario</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/stock/ajuste"><i class="bi bi-pencil-square"></i> Ajuste manual</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/stock" class="row g-2">
            <div class="col-lg-4">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar producto..." />
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="codepar">
                    <option value="0">Todos los dptos.</option>
                    <?php foreach ($departamentos as $d): ?>
                        <option value="<?= (int)$d['codepar'] ?>" <?= $codepar === (int)$d['codepar'] ? 'selected' : '' ?>><?= htmlspecialchars($d['nomdepar'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="stock">
                    <?php foreach ($stockFilters as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $stockFilter === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
            <div class="col-lg-2">
                <?php if ($q !== '' || $codepar > 0 || $stockFilter !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/stock">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0" style="font-size:13px">
            <thead>
                <tr>
                    <th style="width:40px"></th>
                    <th>Producto</th>
                    <th>Código</th>
                    <th>Departamento</th>
                    <th>Variantes</th>
                    <th class="text-end">Precio</th>
                    <th class="text-end">Costo</th>
                    <th class="text-center">Stock</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="9" class="text-muted text-center">Sin productos.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $p): ?>
                        <?php $stock = (int)($p['stocact'] ?? 0); ?>
                        <tr>
                            <td>
                                <?php if ($p['imagen'] ?? ''): ?>
                                    <img src="<?= htmlspecialchars(\Perfushopping\Web\Support\Format::uploadUrl((string)$p['imagen'])) ?>" style="width:32px;height:32px;object-fit:cover;border-radius:4px" alt="" />
                                <?php else: ?>
                                    <span class="text-muted"><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars((string)($p['produ'] ?? '-')) ?></strong></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($p['codprodu'] ?? '')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['nomdepar'] ?? '-')) ?></td>
                            <td class="text-center"><?= (int)($p['variantes'] ?? 0) ?></td>
                            <td class="text-end small">$<?= number_format((float)($p['precio'] ?? 0), 2, ',', '.') ?></td>
                            <td class="text-end small text-muted">$<?= number_format((float)($p['precomp'] ?? 0), 2, ',', '.') ?></td>
                            <td class="text-center">
                                <?php if ($stock <= 0): ?>
                                    <span class="badge bg-danger">0</span>
                                <?php elseif ($stock <= 5): ?>
                                    <span class="badge bg-warning text-dark"><?= $stock ?></span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $stock ?></span>
                                <?php endif; ?>
                            </td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/stock/<?= (int)($p['idprodu'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
