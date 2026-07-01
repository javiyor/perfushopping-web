<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$proveedor = (string)($proveedor ?? '');
$totalFletes = (int)($totalFletes ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Costos de flete</h4>
        <p class="text-muted small">Costos comparativos de fletes por orden de compra</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/ordenes-compra">Volver a OC</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/ordenes-compra/fletes" class="row g-2">
            <div class="col-lg-5">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por código o proveedor" />
            </div>
            <div class="col-lg-4">
                <input class="form-control form-control-sm" name="proveedor" value="<?= htmlspecialchars($proveedor) ?>" placeholder="Filtrar por proveedor" />
            </div>
            <div class="col-lg-3">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Fletes registrados</span>
        <span class="badge bg-secondary"><?= count($list) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>OC</th>
                    <th>Proveedor</th>
                    <th>Fecha</th>
                    <th class="text-end">Total OC</th>
                    <th class="text-end">Flete</th>
                    <th>Estado</th>
                    <th>Comprobante</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin registros de fletes.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $o): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)($o['codigo'] ?? '')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($o['proveedor_nombre'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($o['fecha'] ?? '-')) ?></td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($o['total_cents'] ?? 0)) ?></td>
                            <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($o['flete_cents'] ?? 0)) ?></td>
                            <td>
                                <?php if ((int)$o['flete_pagado']): ?>
                                    <span class="badge bg-success">Pagado</span>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark">En cta cte</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($o['flete_comprobante']): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="/admin/ordenes-compra/descargar-comprobante/<?= (int)$o['id'] ?>"><i class="bi bi-paperclip"></i></a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/ordenes-compra/<?= (int)$o['id'] ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr class="fw-bold">
                    <td colspan="4" class="text-end">Total fletes</td>
                    <td class="text-end"><?= Format::moneyFromCents($totalFletes) ?></td>
                    <td colspan="3"></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
