<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$estado = (string)($estado ?? '');
$desde = (string)($desde ?? '');
$hasta = (string)($hasta ?? '');
$page = max(1, (int)($page ?? 1));
$pages = max(1, (int)($pages ?? 1));
$total = (int)($total ?? count($list));
$estados = ['' => 'Todos', 'pendiente' => 'Pendiente', 'emitida' => 'Emitida', 'anulada' => 'Anulada'];
$queryBase = http_build_query(array_filter(['q' => $q, 'estado' => $estado, 'desde' => $desde, 'hasta' => $hasta], function($v) { return $v !== ''; }));
$pageUrl = function($p) use ($queryBase) {
    return '/admin/facturas/comprobantes?page=' . $p . ($queryBase !== '' ? '&' . $queryBase : '');
};
$winStart = max(1, min($page - 3, $pages - 6));
$winEnd = min($pages, $winStart + 6);
$winStart = max(1, $winEnd - 6);
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
        <form method="get" action="/admin/facturas/comprobantes" class="row g-2">
            <div class="col-lg-4">
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
                <input class="form-control form-control-sm" type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" title="Desde" />
            </div>
            <div class="col-lg-2">
                <input class="form-control form-control-sm" type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" title="Hasta" />
            </div>
            <div class="col-lg-1">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-lg-1">
                <?php if ($q !== '' || $estado !== '' || $desde !== '' || $hasta !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/facturas/comprobantes">Limpiar</a>
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
                    <th>ARCA</th>
                    <th>Pago</th>
                    <th>Vendedor</th>
                    <th>Creado por</th>
                    <th style="width:130px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="12" class="text-muted text-center">Sin facturas.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $f): ?>
                        <tr class="<?= $f['estado'] === 'anulada' ? 'table-light text-muted' : '' ?>">
                            <td><strong><?= htmlspecialchars((string)($f['codigo'] ?? '')) ?></strong></td>
                            <td><span class="badge bg-<?= $tipoBadges[$f['tipo_comprobante'] ?? 'FACT-B'] ?? 'secondary' ?>"><?= htmlspecialchars($tipoLabels[$f['tipo_comprobante'] ?? 'FACT-B'] ?? $f['tipo_comprobante'] ?? '') ?></span></td>
                            <td><?= htmlspecialchars((string)($f['cliente_nombre'] ?? '-')) ?></td>
                            <td class="small"><?= !empty($f['fecha']) ? date('d/m/Y', strtotime($f['fecha'])) : '-' ?></td>
                            <td class="text-center"><?= (int)($f['items_count'] ?? 0) ?></td>
                            <td class="text-end fw-bold"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)($f['total_cents'] ?? 0))) ?></td>
                            <td>
                                <?php $badge = ['pendiente' => 'warning', 'emitida' => 'success', 'anulada' => 'secondary']; ?>
                                <span class="badge bg-<?= $badge[$f['estado'] ?? 'pendiente'] ?? 'secondary' ?>"><?= htmlspecialchars($f['estado'] ?? 'pendiente') ?></span>
                            </td>
                            <td class="small">
                                <?php
                                $cae = !empty($f['cae']) && (string)$f['cae'] !== 'NULL' ? (string)$f['cae'] : '';
                                if ($cae !== ''):
                                ?>
                                    <span class="badge bg-success" title="CAE <?= htmlspecialchars($cae) ?>">Autorizada</span>
                                <?php elseif (($f['arca_resultado'] ?? '') === 'R'): ?>
                                    <span class="badge bg-danger" title="<?= htmlspecialchars((string)($f['arca_observaciones'] ?? '')) ?>">Rechazada</span>
                                    <form method="post" action="/admin/arca/reenviar" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                                        <input type="hidden" name="factura_id" value="<?= (int)$f['id'] ?>" />
                                        <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-1" title="Reenviar a ARCA"><i class="bi bi-arrow-repeat"></i></button>
                                    </form>
                                <?php else: ?>
                                    <form method="post" action="/admin/arca/reenviar" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                                        <input type="hidden" name="factura_id" value="<?= (int)$f['id'] ?>" />
                                        <button type="submit" class="btn btn-sm btn-outline-primary py-0 px-1">Autorizar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                            <td class="small"><?= htmlspecialchars((string)($f['forma_pago'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($f['vendedor_nombre'] ?? '-')) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($f['created_by_nombre'] ?? '-')) ?></td>
                            <td style="white-space:nowrap">
                                <a class="btn btn-sm btn-outline-secondary py-0 px-1" title="Ver" href="/admin/facturas/<?= (int)($f['id'] ?? 0) ?>"><i class="bi bi-eye"></i></a>
                                <a class="btn btn-sm btn-outline-secondary py-0 px-1" title="Imprimir" target="_blank" href="/admin/facturas/imprimir/<?= (int)($f['id'] ?? 0) ?>"><i class="bi bi-printer"></i></a>
                                <?php
                                $waTel = preg_replace('/\D/', '', (string)($f['cliente_tele'] ?? ''));
                                if ($waTel !== ''):
                                    $waText = 'Hola ' . ($f['cliente_nombre'] ?? '') . ', le enviamos su comprobante ' . ($f['codigo'] ?? '') . ' por un total de ' . Format::moneyFromCents((int)($f['total_cents'] ?? 0)) . '. ¡Gracias por su compra!';
                                ?>
                                <a class="btn btn-sm btn-outline-success py-0 px-1" title="Enviar por WhatsApp" target="_blank" href="https://wa.me/<?= htmlspecialchars($waTel) ?>?text=<?= urlencode($waText) ?>"><i class="bi bi-whatsapp"></i></a>
                                <?php endif; ?>
                                <?php if (trim((string)($f['cliente_mail'] ?? '')) !== ''): ?>
                                <button class="btn btn-sm btn-outline-primary py-0 px-1" title="Enviar por email" type="button" onclick="enviarComprobanteEmail(<?= (int)($f['id'] ?? 0) ?>, this)"><i class="bi bi-envelope"></i></button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <script>
    function enviarComprobanteEmail(id, btn) {
        if (!confirm('¿Enviar el comprobante por email al cliente?')) return;
        btn.disabled = true;
        fetch('/admin/facturas/' + id + '/enviar-email', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: '_csrf=' + encodeURIComponent('<?= htmlspecialchars($csrf ?? '') ?>'),
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            alert(d.ok ? 'Email enviado.' : ('Error: ' + (d.error || 'desconocido')));
        })
        .catch(function() { alert('Error de conexión.'); })
        .finally(function() { btn.disabled = false; });
    }
    </script>
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="small text-muted"><?= $total ?> comprobantes · Página <?= $page ?> de <?= $pages ?></span>
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $page <= 1 ? '#' : htmlspecialchars($pageUrl($page - 1)) ?>">‹</a>
                </li>
                <?php for ($p = $winStart; $p <= $winEnd; $p++): ?>
                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($pageUrl($p)) ?>"><?= $p ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= $page >= $pages ? '#' : htmlspecialchars($pageUrl($page + 1)) ?>">›</a>
                </li>
            </ul>
        </nav>
    </div>
    <?php endif; ?>
</div>
