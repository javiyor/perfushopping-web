<?php
use Perfushopping\Web\Support\Format;
$config = $config ?? [];
$comprobantes = $comprobantes ?? [];
$taValido = $taValido ?? null;
$habilitado = ($config['habilitado'] ?? '0') === '1';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">ARCA — Factura electrónica</h4>
        <p class="text-muted small">Integración con ARCA (ex AFIP) para facturación electrónica</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/admin/arca/config"><i class="bi bi-gear"></i> Configurar</a>
        <?php if ($habilitado): ?>
        <form method="post" action="/admin/arca/test" style="margin:0">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-plug"></i> Probar conexión</button>
        </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0">
                <span class="badge bg-<?= $habilitado ? 'success' : 'secondary' ?> fs-6">
                    <?= $habilitado ? 'Habilitado' : 'Deshabilitado' ?>
                </span>
            </div>
            <div class="small text-muted">Estado</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0">
                <span class="badge bg-<?= ($config['ambiente'] ?? '') === 'homologacion' ? 'warning text-dark' : 'primary' ?> fs-6">
                    <?= ($config['ambiente'] ?? '') === 'homologacion' ? 'Homologación' : 'Producción' ?>
                </span>
            </div>
            <div class="small text-muted">Ambiente</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0 text-<?= $taValido ? 'success' : 'danger' ?>">
                <?= $taValido ? 'Válido' : 'Sin TA' ?>
            </div>
            <div class="small text-muted">Ticket de Acceso</div>
            <?php if ($taValido): ?>
                <div class="small text-muted">Vence: <?= htmlspecialchars(date('d/m/Y H:i', strtotime($taValido['expiration'] ?? ''))) ?></div>
            <?php endif; ?>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card-dashboard text-center">
            <div class="h5 fw-bold mb-0"><?= count($comprobantes) ?></div>
            <div class="small text-muted">Comprobantes enviados</div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Últimos comprobantes electrónicos</span>
        <span class="badge bg-secondary"><?= count($comprobantes) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead>
                <tr>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Fecha</th>
                    <th class="text-end">Total</th>
                    <th>CAE</th>
                    <th>Vto. CAE</th>
                    <th>Resultado</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$comprobantes): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin comprobantes electrónicos.</td></tr>
                <?php else: ?>
                    <?php foreach ($comprobantes as $c): ?>
                        <tr>
                            <td><strong><?= htmlspecialchars((string)($c['factura_codigo'] ?? '')) ?></strong></td>
                            <td class="small"><?= htmlspecialchars(mb_substr((string)($c['cliente_nombre'] ?? ''), 0, 30)) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($c['fecha'] ?? '')) ?></td>
                            <td class="text-end"><?= Format::moneyFromCents((int)($c['total_cents'] ?? 0)) ?></td>
                            <td><code><?= htmlspecialchars((string)($c['cae'] ?? '-')) ?></code></td>
                            <td class="small"><?= htmlspecialchars((string)($c['cae_vto'] ?? '-')) ?></td>
                            <td>
                                <?php $r = $c['resultado'] ?? ''; ?>
                                <span class="badge bg-<?= $r === 'A' ? 'success' : ($r === 'R' ? 'danger' : 'secondary') ?>">
                                    <?= $r === 'A' ? 'Aprobado' : ($r === 'R' ? 'Rechazado' : $r) ?>
                                </span>
                            </td>
                            <td><a class="btn btn-sm btn-outline-secondary" href="/admin/facturas/<?= (int)($c['factura_id'] ?? 0) ?>"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
