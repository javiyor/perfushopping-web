<?php
use Perfushopping\Web\Support\Format;

$orden = $orden ?? null;
$pagos = $pagos ?? [];
if (!$orden) { echo '<div class="alert alert-warning">Orden no encontrada.</div>'; return; }

$badgeMap = ['pendiente' => 'warning', 'pagada' => 'success', 'anulada' => 'secondary'];
$formasLabel = ['efectivo' => 'Efectivo', 'transferencia' => 'Transferencia', 'cheque_propio' => 'Cheque propio'];
$noTransition = ['anulada'];
$canChange = !in_array($orden['estado'], $noTransition, true);
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/ordenes-pago">Órdenes de pago</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($orden['codigo'] ?? '') ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-8">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($orden['codigo'] ?? '') ?></span>
                <span class="badge bg-<?= $badgeMap[$orden['estado'] ?? 'pendiente'] ?? 'secondary' ?> fs-6"><?= htmlspecialchars($orden['estado'] ?? '') ?></span>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-3">Proveedor</dt>
                    <dd class="col-sm-9 fw-bold"><?= htmlspecialchars((string)($orden['proveedor_nombre'] ?? '-')) ?></dd>
                    <dt class="col-sm-3">Fecha</dt>
                    <dd class="col-sm-9"><?= htmlspecialchars((string)($orden['fecha'] ?? '-')) ?></dd>
                    <dt class="col-sm-3">Total</dt>
                    <dd class="col-sm-9 fw-bold fs-5"><?= Format::moneyFromCents((int)($orden['monto_cents'] ?? 0)) ?></dd>
                    <dt class="col-sm-3">Creado por</dt>
                    <dd class="col-sm-9 text-muted"><?= htmlspecialchars((string)($orden['created_by_nombre'] ?? '-')) ?></dd>
                    <?php if ($orden['concepto'] ?? ''): ?>
                    <dt class="col-sm-3">Concepto</dt>
                    <dd class="col-sm-9"><?= nl2br(htmlspecialchars((string)$orden['concepto'])) ?></dd>
                    <?php endif; ?>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Medios de pago</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Forma</th>
                            <th>Detalle</th>
                            <th class="text-end">Monto</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$pagos): ?>
                            <tr><td colspan="3" class="text-muted text-center">Sin pagos registrados.</td></tr>
                        <?php else: ?>
                            <?php foreach ($pagos as $p): ?>
                                <tr>
                                    <td><span class="badge bg-info"><?= htmlspecialchars($formasLabel[$p['forma_pago'] ?? ''] ?? $p['forma_pago'] ?? '') ?></span></td>
                                    <td>
                                        <?php if (($p['forma_pago'] ?? '') === 'cheque_propio' && $p['cheque_id']): ?>
                                            <a href="/admin/cheques/<?= (int)$p['cheque_id'] ?>">
                                                <?= htmlspecialchars((string)($p['cheque_banco'] ?? '') . ' N°' . ($p['numero_cheque'] ?? '')) ?>
                                            </a>
                                            <span class="badge bg-<?= ($p['cheque_estado'] ?? '') === 'emitido' ? 'info' : 'secondary' ?>"><?= htmlspecialchars((string)($p['cheque_estado'] ?? '')) ?></span>
                                        <?php elseif ($p['forma_pago'] === 'transferencia'): ?>
                                            Transferencia bancaria
                                        <?php else: ?>
                                            <?= htmlspecialchars((string)($p['forma_pago'] ?? '')) ?>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($p['monto_cents'] ?? 0)) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot>
                        <tr class="fw-bold">
                            <td colspan="2" class="text-end">Total</td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($orden['monto_cents'] ?? 0)) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Acciones</div>
            <div class="card-body">
                <?php if ($canChange): ?>
                <div class="d-grid gap-2">
                    <?php foreach (['pagada', 'anulada'] as $est): ?>
                        <?php if ($est === $orden['estado']) continue; ?>
                        <form method="post" action="/admin/ordenes-pago/estado">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$orden['id'] ?>" />
                            <input type="hidden" name="estado" value="<?= $est ?>" />
                            <button class="btn btn-<?= $est === 'pagada' ? 'success' : 'danger' ?> btn-sm w-100" type="submit">
                                <i class="bi bi-<?= $est === 'pagada' ? 'check-circle' : 'x-circle' ?>"></i>
                                Marcar como <?= ucfirst($est) ?>
                            </button>
                        </form>
                    <?php endforeach; ?>
                </div>
                <hr />
                <?php endif; ?>
                <form method="post" action="/admin/ordenes-pago/delete" onsubmit="return confirm('¿Eliminar esta orden?')">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="id" value="<?= (int)$orden['id'] ?>" />
                    <button class="btn btn-outline-danger btn-sm w-100" type="submit"><i class="bi bi-trash"></i> Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>
