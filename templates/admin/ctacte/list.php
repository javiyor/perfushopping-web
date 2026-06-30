<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Cuentas corrientes</h4>
        <p class="text-muted small">Saldos de clientes</p>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/ctacte" class="row g-2">
            <div class="col-lg-6">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar cliente..." />
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
            <div class="col-lg-2">
                <?php if ($q !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/ctacte">Limpiar</a>
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
                    <th>Cliente</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th class="text-end">Débitos</th>
                    <th class="text-end">Créditos</th>
                    <th class="text-end">Saldo</th>
                    <th>Último movimiento</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin movimientos.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $c): ?>
                        <?php $saldo = (int)($c['saldo_cents'] ?? 0); ?>
                        <tr>
                            <td><strong><?= htmlspecialchars($c['name'] ?? '-') ?></strong></td>
                            <td class="small"><?= htmlspecialchars((string)($c['email'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($c['phone'] ?? '-')) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($c['debitos'] ?? 0))) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($c['creditos'] ?? 0))) ?></td>
                            <td class="text-end fw-bold <?= $saldo > 0 ? 'text-danger' : ($saldo < 0 ? 'text-success' : '') ?>">
                                <?= htmlspecialchars(Format::moneyFromCents($saldo)) ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($c['ultimo_mov'] ?? '-')) ?></td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/ctacte/<?= (int)($c['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
