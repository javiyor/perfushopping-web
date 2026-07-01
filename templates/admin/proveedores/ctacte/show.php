<?php
use Perfushopping\Web\Support\Format;

$movimientos = $movimientos ?? [];
$proveedorNombre = (string)($proveedorNombre ?? '');
$proveedorId = $proveedorId ?? null;
$saldo = (int)($saldo ?? 0);
$q = (string)($q ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($proveedorNombre ?: 'Proveedor') ?></h4>
        <p class="text-muted small">Movimientos de cuenta corriente</p>
    </div>
    <div>
        <span class="fw-bold fs-5 me-3 <?= $saldo > 0 ? 'text-danger' : 'text-success' ?>">Saldo: <?= Format::moneyFromCents($saldo) ?></span>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/proveedores/ctacte">Volver</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/proveedores/ctacte/<?= $proveedorId ?>" class="row g-2">
            <div class="col-lg-8">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar en movimientos..." />
            </div>
            <div class="col-lg-4">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Movimientos</span>
        <span class="badge bg-secondary"><?= count($movimientos) ?> registros</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th>Origen</th>
                    <th class="text-end">Monto</th>
                    <th class="text-end">Saldo después</th>
                    <th>Registrado por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$movimientos): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin movimientos.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td class="small text-muted"><?= (int)($m['id'] ?? 0) ?></td>
                            <td class="small"><?= date('d/m/Y H:i', strtotime((string)($m['created_at'] ?? ''))) ?></td>
                            <td>
                                <?php if (($m['tipo'] ?? '') === 'debito'): ?>
                                    <span class="badge bg-danger">Débito</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Crédito</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($m['concepto'] ?? '')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($m['origen'] ?? '') . ($m['origen_id'] ? ' #' . $m['origen_id'] : '')) ?></td>
                            <td class="text-end <?= ($m['tipo'] ?? '') === 'debito' ? 'text-danger' : 'text-success' ?>">
                                <?= Format::moneyFromCents((int)($m['monto_cents'] ?? 0)) ?>
                            </td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($m['saldo_after_cents'] ?? 0)) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($m['created_by_nombre'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
