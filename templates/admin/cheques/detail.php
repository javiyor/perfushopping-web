<?php
use Perfushopping\Web\Support\Format;

$cheque = $cheque ?? null;
$movimientos = $movimientos ?? [];
if (!$cheque) { echo '<div class="alert alert-warning">Cheque no encontrado.</div>'; return; }

$badge = ['en_cartera'=>'secondary','emitido'=>'info','entregado'=>'warning','depositado'=>'primary','cobrado'=>'success','rechazado'=>'danger','anulado'=>'dark'];
$estadosPermitidos = [];
if ($cheque['tipo'] === 'tercero') {
    $estadosPermitidos = ['depositado' => 'Depositar', 'cobrado' => 'Cobrado', 'rechazado' => 'Rechazar', 'entregado' => 'Entregar', 'anulado' => 'Anular'];
} else {
    $estadosPermitidos = ['entregado' => 'Entregado', 'cobrado' => 'Cobrado', 'rechazado' => 'Rechazado', 'anulado' => 'Anular'];
}
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/cheques">Cheques</a></li>
        <li class="breadcrumb-item active">Cheque #<?= (int)$cheque['id'] ?></li>
    </ol>
</nav>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span>Cheque <?= ($cheque['tipo'] ?? '') === 'propio' ? 'propio' : 'de terceros' ?></span>
                <span class="badge bg-<?= $badge[$cheque['estado'] ?? 'en_cartera'] ?? 'secondary' ?> fs-6"><?= htmlspecialchars((string)($cheque['estado'] ?? '')) ?></span>
            </div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-4">Banco emisor</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($cheque['banco_emisor'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Número de cheque</dt>
                    <dd class="col-sm-8 fw-bold"><?= htmlspecialchars((string)($cheque['numero_cheque'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Titular</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($cheque['titular'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">CUIT</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($cheque['cuit_titular'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Monto</dt>
                    <dd class="col-sm-8 fw-bold fs-5"><?= Format::moneyFromCents((int)($cheque['monto_cents'] ?? 0)) ?></dd>
                    <dt class="col-sm-4">Fecha emisión</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($cheque['fecha_emision'] ?? '-')) ?></dd>
                    <dt class="col-sm-4">Vencimiento</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)($cheque['fecha_vencimiento'] ?? '-')) ?></dd>
                    <?php if ($cheque['cuenta_banco'] ?? ''): ?>
                    <dt class="col-sm-4">Cta. bancaria</dt>
                    <dd class="col-sm-8"><?= htmlspecialchars((string)$cheque['cuenta_banco']) ?></dd>
                    <?php endif; ?>
                    <?php if ($cheque['concepto'] ?? ''): ?>
                    <dt class="col-sm-4">Concepto</dt>
                    <dd class="col-sm-8"><?= nl2br(htmlspecialchars((string)$cheque['concepto'])) ?></dd>
                    <?php endif; ?>
                    <dt class="col-sm-4">Creado por</dt>
                    <dd class="col-sm-8 text-muted"><?= htmlspecialchars((string)($cheque['created_by_nombre'] ?? '-')) ?></dd>
                </dl>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Movimientos</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr><th>Fecha</th><th>Tipo</th><th>Origen</th><th>Observaciones</th><th>Usuario</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$movimientos): ?>
                            <tr><td colspan="5" class="text-muted text-center">Sin movimientos.</td></tr>
                        <?php else: ?>
                            <?php foreach ($movimientos as $m): ?>
                                <tr>
                                    <td class="small"><?= date('d/m/Y H:i', strtotime((string)($m['created_at'] ?? ''))) ?></td>
                                    <td><span class="badge bg-info"><?= htmlspecialchars((string)($m['tipo'] ?? '')) ?></span></td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($m['origen'] ?? '') . ($m['origen_id'] ? ' #' . $m['origen_id'] : '')) ?></td>
                                    <td class="small"><?= htmlspecialchars((string)($m['observaciones'] ?? '')) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($m['created_by_nombre'] ?? '-')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Acciones</div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <?php foreach ($estadosPermitidos as $est => $label): ?>
                        <?php if ($est === ($cheque['estado'] ?? '')) continue; ?>
                        <form method="post" action="/admin/cheques/estado">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                            <input type="hidden" name="id" value="<?= (int)$cheque['id'] ?>" />
                            <input type="hidden" name="estado" value="<?= $est ?>" />
                            <div class="input-group input-group-sm mb-1">
                                <input class="form-control" name="observaciones" placeholder="Observaciones (opcional)" />
                                <button class="btn btn-outline-<?= $est === 'anulado' ? 'danger' : 'primary' ?>" type="submit"><?= htmlspecialchars($label) ?></button>
                            </div>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
