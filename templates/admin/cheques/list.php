<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$tipo = (string)($tipo ?? '');
$estado = (string)($estado ?? '');
$q = (string)($q ?? '');
$sort = (string)($sort ?? 'vencimiento');
$order = (string)($order ?? 'asc');
$estadosDisponibles = ['' => 'Todos', 'en_cartera' => 'En cartera', 'emitido' => 'Emitido', 'entregado' => 'Entregado', 'depositado' => 'Depositado', 'cobrado' => 'Cobrado', 'rechazado' => 'Rechazado', 'anulado' => 'Anulado'];

$hoy = date('Y-m-d');
$estadosFinales = ['cobrado', 'rechazado', 'anulado', 'compensado', 'debitado'];
$esEnFecha = static function (array $c) use ($hoy, $estadosFinales): bool {
    $ven = substr((string)($c['fecha_vencimiento'] ?? ''), 0, 10);
    $emi = substr((string)($c['fecha_emision'] ?? ''), 0, 10);
    if ($ven === '' || $ven === '0000-00-00') {
        return false;
    }
    if (in_array((string)($c['estado'] ?? ''), $estadosFinales, true)) {
        return false;
    }
    if ($emi !== '' && $emi !== '0000-00-00' && $emi > $hoy) {
        return false;
    }
    return $ven >= $hoy;
};

$propios = array_values(array_filter($list, static fn ($c) => ($c['tipo'] ?? '') === 'propio'));
$terceros = array_values(array_filter($list, static fn ($c) => ($c['tipo'] ?? '') === 'tercero'));
$mostrarPropios = $tipo !== 'tercero';
$mostrarTerceros = $tipo !== 'propio';
$badgeMap = ['en_cartera' => 'secondary', 'emitido' => 'info', 'entregado' => 'warning', 'depositado' => 'primary', 'cobrado' => 'success', 'rechazado' => 'danger', 'anulado' => 'dark'];

$preserve = [];
if ($estado !== '') $preserve['estado'] = $estado;
if ($q !== '') $preserve['q'] = $q;
if ($tipo !== '') $preserve['tipo'] = $tipo;
$sortUrl = static fn (string $col) => '/admin/cheques?' . http_build_query(array_merge($preserve, [
    'sort' => $col,
    'order' => ($sort === $col && $order === 'asc') ? 'desc' : 'asc',
]));
$th = static function (string $col, string $label) use ($sortUrl, $sort, $order): string {
    $arrow = '';
    if ($sort === $col) {
        $arrow = $order === 'asc' ? ' <i class="bi bi-arrow-up small"></i>' : ' <i class="bi bi-arrow-down small"></i>';
    }
    return '<th><a class="text-decoration-none text-body fw-semibold" href="' . htmlspecialchars($sortUrl($col)) . '">' . $label . $arrow . '</a></th>';
};

$renderTabla = static function (array $rows, string $tipoTabla) use ($th, $esEnFecha, $badgeMap): void {
    $esPropio = $tipoTabla === 'propio';
    ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2 px-3">
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 cheques-densas">
                    <thead>
                        <tr>
                            <?= $th('id', '#') ?>
                            <th>Banco</th>
                            <th>N° Cheque</th>
                            <?= $th('titular', 'Proveedor / Titular') ?>
                            <th><?= $esPropio ? 'Concepto' : 'Quién entregó' ?></th>
                            <th class="text-end">Monto</th>
                            <?= $th('emision', 'Emisión') ?>
                            <?= $th('vencimiento', 'Vencimiento') ?>
                            <th>Estado</th>
                            <th style="width:36px"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$rows): ?>
                            <tr><td colspan="10" class="text-muted text-center">Sin cheques.</td></tr>
                        <?php else: ?>
                            <?php foreach ($rows as $c):
                                $enFecha = $esEnFecha($c); ?>
                                <tr class="<?= $enFecha ? 'fila-en-fecha' : '' ?>">
                                    <td class="text-muted"><?= (int)($c['id'] ?? 0) ?></td>
                                    <td><?= htmlspecialchars((string)($c['banco_emisor'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($c['numero_cheque'] ?? '')) ?></td>
                                    <td><?= htmlspecialchars((string)($c['titular'] ?? '')) ?></td>
                                    <td class="text-truncate" style="max-width:180px"><?= htmlspecialchars((string)($c[$esPropio ? 'concepto' : 'quien_entrego'] ?? '')) ?></td>
                                    <td class="text-end fw-bold"><?= Format::moneyFromCents((int)($c['monto_cents'] ?? 0)) ?></td>
                                    <td><?= htmlspecialchars(substr((string)($c['fecha_emision'] ?? ''), 0, 10)) ?></td>
                                    <td class="fw-semibold">
                                        <?= htmlspecialchars(substr((string)($c['fecha_vencimiento'] ?? ''), 0, 10)) ?>
                                        <?php if ($enFecha): ?>
                                            <span class="badge bg-success">En fecha</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="badge bg-<?= $badgeMap[$c['estado'] ?? 'en_cartera'] ?? 'secondary' ?>"><?= htmlspecialchars((string)($c['estado'] ?? '')) ?></span></td>
                                    <td><a class="btn btn-sm btn-outline-secondary py-0 px-1" href="/admin/cheques/<?= (int)($c['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <?php
};
?>
<style>
.cheques-densas > thead > tr > th,
.cheques-densas > tbody > tr > td {
    padding: 0.1rem 0.35rem;
    line-height: 1.25;
    vertical-align: middle;
    white-space: nowrap;
}
.cheques-densas > tbody > tr.fila-en-fecha > td {
    background-color: #d1e7dd;
}
.cheques-densas > thead > tr > th > a {
    color: inherit;
}
</style>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Cheques</h4>
        <p class="text-muted small mb-0">Listas separadas por tipo · orden por defecto: vencimiento ascendente · resaltado en verde = <strong>en fecha para cobrar/pagar</strong></p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary btn-sm" href="/admin/cheques/emitir?tipo=tercero"><i class="bi bi-plus-lg"></i> Cheque de tercero</a>
        <a class="btn btn-accent btn-sm" href="/admin/cheques/emitir"><i class="bi bi-plus-lg"></i> Emitir cheque propio</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="get" action="/admin/cheques" class="row g-2">
            <?php if ($tipo !== ''): ?>
                <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>" />
            <?php endif; ?>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="estado">
                    <?php foreach ($estadosDisponibles as $v => $l): ?>
                        <option value="<?= htmlspecialchars($v) ?>" <?= $estado === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-7">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por titular, banco o número" />
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<?php if ($mostrarPropios): ?>
    <div class="d-flex align-items-center gap-2 mb-1">
        <h5 class="fw-bold mb-0">Cheques propios</h5>
        <span class="badge bg-secondary"><?= count($propios) ?></span>
    </div>
    <?= $renderTabla($propios, 'propio') ?>
<?php endif; ?>

<?php if ($mostrarTerceros): ?>
    <div class="d-flex align-items-center gap-2 mb-1">
        <h5 class="fw-bold mb-0">Cheques de terceros</h5>
        <span class="badge bg-secondary"><?= count($terceros) ?></span>
    </div>
    <?= $renderTabla($terceros, 'tercero') ?>
<?php endif; ?>
