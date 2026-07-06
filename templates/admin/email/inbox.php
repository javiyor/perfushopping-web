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

<?php if ($emails): ?>
<div class="card shadow-sm">
    <div class="list-group list-group-flush">
        <?php foreach ($emails as $e): ?>
        <a href="/admin/email/<?= (int)$e['uid'] ?>" class="list-group-item list-group-item-action d-flex gap-3 align-items-start py-3">
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
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php elseif (!$error): ?>
    <div class="alert alert-info">Bandeja de entrada vacía.</div>
<?php endif; ?>
