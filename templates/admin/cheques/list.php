<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$tipo = (string)($tipo ?? '');
$estado = (string)($estado ?? '');
$q = (string)($q ?? '');
$tipos = ['' => 'Todos', 'propio' => 'Propios', 'tercero' => 'De terceros'];
$estadosDisponibles = ['' => 'Todos', 'en_cartera' => 'En cartera', 'emitido' => 'Emitido', 'entregado' => 'Entregado', 'depositado' => 'Depositado', 'cobrado' => 'Cobrado', 'rechazado' => 'Rechazado', 'anulado' => 'Anulado'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Cheques</h4>
        <p class="text-muted small">Gestión de cheques propios y de terceros</p>
    </div>
    <a class="btn btn-accent btn-sm" href="/admin/cheques/emitir"><i class="bi bi-plus-lg"></i> Emitir cheque propio</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/cheques" class="row g-2">
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="tipo">
                    <?php foreach ($tipos as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $tipo === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="estado">
                    <?php foreach ($estadosDisponibles as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $estado === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-4">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por titular, banco o número" />
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Tipo</th>
                    <th>Banco</th>
                    <th>N° Cheque</th>
                    <th>Titular</th>
                    <th class="text-end">Monto</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <th>Estado</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="10" class="text-muted text-center">Sin cheques.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $c): ?>
                        <tr>
                            <td class="small text-muted"><?= (int)($c['id'] ?? 0) ?></td>
                            <td><span class="badge bg-<?= ($c['tipo'] ?? '') === 'propio' ? 'info' : 'secondary' ?>"><?= ($c['tipo'] ?? '') === 'propio' ? 'Propio' : 'Tercero' ?></span></td>
                            <td><?= htmlspecialchars((string)($c['banco_emisor'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($c['numero_cheque'] ?? '')) ?></td>
                            <td><?= htmlspecialchars((string)($c['titular'] ?? '')) ?></td>
                            <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($c['monto_cents'] ?? 0)) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($c['fecha_emision'] ?? '')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($c['fecha_vencimiento'] ?? '')) ?></td>
                            <td>
                                <?php $badge = ['en_cartera'=>'secondary','emitido'=>'info','entregado'=>'warning','depositado'=>'primary','cobrado'=>'success','rechazado'=>'danger','anulado'=>'dark']; ?>
                                <span class="badge bg-<?= $badge[$c['estado'] ?? 'en_cartera'] ?? 'secondary' ?>"><?= htmlspecialchars((string)($c['estado'] ?? '')) ?></span>
                            </td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/cheques/<?= (int)($c['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
