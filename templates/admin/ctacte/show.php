<?php
use Perfushopping\Web\Support\Format;

$cliente = $cliente ?? null;
$saldo = (int)($saldo ?? 0);
$movimientos = $movimientos ?? [];
if (!$cliente):
?>
    <div class="alert alert-warning">Cliente no encontrado.</div>
<?php return; endif;

$origenLabels = ['factura'=>'Factura','recibo'=>'Recibo','ajuste'=>'Ajuste manual','nota_credito'=>'Nota de Crédito'];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/ctacte">Cuentas corrientes</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($cliente['name'] ?? '') ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($cliente['name'] ?? '') ?></h4>
        <p class="text-muted small"><?= htmlspecialchars((string)($cliente['email'] ?? '')) ?> — <?= htmlspecialchars((string)($cliente['phone'] ?? '')) ?></p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="fs-4 fw-bold <?= $saldo > 0 ? 'text-danger' : ($saldo < 0 ? 'text-success' : '') ?>">
            Saldo: <?= htmlspecialchars(Format::moneyFromCents($saldo)) ?>
        </span>
        <a class="btn btn-outline-primary btn-sm" href="/admin/ctacte/ajuste/<?= (int)($cliente['id'] ?? 0) ?>"><i class="bi bi-pencil"></i> Ajuste manual</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Movimientos</span>
        <span class="badge bg-secondary"><?= count($movimientos) ?> mov.</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Origen</th>
                    <th>Concepto</th>
                    <th class="text-end">Débito</th>
                    <th class="text-end">Crédito</th>
                    <th class="text-end">Saldo</th>
                    <th class="small text-muted">Creado por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$movimientos): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin movimientos.</td></tr>
                <?php else: ?>
                    <?php foreach (array_reverse($movimientos) as $m): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars((string)($m['created_at'] ?? '-')) ?></td>
                            <td>
                                <?php if (($m['tipo'] ?? '') === 'debito'): ?>
                                    <span class="badge bg-danger">Debe</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Haber</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php
                                $origenLabel = $origenLabels[$m['origen'] ?? ''] ?? $m['origen'] ?? '';
                                $origenId = (int)($m['origen_id'] ?? 0);
                                if ($origenId && $m['origen'] === 'factura') {
                                    echo '<a href="/admin/facturas/' . $origenId . '">' . htmlspecialchars($origenLabel) . ' #' . $origenId . '</a>';
                                } elseif ($origenId && $m['origen'] === 'recibo') {
                                    echo '<a href="/admin/recibos/' . $origenId . '">' . htmlspecialchars($origenLabel) . ' #' . $origenId . '</a>';
                                } else {
                                    echo htmlspecialchars($origenLabel) . ($origenId ? ' #' . $origenId : '');
                                }
                                ?>
                            </td>
                            <td class="small"><?= htmlspecialchars((string)($m['concepto'] ?? '-')) ?></td>
                            <td class="text-end"><?= ($m['tipo'] ?? '') === 'debito' ? htmlspecialchars(Format::moneyFromCents((int)($m['monto_cents'] ?? 0))) : '-' ?></td>
                            <td class="text-end"><?= ($m['tipo'] ?? '') === 'credito' ? htmlspecialchars(Format::moneyFromCents((int)($m['monto_cents'] ?? 0))) : '-' ?></td>
                            <td class="text-end fw-bold <?= ((int)($m['saldo_after_cents'] ?? 0)) > 0 ? 'text-danger' : (((int)($m['saldo_after_cents'] ?? 0)) < 0 ? 'text-success' : '') ?>">
                                <?= htmlspecialchars(Format::moneyFromCents((int)($m['saldo_after_cents'] ?? 0))) ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($m['created_by_nombre'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
