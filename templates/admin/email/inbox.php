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
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php foreach ($emails as $e):
            $mid = (string)($e['message_id'] ?? '');
            $fid = (int)($labels[$mid] ?? 0);
        ?>
        <div class="list-group-item d-flex gap-3 align-items-start py-3">
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
