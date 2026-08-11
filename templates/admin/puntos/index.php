<?php
$cuentas = $cuentas ?? [];
$q = (string)($q ?? '');
$pctGeneral = (float)($pctGeneral ?? 1);
$marcas = $marcas ?? [];
$productos = $productos ?? [];
$csrfToken = $csrf ?? '';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Puntos (Serviclub)</h4>
        <p class="text-muted small">1 punto = $1 de crédito. Se acumula 1% del importe + bonus por marca/producto.</p>
    </div>
</div>

<ul class="nav nav-tabs mb-3" id="puntosTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabCuentas" type="button">Cuentas</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabConfig" type="button">Configuración</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabMarcas" type="button">Bonus por marca</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabProductos" type="button">Bonus por producto</button></li>
</ul>

<div class="tab-content">
    <!-- Cuentas -->
    <div class="tab-pane fade show active" id="tabCuentas" role="tabpanel">
        <div class="card shadow-sm mb-3">
            <div class="card-body">
                <form method="get" action="/admin/puntos" class="row g-2">
                    <div class="col-lg-6">
                        <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar cliente por nombre o CUIT..." />
                    </div>
                    <div class="col-lg-2">
                        <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-admin table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Cliente</th>
                            <th>CUIT</th>
                            <th>Email</th>
                            <th class="text-end">Acumulados</th>
                            <th class="text-end">Usados</th>
                            <th class="text-end">Saldo</th>
                            <th style="width:50px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$cuentas): ?>
                            <tr><td colspan="7" class="text-muted text-center">Sin cuentas con puntos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($cuentas as $c): ?>
                                <?php $saldo = (int)($c['saldo_puntos'] ?? 0); ?>
                                <tr>
                                    <td><strong><?= htmlspecialchars($c['razon'] ?? '-') ?></strong></td>
                                    <td class="small"><?= htmlspecialchars((string)($c['cuit'] ?? '-')) ?></td>
                                    <td class="small"><?= htmlspecialchars((string)($c['email'] ?? '-')) ?></td>
                                    <td class="text-end"><?= number_format((int)($c['total_acumulado'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-end"><?= number_format((int)($c['total_usados'] ?? 0), 0, ',', '.') ?></td>
                                    <td class="text-end fw-bold <?= $saldo > 0 ? 'text-success' : '' ?>"><?= number_format($saldo, 0, ',', '.') ?></td>
                                    <td><a class="btn btn-sm btn-outline-secondary" href="/admin/puntos/<?= (int)($c['idclien'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Config -->
    <div class="tab-pane fade" id="tabConfig" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-gear"></i> Porcentaje general de acumulación</h6>
                <form method="post" action="/admin/puntos/config" class="row g-2 align-items-end">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                    <div class="col-lg-4">
                        <label class="form-label small">% sobre el importe de la compra</label>
                        <div class="input-group">
                            <input class="form-control" type="number" name="general_pct" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string)$pctGeneral) ?>" />
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    <div class="col-lg-3">
                        <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
                    </div>
                    <div class="col-12 small text-muted">Ejemplo: con 1%, una compra de $100.000 acumula 1.000 puntos (=$1.000 de crédito).</div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bonus por marca -->
    <div class="tab-pane fade" id="tabMarcas" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-tags"></i> Bonus extra por marca (subrubro)</h6>
                <div class="table-responsive">
                    <table class="table table-admin table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Marca</th>
                                <th class="text-end" style="width:180px">Bonus %</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($marcas as $m): ?>
                                <tr>
                                    <form method="post" action="/admin/puntos/marcas" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                                        <input type="hidden" name="codsub" value="<?= (int)($m['codsub'] ?? 0) ?>" />
                                        <td class="py-1" style="border:none"><?= htmlspecialchars($m['nomsub'] ?? '-') ?></td>
                                        <td style="border:none">
                                            <div class="input-group input-group-sm">
                                                <input class="form-control" type="number" name="porcentaje" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string)($m['porcentaje'] ?? '0')) ?>" style="width:90px" />
                                                <button class="btn btn-sm btn-accent" type="submit"><i class="bi bi-check-lg"></i></button>
                                            </div>
                                        </td>
                                        <td style="border:none">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" name="eliminar" value="1" title="Quitar bonus"><i class="bi bi-x-lg"></i></button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$marcas): ?>
                                <tr><td colspan="3" class="text-muted text-center">Sin marcas.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Bonus por producto -->
    <div class="tab-pane fade" id="tabProductos" role="tabpanel">
        <div class="card shadow-sm">
            <div class="card-body">
                <h6 class="fw-bold mb-3"><i class="bi bi-box-seam"></i> Bonus extra por producto</h6>
                <div class="table-responsive">
                    <table class="table table-admin table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Marca</th>
                                <th class="text-end" style="width:180px">Bonus %</th>
                                <th style="width:60px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($productos as $p): ?>
                                <tr>
                                    <form method="post" action="/admin/puntos/productos" class="d-flex align-items-center gap-2">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                                        <input type="hidden" name="idprodu" value="<?= (int)($p['idprodu'] ?? 0) ?>" />
                                        <td class="py-1" style="border:none"><?= htmlspecialchars($p['produ'] ?? '-') ?></td>
                                        <td class="small text-muted" style="border:none"><?= htmlspecialchars($p['nomsub'] ?? '-') ?></td>
                                        <td style="border:none">
                                            <div class="input-group input-group-sm">
                                                <input class="form-control" type="number" name="porcentaje" min="0" max="100" step="0.01" value="<?= htmlspecialchars((string)($p['porcentaje'] ?? '0')) ?>" style="width:90px" />
                                                <button class="btn btn-sm btn-accent" type="submit"><i class="bi bi-check-lg"></i></button>
                                            </div>
                                        </td>
                                        <td style="border:none">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" name="eliminar" value="1" title="Quitar bonus"><i class="bi bi-x-lg"></i></button>
                                        </td>
                                    </form>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$productos): ?>
                                <tr><td colspan="4" class="text-muted text-center">Sin productos con bonus.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
