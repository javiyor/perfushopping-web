<?php
use Perfushopping\Web\Repo\CompraRepo;

$list = $list ?? [];
$q = (string)($q ?? '');
$estado = (string)($estado ?? '');
$desde = (string)($desde ?? '');
$hasta = (string)($hasta ?? '');
$cuentas = (new CompraRepo())->cuentas();
$estados = ['' => 'Todos', 'pendiente' => 'Pendiente', 'completa' => 'Completa'];
$origen = ['manual' => 'Manual', 'excel' => 'Excel', 'qr' => 'QR'];
$mon = static fn ($v) => number_format((float)$v, 2, ',', '.');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Facturas de compra</h4>
        <p class="text-muted small">Comprobantes de compra: importar desde ARCA, manual o por QR</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary btn-sm" href="/admin/compras/importar"><i class="bi bi-upload"></i> Importar (ARCA)</a>
        <a class="btn btn-accent btn-sm" href="/admin/compras/nueva"><i class="bi bi-plus-lg"></i> Nueva factura</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/compras" class="row g-2">
            <div class="col-lg-4">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Proveedor, CUIT, tipo o N°" />
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="estado">
                    <?php foreach ($estados as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $estado === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <input type="date" class="form-control form-control-sm" name="desde" value="<?= htmlspecialchars($desde) ?>" />
            </div>
            <div class="col-lg-2">
                <input type="date" class="form-control form-control-sm" name="hasta" value="<?= htmlspecialchars($hasta) ?>" />
            </div>
            <div class="col-lg-2 d-flex gap-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
                <?php if ($q !== '' || $estado !== '' || $desde !== '' || $hasta !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/compras">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<form method="post" action="/admin/compras/cuenta">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
    <div class="card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap align-items-center gap-2">
            <strong class="me-1">Cuenta contable:</strong>
            <select class="form-select form-select-sm" style="max-width:360px" name="idcta1">
                <option value="0">— Asignar a seleccionados —</option>
                <?php foreach ($cuentas as $c): ?>
                    <option value="<?= (int)$c['idcta1'] ?>"><?= htmlspecialchars((string)$c['nomcta'] . ' › ' . $c['nomcta1']) ?></option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Asignar</button>
            <span class="text-muted small">Seleccioná comprobantes con el checkbox.</span>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-admin table-hover mb-0">
                <thead>
                    <tr>
                        <th style="width:34px"></th>
                        <th>Fecha</th>
                        <th>Comprobante</th>
                        <th>Proveedor</th>
                        <th>CUIT</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">IVA</th>
                        <th>Cuenta contable</th>
                        <th>Origen</th>
                        <th>Items</th>
                        <th>Estado</th>
                        <th style="width:110px"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$list): ?>
                        <tr><td colspan="12" class="text-muted text-center">Sin facturas de compra.</td></tr>
                    <?php else: ?>
                        <?php foreach ($list as $fc): ?>
                            <tr>
                                <td><input type="checkbox" name="ids[]" value="<?= (int)$fc['id'] ?>" /></td>
                                <td class="small"><?= htmlspecialchars((string)($fc['fecha'] ?? '-')) ?></td>
                                <td class="small">
                                    <strong><?= htmlspecialchars((string)($fc['tipo'] ?? '-')) ?></strong>
                                    <span class="text-muted"><?= htmlspecialchars((string)($fc['punto_venta'] ?? '') . '-' . $fc['numero_desde']) ?></span>
                                </td>
                                <td><?= htmlspecialchars((string)($fc['razon_proveedor'] ?: ($fc['proveedor_razon'] ?: '-'))) ?></td>
                                <td class="small"><?= htmlspecialchars((string)($fc['cuit_proveedor'] ?? '')) ?></td>
                                <td class="text-end fw-bold">$<?= $mon($fc['imp_total']) ?></td>
                                <td class="text-end">$<?= $mon($fc['imp_iva']) ?></td>
                                <td class="small">
                                    <?php if (!empty($fc['cuenta_nombre'])): ?>
                                        <?= htmlspecialchars((string)$fc['cuenta_grupo'] . ' › ' . $fc['cuenta_nombre']) ?>
                                    <?php else: ?>
                                        <span class="text-muted">Sin asignar</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-light text-dark"><?= htmlspecialchars((string)($origen[$fc['origen'] ?? ''] ?? $fc['origen'] ?? '')) ?></span></td>
                                <td class="text-center"><?= (int)($fc['items_count'] ?? 0) ?></td>
                                <td>
                                    <?php $badge = ['pendiente' => 'warning', 'completa' => 'success']; ?>
                                    <span class="badge bg-<?= $badge[$fc['estado'] ?? 'pendiente'] ?? 'secondary' ?>"><?= htmlspecialchars((string)($fc['estado'] ?? '')) ?></span>
                                </td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-outline-secondary" title="Ver" href="/admin/compras/<?= (int)$fc['id'] ?>"><i class="bi bi-eye"></i></a>
                                    <a class="btn btn-sm btn-outline-primary" title="Editar" href="/admin/compras/<?= (int)$fc['id'] ?>/editar"><i class="bi bi-pencil"></i></a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</form>
