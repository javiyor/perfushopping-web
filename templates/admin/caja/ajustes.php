<?php
use Perfushopping\Web\Support\Format;

$pendientes = $pendientes ?? [];
$historial = $historial ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Correcciones de caja</h4>
        <p class="text-muted small">Solicitudes de corrección de apertura pendientes de aprobación</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver a caja</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Pendientes (<?= count($pendientes) ?>)</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Solicitada</th>
                    <th>Sucursal / Turno</th>
                    <th>Por</th>
                    <th class="text-end">Actual</th>
                    <th class="text-end">Propuesto</th>
                    <th>Motivo del error</th>
                    <th style="width:170px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$pendientes): ?>
                    <tr><td colspan="7" class="text-muted text-center small">Sin solicitudes pendientes.</td></tr>
                <?php else: ?>
                    <?php foreach ($pendientes as $p): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars(mb_substr((string)($p['created_at'] ?? ''), 0, 16)) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['sucursal_nombre'] ?? '')) ?> · <?= htmlspecialchars((string)($p['fecha'] ?? '')) ?> · <?= htmlspecialchars((string)($p['turno'] ?? '')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['solicitado_por_nombre'] ?? '')) ?></td>
                            <td class="text-end small"><?= Format::moneyFromCents((int)($p['valor_anterior_cents'] ?? 0)) ?></td>
                            <td class="text-end small fw-bold"><?= Format::moneyFromCents((int)($p['valor_nuevo_cents'] ?? 0)) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($p['motivo'] ?? '')) ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <form method="post" action="/admin/caja/ajuste/resolver" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                                        <input type="hidden" name="accion" value="aprobar" />
                                        <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('¿Aprobar y aplicar esta corrección a la apertura?')">Aprobar</button>
                                    </form>
                                    <form method="post" action="/admin/caja/ajuste/resolver" class="d-inline">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>" />
                                        <input type="hidden" name="accion" value="rechazar" />
                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('¿Rechazar esta solicitud?')">Rechazar</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Historial</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Estado</th>
                    <th class="text-end">De</th>
                    <th class="text-end">A</th>
                    <th>Motivo</th>
                    <th>Resuelto por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$historial): ?>
                    <tr><td colspan="6" class="text-muted text-center small">Sin solicitudes.</td></tr>
                <?php else: ?>
                    <?php foreach ($historial as $h): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars(mb_substr((string)($h['created_at'] ?? ''), 0, 16)) ?></td>
                            <td><span class="badge bg-<?= ($h['estado'] ?? '') === 'aprobado' ? 'success' : (($h['estado'] ?? '') === 'rechazado' ? 'danger' : 'warning') ?>"><?= htmlspecialchars($h['estado'] ?? '') ?></span></td>
                            <td class="text-end small"><?= Format::moneyFromCents((int)($h['valor_anterior_cents'] ?? 0)) ?></td>
                            <td class="text-end small"><?= Format::moneyFromCents((int)($h['valor_nuevo_cents'] ?? 0)) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars(mb_substr((string)($h['motivo'] ?? ''), 0, 60)) ?></td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($h['resuelto_por_nombre'] ?? '—')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
