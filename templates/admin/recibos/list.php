<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$estado = (string)($estado ?? '');
$estados = ['' => 'Todos', 'emitido' => 'Emitido', 'anulado' => 'Anulado'];
$formaPagoLabels = [
    'efectivo'=>'Efectivo', 'transferencia'=>'Transferencia', 'tarjeta_credito'=>'Tarjeta crédito',
    'tarjeta_debito'=>'Tarjeta débito', 'mercadopago'=>'Mercado Pago', 'cuenta_corriente'=>'Cta. cte.',
];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Recibos emitidos</h4>
        <p class="text-muted small">Comprobantes de pago</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/recibos/nuevo"><i class="bi bi-plus-lg"></i> Nuevo recibo</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/recibos" class="row g-2">
            <div class="col-lg-6">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por código, cliente o CUIT" />
            </div>
            <div class="col-lg-2">
                <select class="form-select form-select-sm" name="estado">
                    <?php foreach ($estados as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $estado === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
            <div class="col-lg-2">
                <?php if ($q !== '' || $estado !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/recibos">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Concepto</th>
                    <th class="text-end">Monto</th>
                    <th>Pago</th>
                    <th>Estado</th>
                    <th>Creado por</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="9" class="text-muted text-center">Sin recibos.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $r): ?>
                        <tr class="<?= $r['estado'] === 'anulado' ? 'table-light text-muted' : '' ?>">
                            <td><strong><?= htmlspecialchars((string)($r['codigo'] ?? '')) ?></strong></td>
                            <td><?= htmlspecialchars((string)($r['cliente_nombre'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($r['fecha'] ?? '-')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($r['concepto'] ?? '-')) ?></td>
                            <td class="text-end fw-bold"><?= htmlspecialchars(Format::moneyFromCents((int)($r['monto_cents'] ?? 0))) ?></td>
                            <td class="small"><?= htmlspecialchars($formaPagoLabels[$r['forma_pago'] ?? ''] ?? $r['forma_pago'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-<?= ($r['estado'] ?? '') === 'anulado' ? 'secondary' : 'success' ?>"><?= htmlspecialchars($r['estado'] ?? 'emitido') ?></span>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($r['created_by_nombre'] ?? '-')) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/recibos/<?= (int)($r['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
