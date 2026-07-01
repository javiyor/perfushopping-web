<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$saldoTotal = (int)($saldoTotal ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Cta Cte — Proveedores</h4>
        <p class="text-muted small">Saldos pendientes con proveedores (fletes en cta cte)</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/proveedores">Volver a proveedores</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/proveedores/ctacte" class="row g-2">
            <div class="col-lg-8">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar proveedor" />
            </div>
            <div class="col-lg-4">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Proveedores con saldo</span>
        <span class="badge bg-secondary"><?= count($list) ?> proveedores</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th class="text-end">Débitos</th>
                    <th class="text-end">Créditos</th>
                    <th class="text-end">Saldo</th>
                    <th>Último movimiento</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="6" class="text-muted text-center">Sin saldos pendientes.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $item): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)($item['proveedor_nombre'] ?? '-')) ?></strong></td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($item['debitos'] ?? 0)) ?></td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($item['creditos'] ?? 0)) ?></td>
                            <td class="text-end fw-bold <?= (int)($item['saldo_cents'] ?? 0) > 0 ? 'text-danger' : 'text-success' ?>">
                                <?= Format::moneyFromCents((int)($item['saldo_cents'] ?? 0)) ?>
                            </td>
                            <td class="small text-muted">
                                <?php if ($item['ultimo_mov'] ?? ''): ?>
                                    <?= date('d/m/Y H:i', strtotime((string)$item['ultimo_mov'])) ?>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($item['proveedor_id']): ?>
                                    <a class="btn btn-sm btn-outline-secondary" href="/admin/proveedores/ctacte/<?= (int)$item['proveedor_id'] ?>"><i class="bi bi-eye"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <?php if ($list): ?>
            <tfoot>
                <tr class="fw-bold">
                    <td>Total</td>
                    <td class="text-end"><?= Format::moneyFromCents(array_sum(array_column($list, 'debitos'))) ?></td>
                    <td class="text-end"><?= Format::moneyFromCents(array_sum(array_column($list, 'creditos'))) ?></td>
                    <td class="text-end <?= $saldoTotal > 0 ? 'text-danger' : 'text-success' ?>"><?= Format::moneyFromCents($saldoTotal) ?></td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
