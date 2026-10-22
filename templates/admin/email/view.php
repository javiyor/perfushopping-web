<?php
$folders = $folders ?? [];
$folderId = (int)($folderId ?? 0);
$cliente = $cliente ?? null;
$attachments = $attachments ?? [];
$uid = (int)($uid ?? 0);
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

    <?php if ($attachments): ?>
    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-paperclip"></i> Archivos adjuntos (<?= count($attachments) ?>)</div>
        <div class="list-group list-group-flush">
            <?php foreach ($attachments as $attachment):
                $part = (string)$attachment['part'];
                $attachmentUrl = '/admin/email/' . $uid . '/adjunto?part=' . rawurlencode($part);
                $mime = strtolower((string)$attachment['mime']);
                $canPreview = in_array($mime, ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'application/pdf'], true);
                $bytes = (int)($attachment['bytes'] ?? 0);
                $sizeLabel = $bytes >= 1048576 ? number_format($bytes / 1048576, 1, ',', '.') . ' MB' : ($bytes >= 1024 ? number_format($bytes / 1024, 1, ',', '.') . ' KB' : $bytes . ' B');
            ?>
            <div class="list-group-item d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2 min-w-0">
                    <i class="bi bi-file-earmark-text fs-5 text-secondary"></i>
                    <div class="min-w-0">
                        <div class="fw-semibold text-break"><?= htmlspecialchars((string)$attachment['name']) ?></div>
                        <small class="text-muted"><?= htmlspecialchars($mime) ?> · <?= htmlspecialchars($sizeLabel) ?></small>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <?php if ($canPreview): ?><a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($attachmentUrl . '&inline=1') ?>" target="_blank" rel="noopener noreferrer"><i class="bi bi-eye"></i> Ver</a><?php endif; ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($attachmentUrl) ?>"><i class="bi bi-download"></i> Descargar</a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

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

    <div class="card shadow-sm mt-3">
        <div class="card-header bg-white fw-semibold"><i class="bi bi-reply"></i> Responder</div>
        <div class="card-body">
            <form method="post" action="/admin/email/responder" id="emailReplyForm">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <input type="hidden" name="uid" value="<?= $uid ?>" />
                <div class="mb-2 small text-muted">Para: <strong><?= htmlspecialchars((string)$email['from']) ?></strong></div>
                <div class="btn-toolbar gap-1 mb-2" role="toolbar" aria-label="Formato de respuesta">
                    <?php foreach ([
                        'bold' => ['Negrita', 'bi-type-bold'], 'italic' => ['Cursiva', 'bi-type-italic'],
                        'underline' => ['Subrayado', 'bi-type-underline'], 'insertUnorderedList' => ['Lista', 'bi-list-ul'],
                        'insertOrderedList' => ['Lista numerada', 'bi-list-ol'], 'justifyLeft' => ['Alinear izquierda', 'bi-text-left'],
                        'justifyCenter' => ['Centrar', 'bi-text-center'], 'justifyRight' => ['Alinear derecha', 'bi-text-right'],
                    ] as $command => [$label, $icon]): ?>
                    <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="<?= htmlspecialchars($command) ?>" title="<?= htmlspecialchars($label) ?>"><i class="bi <?= htmlspecialchars($icon) ?>"></i></button>
                    <?php endforeach; ?>
                    <label class="btn btn-outline-secondary btn-sm mb-0 d-inline-flex align-items-center gap-1" title="Color de texto">
                        <i class="bi bi-palette"></i><input type="color" id="replyTextColor" value="#212529" aria-label="Color de texto" style="width:24px;height:22px;padding:0;border:0" />
                    </label>
                    <button class="btn btn-outline-secondary btn-sm" type="button" id="replyInsertLink" title="Insertar enlace"><i class="bi bi-link-45deg"></i></button>
                    <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="removeFormat" title="Quitar formato"><i class="bi bi-eraser"></i></button>
                </div>
                <div id="emailReplyEditor" class="form-control" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Texto de respuesta" style="height:220px;overflow:auto;background:#fff;color:#212529"></div>
                <textarea name="body_html" id="emailReplyHtml" hidden></textarea>
                <div class="d-flex justify-content-end mt-3">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Enviar respuesta</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    (function(){
        var editor = document.getElementById('emailReplyEditor');
        var form = document.getElementById('emailReplyForm');
        var savedRange = null;
        if (!editor || !form) return;
        function rememberSelection(){
            var selection = window.getSelection();
            if (selection && selection.rangeCount && editor.contains(selection.anchorNode)) savedRange = selection.getRangeAt(0).cloneRange();
        }
        function restoreSelection(){
            editor.focus();
            if (!savedRange) return;
            var selection = window.getSelection();
            selection.removeAllRanges(); selection.addRange(savedRange);
        }
        editor.addEventListener('keyup', rememberSelection);
        editor.addEventListener('mouseup', rememberSelection);
        document.querySelectorAll('.email-format-command').forEach(function(button){
            button.addEventListener('mousedown', function(event){ event.preventDefault(); });
            button.addEventListener('click', function(){
                restoreSelection();
                document.execCommand('styleWithCSS', false, true);
                document.execCommand(this.dataset.command, false, null);
                rememberSelection();
            });
        });
        var color = document.getElementById('replyTextColor');
        color.addEventListener('mousedown', rememberSelection);
        color.addEventListener('input', function(){ restoreSelection(); document.execCommand('foreColor', false, this.value); rememberSelection(); });
        document.getElementById('replyInsertLink').addEventListener('mousedown', function(event){ event.preventDefault(); });
        document.getElementById('replyInsertLink').addEventListener('click', function(){
            restoreSelection();
            var url = window.prompt('Ingresá la dirección del enlace (https://...)');
            if (url && /^(https?:\/\/|mailto:)/i.test(url)) document.execCommand('createLink', false, url);
            rememberSelection();
        });
        form.addEventListener('submit', function(){ document.getElementById('emailReplyHtml').value = editor.innerHTML; });
    })();
    </script>
<?php else: ?>
    <div class="alert alert-info">No se pudo cargar el email.</div>
<?php endif; ?>
