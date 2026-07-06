<div class="mb-3">
    <a href="/admin/email" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Volver</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i> <?= htmlspecialchars($error) ?>
    </div>
<?php elseif ($email): ?>
    <div class="card shadow-sm">
        <div class="card-body">
            <h5 class="card-title fw-bold"><?= htmlspecialchars($email['subject']) ?></h5>
            <hr/>
            <dl class="row mb-0 small">
                <dt class="col-sm-2 text-muted">De</dt>
                <dd class="col-sm-10"><?= htmlspecialchars($email['from_name'] ?: $email['from']) ?> &lt;<?= htmlspecialchars($email['from']) ?>&gt;</dd>
                <dt class="col-sm-2 text-muted">Fecha</dt>
                <dd class="col-sm-10"><?= htmlspecialchars((string)($email['date'])) ?></dd>
            </dl>
        </div>
        <div class="card-body border-top" style="min-height:300px">
            <?php
            $body = $email['body'] ?? '';
            $isHtml = str_contains($body, '<') && str_contains($body, '>');
            if ($isHtml):
                echo $body;
            else:
                echo '<pre style="white-space:pre-wrap;font-size:14px">' . htmlspecialchars($body) . '</pre>';
            endif;
            ?>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-info">No se pudo cargar el email.</div>
<?php endif; ?>
