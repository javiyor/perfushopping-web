<?php
$tab = $tab ?? 'unassigned';
$q = $q ?? '';
$conversations = $conversations ?? [];
$selected = $selected ?? null;
$messages = $messages ?? [];
$notes = $notes ?? [];

$tabLabels = [
    'unassigned' => 'Sin asignar',
    'mine' => 'Mis conversaciones',
    'closed' => 'Cerradas',
];

$channelBadge = [
    'whatsapp' => 'success',
    'instagram' => 'danger',
    'facebook' => 'primary',
];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Mensajes de redes</h4>
        <p class="text-muted small mb-0">Cola de atencion para WhatsApp, Instagram y Facebook</p>
    </div>
</div>

<form method="get" action="/admin/mensajes" class="row g-2 mb-3">
    <div class="col-md-4">
        <input class="form-control form-control-sm" type="search" name="q" value="<?= htmlspecialchars((string)$q) ?>" placeholder="Buscar por nombre, telefono o ultimo mensaje" />
    </div>
    <div class="col-md-4">
        <input type="hidden" name="tab" value="<?= htmlspecialchars((string)$tab) ?>" />
        <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-search"></i> Buscar</button>
    </div>
</form>

<ul class="nav nav-pills mb-3">
    <?php foreach ($tabLabels as $tabKey => $label): ?>
    <li class="nav-item me-2">
        <a class="nav-link <?= $tab === $tabKey ? 'active' : '' ?>" href="/admin/mensajes?tab=<?= urlencode($tabKey) ?><?= $q !== '' ? '&q=' . urlencode((string)$q) : '' ?>"><?= htmlspecialchars($label) ?></a>
    </li>
    <?php endforeach; ?>
</ul>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><?= htmlspecialchars($tabLabels[$tab] ?? 'Bandeja') ?></span>
                <span class="badge bg-secondary"><?= count($conversations) ?></span>
            </div>
            <div class="list-group list-group-flush" style="max-height:70vh;overflow:auto">
                <?php if (!$conversations): ?>
                    <div class="p-3 text-muted small">No hay conversaciones en esta vista.</div>
                <?php endif; ?>
                <?php foreach ($conversations as $c): ?>
                    <?php $isActive = $selected && (int)$selected['id'] === (int)$c['id']; ?>
                    <?php $channel = (string)($c['channel'] ?? ''); ?>
                    <a href="/admin/mensajes?tab=<?= urlencode((string)$tab) ?>&c=<?= (int)$c['id'] ?><?= $q !== '' ? '&q=' . urlencode((string)$q) : '' ?>"
                       class="list-group-item list-group-item-action <?= $isActive ? 'active' : '' ?>">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong class="text-truncate" style="max-width:70%">
                                <?= htmlspecialchars((string)($c['display_name'] ?: $c['phone'] ?: $c['external_id'])) ?>
                            </strong>
                            <span class="badge bg-<?= htmlspecialchars($channelBadge[$channel] ?? 'secondary') ?>"><?= htmlspecialchars($channel) ?></span>
                        </div>
                        <div class="small text-muted text-truncate"><?= htmlspecialchars((string)($c['last_message_preview'] ?? '')) ?></div>
                        <div class="d-flex justify-content-between align-items-center mt-1">
                            <small class="text-muted"><?= htmlspecialchars((string)($c['last_message_at'] ?? '')) ?></small>
                            <?php if ((int)($c['unread_count'] ?? 0) > 0): ?>
                                <span class="badge rounded-pill bg-danger"><?= (int)$c['unread_count'] ?></span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <?php if ($selected): ?>
                    <div>
                        <strong><?= htmlspecialchars((string)($selected['display_name'] ?: $selected['phone'] ?: $selected['external_id'])) ?></strong>
                        <div class="small text-muted">
                            <?= htmlspecialchars((string)($selected['channel'] ?? '')) ?> ·
                            <?= htmlspecialchars((string)($selected['phone'] ?: $selected['external_id'])) ?>
                            <?php if ($selected['assigned_admin_nombre'] ?? ''): ?>
                                · Asignado a <?= htmlspecialchars((string)$selected['assigned_admin_nombre']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if (($selected['status'] ?? '') !== 'cerrado' && empty($selected['assigned_admin_id'])): ?>
                            <form method="post" action="/admin/mensajes/tomar">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                <input type="hidden" name="conversation_id" value="<?= (int)$selected['id'] ?>" />
                                <button class="btn btn-sm btn-accent" type="submit"><i class="bi bi-hand-index-thumb"></i> Tomar</button>
                            </form>
                        <?php endif; ?>
                        <?php if (($selected['status'] ?? '') !== 'cerrado' && !empty($selected['assigned_admin_id'])): ?>
                            <form method="post" action="/admin/mensajes/liberar">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                <input type="hidden" name="conversation_id" value="<?= (int)$selected['id'] ?>" />
                                <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-unlock"></i> Liberar</button>
                            </form>
                        <?php endif; ?>
                        <?php if (($selected['status'] ?? '') !== 'cerrado'): ?>
                            <form method="post" action="/admin/mensajes/cerrar">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                <input type="hidden" name="conversation_id" value="<?= (int)$selected['id'] ?>" />
                                <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-check2-square"></i> Cerrar</button>
                            </form>
                        <?php else: ?>
                            <form method="post" action="/admin/mensajes/reabrir">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                                <input type="hidden" name="conversation_id" value="<?= (int)$selected['id'] ?>" />
                                <button class="btn btn-sm btn-outline-primary" type="submit"><i class="bi bi-arrow-clockwise"></i> Reabrir</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <strong>Seleccioná una conversación</strong>
                <?php endif; ?>
            </div>
            <div class="card-body" style="max-height:52vh;overflow:auto;background:#fafafa">
                <?php if (!$selected): ?>
                    <div class="text-muted small">No hay conversación seleccionada.</div>
                <?php elseif (!$messages): ?>
                    <div class="text-muted small">No hay mensajes todavía.</div>
                <?php else: ?>
                    <?php foreach ($messages as $m): ?>
                        <div class="mb-2 d-flex <?= ($m['direction'] ?? 'in') === 'in' ? 'justify-content-start' : 'justify-content-end' ?>">
                            <div class="p-2 rounded" style="max-width:85%;background:<?= ($m['direction'] ?? 'in') === 'in' ? '#fff' : '#d1e7dd' ?>;border:1px solid #e5e5e5">
                                <?php if (($m['body'] ?? '') !== ''): ?>
                                    <div style="white-space:pre-wrap"><?= nl2br(htmlspecialchars((string)$m['body'])) ?></div>
                                <?php else: ?>
                                    <div class="text-muted small">[<?= htmlspecialchars((string)($m['message_type'] ?? 'mensaje')) ?>]</div>
                                <?php endif; ?>
                                <div class="small text-muted mt-1"><?= htmlspecialchars((string)($m['provider_created_at'] ?? $m['created_at'] ?? '')) ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($selected): ?>
        <div class="card shadow-sm mt-3">
            <div class="card-header bg-white fw-semibold">Notas internas</div>
            <div class="card-body">
                <form method="post" action="/admin/mensajes/nota" class="mb-3">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
                    <input type="hidden" name="conversation_id" value="<?= (int)$selected['id'] ?>" />
                    <input type="hidden" name="tab" value="<?= htmlspecialchars((string)$tab) ?>" />
                    <textarea class="form-control form-control-sm" name="note" rows="2" placeholder="Agregar nota interna"></textarea>
                    <button class="btn btn-outline-secondary btn-sm mt-2" type="submit"><i class="bi bi-journal-plus"></i> Guardar nota</button>
                </form>

                <?php if (!$notes): ?>
                    <div class="text-muted small">Todavia no hay notas.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($notes as $n): ?>
                            <div class="list-group-item px-0">
                                <div class="small"><?= nl2br(htmlspecialchars((string)$n['note'])) ?></div>
                                <div class="small text-muted"><?= htmlspecialchars((string)($n['admin_nombre'] ?? 'Admin')) ?> · <?= htmlspecialchars((string)($n['created_at'] ?? '')) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
