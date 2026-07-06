<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$estado = (string)($estado ?? '');
$estados = ['' => 'Todos', 'pendiente' => 'Pendiente', 'emitida' => 'Emitida', 'anulada' => 'Anulada'];
$tipoLabels = ['FACT-A' => 'Factura A', 'FACT-B' => 'Factura B', 'FACT-C' => 'Factura C', 'NC' => 'Nota Crédito', 'ND' => 'Nota Débito'];
$tipoBadges = ['FACT-A' => 'primary', 'FACT-B' => 'success', 'FACT-C' => 'secondary', 'NC' => 'warning', 'ND' => 'danger'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Facturación</h4>
        <p class="text-muted small">Comprobantes emitidos</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/facturas/nueva"><i class="bi bi-plus-lg"></i> Nueva factura</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/facturas" class="row g-2">
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
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/facturas">Limpiar</a>
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
                    <th>Tipo</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th>Items</th>
                    <th class="text-end">Total</th>
                    <th>Estado</th>
                    <th>Pago</th>
                    <th>Vendedor</th>
                    <th>Creado por</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="11" class="text-muted text-center">Sin facturas.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $f): ?>
                        <tr class="<?= $f['estado'] === 'anulada' ? 'table-light text-muted' : '' ?>">
                            <td><strong><?= htmlspecialchars((string)($f['codigo'] ?? '')) ?></strong></td>
                            <td><span class="badge bg-<?= $tipoBadges[$f['tipo_comprobante'] ?? 'FACT-B'] ?? 'secondary' ?>"><?= htmlspecialchars($tipoLabels[$f['tipo_comprobante'] ?? 'FACT-B'] ?? $f['tipo_comprobante'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars((string)($f['cliente_nombre'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($f['fecha'] ?? '-')) ?></td>
                            <td class="text-center"><?= (int)($f['items_count'] ?? 0) ?></td>
                            <td class="text-end fw-bold"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)($f['total_cents'] ?? 0))) ?></td>
                            <td>
                                <?php $badge = ['pendiente' => 'warning', 'emitida' => 'success', 'anulada' => 'secondary']; ?>
                                <span class="badge bg-<?= $badge[$f['estado'] ?? 'pendiente'] ?? 'secondary' ?>"><?= htmlspecialchars($f['estado'] ?? 'pendiente') ?></span>
                            </td>
                            <td class="small"><?= htmlspecialchars((string)($f['forma_pago'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($f['vendedor_nombre'] ?? '-')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($f['created_by_nombre'] ?? '-')) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/facturas/<?= (int)($f['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
