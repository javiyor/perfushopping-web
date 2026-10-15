<?php
$folders = $folders ?? [];
$folderId = (int)($folderId ?? 0);
$cliente = $cliente ?? null;
$sugerida = $sugerida ?? null;
$csrf = (string)($csrf ?? '');
$folderName = static function (int $id) use ($folders): string {
    foreach ($folders as $f) {
        if ((int)($f['id'] ?? 0) === $id) {
            return (string)($f['nombre'] ?? '');
        }
    }
    return '';
};
?>
<div class="mb-3">
    <a href="/admin/email" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php elseif ($email): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body">
            <h5 class="card-title fw-bold"><?= htmlspecialchars($email['subject']) ?></h5>
            <hr/>
            <dl class="row mb-0 small">
                <dt class="col-sm-2 text-muted">De</dt>
                <dd class="col-sm-10"><?= htmlspecialchars($email['from_name'] ?: $email['from']) ?> &lt;<?= htmlspecialchars($email['from']) ?>&gt;</dd>
                <dt class="col-sm-2 text-muted">Fecha</dt>
                <dd class="col-sm-10"><?= htmlspecialchars((string)($email['date'])) ?></dd>
                <?php if ($folderId > 0): ?>
                <dt class="col-sm-2 text-muted">Carpeta</dt>
                <dd class="col-sm-10"><span class="badge bg-info"><i class="bi bi-folder"></i> <?= htmlspecialchars($folderName($folderId)) ?></span></dd>
                <?php endif; ?>
            </dl>
            <?php if (($email['message_id'] ?? '') !== '' && $folders): ?>
            <form method="post" action="/admin/email/mover" class="d-flex gap-2 mt-3 align-items-end">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <input type="hidden" name="message_id" value="<?= htmlspecialchars((string)$email['message_id']) ?>" />
                <div>
                    <label class="form-label small mb-1">Mover a carpeta</label>
                    <select class="form-select form-select-sm" name="folder_id" style="min-width:180px">
                        <?php foreach ($folders as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= $folderId === (int)$f['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($f['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-folder-symlink"></i> Mover</button>
                <?php if ($folderId > 0): ?>
                <button class="btn btn-outline-danger btn-sm" type="submit" formaction="/admin/email/quitar" title="Sacar de la carpeta"><i class="bi bi-folder-x"></i></button>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($cliente): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-body py-2 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <div class="small">
                <i class="bi bi-person-check text-success"></i>
                <strong>Cliente:</strong> <?= htmlspecialchars((string)($cliente['razon'] ?? '')) ?>
                <?php if (trim((string)($cliente['categoria'] ?? '')) !== ''): ?>
                    · <span class="badge bg-secondary"><?= htmlspecialchars((string)$cliente['categoria']) ?></span>
                <?php endif; ?>
                <?php if (trim((string)($cliente['especialidad'] ?? '')) !== ''): ?>
                    · <span class="badge bg-secondary"><?= htmlspecialchars((string)$cliente['especialidad']) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($sugerida && ($email['message_id'] ?? '') !== ''): ?>
            <form method="post" action="/admin/email/mover">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <input type="hidden" name="message_id" value="<?= htmlspecialchars((string)$email['message_id']) ?>" />
                <input type="hidden" name="folder_id" value="<?= (int)$sugerida['id'] ?>" />
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-magic"></i> Mover a <?= htmlspecialchars((string)($sugerida['nombre'] ?? '')) ?></button>
            </form>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body p-2" style="background:#fff;border-radius:0 0 6px 6px">
            <?php if (trim((string)($email['body_html'] ?? '')) !== ''): ?>
                <iframe sandbox="allow-popups" srcdoc="<?= htmlspecialchars((string)$email['body_html'], ENT_QUOTES, 'UTF-8') ?>" style="width:100%;height:640px;border:0;background:#fff" title="Contenido del email"></iframe>
            <?php else: ?>
                <pre style="white-space:pre-wrap;font-size:14px;background:#fff;color:#212529;padding:12px;margin:0"><?= htmlspecialchars((string)($email['body_plain'] ?? '(sin contenido)')) ?></pre>
            <?php endif; ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">No se pudo cargar el email.</div>
<?php endif; ?>
