<?php
$csrf = (string)($csrf ?? '');
$logoUrl = (string)($logoUrl ?? '');
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0"><i class="bi bi-pencil-square"></i> Redactar email</h4>
    <a href="/admin/email" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver a la bandeja</a>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="/admin/email/enviar" id="emailComposeForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <div class="mb-3">
                <label class="form-label fw-semibold" for="emailTo">Para</label>
                <input class="form-control" id="emailTo" name="to" type="email" maxlength="254" required autocomplete="email" placeholder="destinatario@ejemplo.com" />
            </div>
            <div class="mb-3">
                <label class="form-label fw-semibold" for="emailSubject">Asunto</label>
                <input class="form-control" id="emailSubject" name="subject" maxlength="200" required />
            </div>
            <label class="form-label fw-semibold">Mensaje</label>
            <div class="btn-toolbar gap-1 mb-2" role="toolbar" aria-label="Formato del mensaje">
                <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="bold" title="Negrita"><i class="bi bi-type-bold"></i></button>
                <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="italic" title="Cursiva"><i class="bi bi-type-italic"></i></button>
                <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="underline" title="Subrayado"><i class="bi bi-type-underline"></i></button>
                <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="insertUnorderedList" title="Lista"><i class="bi bi-list-ul"></i></button>
                <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="insertOrderedList" title="Lista numerada"><i class="bi bi-list-ol"></i></button>
                <label class="btn btn-outline-secondary btn-sm mb-0 d-inline-flex align-items-center gap-1" title="Color de texto">
                    <i class="bi bi-palette"></i><input type="color" id="composeTextColor" value="#212529" aria-label="Color de texto" style="width:24px;height:22px;padding:0;border:0" />
                </label>
                <button class="btn btn-outline-secondary btn-sm" type="button" id="composeInsertLink" title="Insertar enlace"><i class="bi bi-link-45deg"></i></button>
                <button class="btn btn-outline-secondary btn-sm email-format-command" type="button" data-command="removeFormat" title="Quitar formato"><i class="bi bi-eraser"></i></button>
            </div>
            <div id="emailComposeEditor" class="form-control" contenteditable="true" role="textbox" aria-multiline="true" aria-label="Texto del mensaje" style="min-height:280px;max-height:60vh;overflow:auto;background:#fff;color:#212529"></div>
            <textarea name="body_html" id="emailComposeHtml" hidden></textarea>
            <?php if ($logoUrl !== ''): ?>
                <div class="mt-3 small text-muted">Se agregará la firma con el logo configurado en los datos de la empresa.</div>
                <img src="<?= htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo de la empresa" style="display:block;max-width:220px;max-height:100px;margin-top:8px" />
            <?php else: ?>
                <div class="mt-3 small text-muted">No hay un logo configurado en los datos de la empresa.</div>
            <?php endif; ?>
            <div class="d-flex justify-content-end mt-3">
                <button class="btn btn-primary" type="submit"><i class="bi bi-send"></i> Enviar email</button>
            </div>
        </form>
    </div>
</div>
<script>
(function(){
    var editor = document.getElementById('emailComposeEditor');
    var form = document.getElementById('emailComposeForm');
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
    var color = document.getElementById('composeTextColor');
    color.addEventListener('mousedown', rememberSelection);
    color.addEventListener('input', function(){ restoreSelection(); document.execCommand('foreColor', false, this.value); rememberSelection(); });
    var linkButton = document.getElementById('composeInsertLink');
    linkButton.addEventListener('mousedown', function(event){ event.preventDefault(); });
    linkButton.addEventListener('click', function(){
        restoreSelection();
        var url = window.prompt('Ingresá la dirección del enlace (https://...)');
        if (url && /^(https?:\/\/|mailto:)/i.test(url)) document.execCommand('createLink', false, url);
        rememberSelection();
    });
    form.addEventListener('submit', function(event){
        var body = editor.innerHTML.trim();
        if (!editor.innerText.trim()) { event.preventDefault(); alert('Escribí el mensaje antes de enviarlo.'); return; }
        document.getElementById('emailComposeHtml').value = body;
    });
})();
</script>
