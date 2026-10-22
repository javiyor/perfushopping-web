<?php
use Perfushopping\Web\Support\Env;

$folders = $folders ?? [];
$counts = $counts ?? [];
$labels = $labels ?? [];
$folderFilter = (int)($folderFilter ?? 0);
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
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Bandeja de entrada</h4>
        <p class="text-muted small"><?= htmlspecialchars(Env::get('SMTP_USER', '')) ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="https://webmail.perfushopping.com.ar" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Webmail</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
        <hr/>
        <p class="mb-0 small">También podés acceder al webmail directamente: <a href="https://webmail.perfushopping.com.ar" target="_blank">webmail.perfushopping.com.ar</a></p>
    </div>
<?php endif; ?>

<?php if (!$imapExtension): ?>
    <div class="alert alert-info">
        <i class="bi bi-info-circle"></i> La extensión IMAP no está disponible. Activá <code>php-imap</code> en Hostinger o usá el webmail.
    </div>
<?php endif; ?>

<ul class="nav nav-pills mb-3 flex-wrap">
    <li class="nav-item me-2 mb-1">
        <a class="nav-link <?= $folderFilter === 0 ? 'active' : '' ?>" href="/admin/email"><i class="bi bi-inbox"></i> Todos</a>
    </li>
    <?php foreach ($folders as $f): ?>
    <li class="nav-item me-2 mb-1">
        <a class="nav-link <?= $folderFilter === (int)$f['id'] ? 'active' : '' ?>" href="/admin/email?folder=<?= (int)$f['id'] ?>">
            <i class="bi bi-folder"></i> <?= htmlspecialchars((string)($f['nombre'] ?? '')) ?>
            <span class="badge bg-secondary"><?= (int)($counts[(int)$f['id']] ?? 0) ?></span>
        </a>
    </li>
    <?php endforeach; ?>
    <li class="nav-item mb-1">
        <form method="post" action="/admin/email/carpeta" class="d-flex gap-1">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input class="form-control form-control-sm" name="nombre" maxlength="80" placeholder="Nueva carpeta..." style="max-width:160px" required />
            <button class="btn btn-outline-secondary btn-sm" type="submit" title="Crear carpeta"><i class="bi bi-plus-lg"></i></button>
        </form>
    </li>
</ul>

<?php if ($emails): ?>
<div class="card shadow-sm mb-2">
    <div class="card-body py-2 d-flex gap-2 align-items-center flex-wrap">
        <input type="checkbox" id="checkAllMail" title="Seleccionar todos" />
        <label for="checkAllMail" class="small text-muted mb-0">Todos</label>
        <span class="small text-muted" id="selCount"></span>
        <?php if ($folders): ?>
        <select class="form-select form-select-sm" id="bulkFolder" style="max-width:180px">
            <option value="0">Mover a...</option>
            <?php foreach ($folders as $f): ?>
            <option value="<?= (int)$f['id'] ?>"><?= htmlspecialchars((string)($f['nombre'] ?? '')) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-secondary btn-sm" type="button" onclick="moverSeleccionados()"><i class="bi bi-folder-symlink"></i> Mover</button>
        <?php endif; ?>
        <button class="btn btn-outline-danger btn-sm" type="button" onclick="eliminarSeleccionados()"><i class="bi bi-trash"></i> Eliminar</button>
    </div>
</div>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php foreach ($emails as $e):
            $mid = (string)($e['message_id'] ?? '');
            $fid = (int)($labels[$mid] ?? 0);
        ?>
        <div class="list-group-item d-flex gap-2 align-items-start py-3">
            <input type="checkbox" class="mail-check mt-2" value="<?= htmlspecialchars($mid) ?>" data-msgno="<?= (int)$e['uid'] ?>" title="Seleccionar" />
            <a href="/admin/email/<?= (int)$e['uid'] ?>" class="d-flex gap-3 align-items-start flex-grow-1 min-w-0 text-decoration-none text-body">
                <div class="flex-shrink-0">
                    <div class="rounded-circle bg-accent text-white d-flex align-items-center justify-content-center" style="width:40px;height:40px;font-weight:700;font-size:14px">
                        <?= htmlspecialchars(mb_strtoupper(mb_substr($e['from_name'] ?: $e['from'], 0, 1))) ?>
                    </div>
                </div>
                <div class="flex-grow-1 min-w-0">
                    <div class="d-flex justify-content-between">
                        <strong class="text-truncate"><?= htmlspecialchars($e['from_name'] ?: $e['from']) ?></strong>
                        <small class="text-muted flex-shrink-0 ms-2"><?= htmlspecialchars((string)($e['date'])) ?></small>
                    </div>
                    <div class="text-truncate small fw-semibold"><?= htmlspecialchars(mb_substr((string)($e['subject']), 0, 80)) ?></div>
                    <div class="text-truncate text-muted small"><?= htmlspecialchars((string)($e['body_preview'])) ?></div>
                    <?php if ($fid > 0): ?>
                        <div class="mt-1"><span class="badge bg-info"><i class="bi bi-folder"></i> <?= htmlspecialchars($folderName($fid)) ?></span></div>
                    <?php endif; ?>
                </div>
            </a>
            <div class="flex-shrink-0">
                <?php if ($mid !== '' && $folders): ?>
                <form method="post" action="/admin/email/mover" class="d-flex gap-1">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                    <input type="hidden" name="message_id" value="<?= htmlspecialchars($mid) ?>" />
                    <input type="hidden" name="folder" value="<?= $folderFilter ?>" />
                    <select class="form-select form-select-sm" name="folder_id" style="max-width:130px" title="Mover a carpeta">
                        <option value="0">Carpeta...</option>
                        <?php foreach ($folders as $f): ?>
                        <option value="<?= (int)$f['id'] ?>" <?= $fid === (int)$f['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($f['nombre'] ?? '')) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-outline-secondary btn-sm" type="submit" title="Mover"><i class="bi bi-folder-symlink"></i></button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif (!$error): ?>
    <div class="alert alert-info">Bandeja de entrada vacía.</div>
<?php endif; ?>
<script>
(function() {
    var b = document.getElementById('badgeEmailsNuevos');
    if (b) b.style.display = 'none';
})();
function checkedMails() {
    return Array.from(document.querySelectorAll('.mail-check:checked'));
}
function updateSelCount() {
    var n = checkedMails().length;
    var el = document.getElementById('selCount');
    if (el) el.textContent = n > 0 ? n + ' seleccionado(s)' : '';
}
document.addEventListener('change', function(e) {
    if (e.target && e.target.id === 'checkAllMail') {
        document.querySelectorAll('.mail-check').forEach(function(c) { c.checked = e.target.checked; });
    }
    if (e.target && (e.target.id === 'checkAllMail' || e.target.classList.contains('mail-check'))) {
        updateSelCount();
    }
});
function mailPost(action, data) {
    var f = document.createElement('form');
    f.method = 'post';
    f.action = action;
    var csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_csrf'; csrf.value = <?= json_encode($csrf) ?>;
    f.appendChild(csrf);
    Object.keys(data).forEach(function(k) {
        var isArray = Array.isArray(data[k]);
        var vals = isArray ? data[k] : [data[k]];
        var fieldName = isArray && !/\[\]$/.test(k) ? k + '[]' : k;
        vals.forEach(function(v) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = fieldName; inp.value = v;
            f.appendChild(inp);
        });
    });
    var folder = document.createElement('input');
    folder.type = 'hidden'; folder.name = 'folder'; folder.value = '<?= $folderFilter ?>';
    f.appendChild(folder);
    document.body.appendChild(f); f.submit();
}
function moverSeleccionados() {
    var sel = checkedMails().map(function(c) { return c.value; }).filter(function(v) { return v !== ''; });
    if (sel.length === 0) { alert('Seleccioná al menos un mensaje.'); return; }
    var fid = document.getElementById('bulkFolder').value;
    if (!fid || fid === '0') { alert('Elegí la carpeta destino.'); return; }
    mailPost('/admin/email/mover-grupo', {'message_ids': sel, 'folder_id': fid});
}
function eliminarSeleccionados() {
    var sel = checkedMails().map(function(c) { return c.getAttribute('data-msgno'); }).filter(function(v) { return v && v !== '0'; });
    if (sel.length === 0) { alert('Seleccioná al menos un mensaje.'); return; }
    if (!confirm('¿Eliminar ' + sel.length + ' mensaje(s) del servidor?')) return;
    mailPost('/admin/email/eliminar', {'msgnos': sel});
}
</script>
