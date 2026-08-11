<?php
$cliente = $cliente ?? [];
$cuenta = $cuenta ?? [];
$movimientos = $movimientos ?? [];
$csrfToken = $csrf ?? '';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($cliente['razon'] ?? '-') ?></h4>
        <p class="text-muted small"><?= htmlspecialchars((string)($cliente['cuit'] ?? '')) ?> · <?= htmlspecialchars((string)($cliente['email'] ?? '-')) ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/puntos"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<div class="row g-3 mb-3">
    <div class="col-md-3">
        <div class="card shadow-sm text-center py-3">
            <div class="text-muted small">Saldo disponible</div>
            <div class="display-6 fw-bold text-success"><?= number_format((int)($cuenta['saldo_puntos'] ?? 0), 0, ',', '.') ?></div>
            <div class="small text-muted">= $<?= number_format((int)($cuenta['saldo_puntos'] ?? 0), 0, ',', '.') ?> de crédito</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center py-3">
            <div class="text-muted small">Acumulados</div>
            <div class="display-6 fw-bold"><?= number_format((int)($cuenta['total_acumulado'] ?? 0), 0, ',', '.') ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm text-center py-3">
            <div class="text-muted small">Usados</div>
            <div class="display-6 fw-bold"><?= number_format((int)($cuenta['total_usados'] ?? 0), 0, ',', '.') ?></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card shadow-sm p-3">
            <h6 class="fw-bold mb-2"><i class="bi bi-sliders"></i> Ajuste manual</h6>
            <form method="post" action="/admin/puntos/ajustar" class="row g-1">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                <input type="hidden" name="idclien" value="<?= (int)($cliente['idclien'] ?? 0) ?>" />
                <div class="col-12">
                    <select class="form-select form-select-sm" name="tipo">
                        <option value="sumar">Sumar puntos</option>
                        <option value="quitar">Quitar puntos</option>
                    </select>
                </div>
                <div class="col-12">
                    <input class="form-control form-control-sm" type="number" name="puntos" min="1" placeholder="Cantidad" required />
                </div>
                <div class="col-12">
                    <input class="form-control form-control-sm" type="text" name="concepto" placeholder="Concepto" required />
                </div>
                <div class="col-12">
                    <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-check-lg"></i> Registrar ajuste</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold"><i class="bi bi-list-ul"></i> Movimientos</div>
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Concepto</th>
                    <th class="text-end">Puntos</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$movimientos): ?>
                    <tr><td colspan="4" class="text-muted text-center">Sin movimientos.</td></tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                        <?php $puntos = (int)($m['puntos'] ?? 0); ?>
                        <?php $tipo = (string)($m['tipo'] ?? ''); ?>
                        <?php $tipoLabel = ['acumulacion' => 'Acumulación', 'uso' => 'Canje', 'ajuste' => 'Ajuste'][$tipo] ?? $tipo; ?>
                        <?php $tipoBadge = ['acumulacion' => 'success', 'uso' => 'warning', 'ajuste' => 'secondary'][$tipo] ?? 'secondary'; ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars((string)($m['created_at'] ?? '-')) ?></td>
                            <td><span class="badge bg-<?= $tipoBadge ?>"><?= htmlspecialchars($tipoLabel) ?></span></td>
                            <td class="small"><?= htmlspecialchars((string)($m['descripcion'] ?? '-')) ?></td>
                            <td class="text-end fw-bold <?= $puntos > 0 ? 'text-success' : 'text-danger' ?>"><?= $puntos > 0 ? '+' : '' ?><?= number_format($puntos, 0, ',', '.') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
