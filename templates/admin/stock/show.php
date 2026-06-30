<?php
$producto = $producto ?? null;
$variantes = $variantes ?? [];
$stockDepositos = $stockDepositos ?? [];
$movimientos = $movimientos ?? [];
if (!$producto):
?>
    <div class="alert alert-warning">Producto no encontrado.</div>
<?php return; endif;
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/stock">Stock</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars((string)($producto['produ'] ?? '')) ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars((string)($producto['produ'] ?? '')) ?></span>
                <span class="text-muted small"><?= htmlspecialchars((string)($producto['codprodu'] ?? '')) ?></span>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3 text-center">
                        <?php if ($producto['imagen'] ?? ''): ?>
                            <img src="<?= htmlspecialchars(\Perfushopping\Web\Support\Format::uploadUrl((string)$producto['imagen'])) ?>" style="max-width:100%;max-height:150px;border-radius:8px" alt="" />
                        <?php else: ?>
                            <div class="text-muted" style="font-size:60px"><i class="bi bi-image"></i></div>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-9">
                        <dl class="row small mb-0">
                            <dt class="col-sm-4">Código</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars((string)($producto['codprodu'] ?? '-')) ?></dd>
                            <dt class="col-sm-4">Código proveedor</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars((string)($producto['codprodup'] ?? '-')) ?></dd>
                            <dt class="col-sm-4">Departamento</dt>
                            <dd class="col-sm-8"><?= htmlspecialchars((string)($producto['nomdepar'] ?? '-')) ?></dd>
                            <dt class="col-sm-4">Precio venta</dt>
                            <dd class="col-sm-8 fw-bold">$<?= number_format((float)($producto['precio'] ?? 0), 2, ',', '.') ?></dd>
                            <dt class="col-sm-4">Costo</dt>
                            <dd class="col-sm-8">$<?= number_format((float)($producto['precomp'] ?? 0), 2, ',', '.') ?></dd>
                            <dt class="col-sm-4">Stock (ERP)</dt>
                            <dd class="col-sm-8">
                                <span class="fw-bold"><?= (int)($producto['stocact'] ?? 0) ?></span>
                                <span class="text-muted"> / Depósito: <?= (int)($producto['stocdep'] ?? 0) ?></span>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($variantes): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Variantes</span>
                <span class="badge bg-secondary"><?= count($variantes) ?></span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Variedad</th>
                            <th>Código de barras</th>
                            <th class="text-center">Stock actual</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($variantes as $v): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($v['nomgusto'] ?? '-')) ?></td>
                                <td class="small text-muted"><?= htmlspecialchars((string)($v['codscan'] ?? '-')) ?></td>
                                <td class="text-center">
                                    <?php $s = (int)($v['stockact'] ?? 0); ?>
                                    <span class="badge bg-<?= $s <= 0 ? 'danger' : ($s <= 5 ? 'warning text-dark' : 'success') ?>"><?= $s ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($stockDepositos): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Stock por depósito</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Depósito</th>
                            <th>Variedad</th>
                            <th class="text-center">Stock</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stockDepositos as $sd): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($sd['nomdepo'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($sd['nomgusto'] ?? 'Principal')) ?></td>
                                <td class="text-center"><?= (int)($sd['stock'] ?? 0) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Movimientos</span>
                <span class="badge bg-secondary"><?= count($movimientos) ?></span>
            </div>
            <?php if ($movimientos): ?>
            <div class="table-responsive" style="max-height:400px;overflow-y:auto">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Variedad</th>
                            <th class="text-center">Cantidad</th>
                            <th>Origen</th>
                            <th>Destino</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $m): ?>
                            <?php $cant = (int)($m['canti'] ?? 0); ?>
                            <tr>
                                <td class="small"><?= htmlspecialchars((string)($m['mov_fecha'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($m['nomgusto'] ?? '-')) ?></td>
                                <td class="text-center fw-bold <?= $cant > 0 ? 'text-success' : 'text-danger' ?>"><?= $cant > 0 ? '+' : '' ?><?= $cant ?></td>
                                <td class="small"><?= htmlspecialchars((string)($m['nom_depoh'] ?? '-')) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($m['nom_depod'] ?? '-')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="card-body text-muted text-center small">Sin movimientos registrados</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Resumen</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-6">Stock total (ERP)</dt>
                    <dd class="col-sm-6 text-end fw-bold"><?= (int)($producto['stocact'] ?? 0) ?></dd>
                    <dt class="col-sm-6">Stock depósito</dt>
                    <dd class="col-sm-6 text-end"><?= (int)($producto['stocdep'] ?? 0) ?></dd>
                    <dt class="col-sm-6">Variantes</dt>
                    <dd class="col-sm-6 text-end"><?= count($variantes) ?></dd>
                    <dt class="col-sm-6">En web</dt>
                    <dd class="col-sm-6 text-end"><?= ($producto['enweb'] ?? 0) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Depósitos disponibles</div>
            <div class="card-body">
                <?php
                $deposNombres = [];
                foreach ($stockDepositos as $sd) {
                    $deposNombres[$sd['nomdepo'] ?? ''] = true;
                }
                ?>
                <?php if ($deposNombres): ?>
                    <ul class="small mb-0">
                        <?php foreach (array_keys($deposNombres) as $nom): ?>
                            <li><?= htmlspecialchars($nom) ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php else: ?>
                    <p class="text-muted small mb-0">Sin datos de depósito</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
