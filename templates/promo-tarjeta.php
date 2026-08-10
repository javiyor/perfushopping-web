<?php
/** @var array $promo */
/** @var array $share */
$p = $promo;
$esCredito = (string)($p['tipo_tarjeta'] ?? '') === 'credito';
$icono = $esCredito ? '💳' : '🏦';
$tipoLabel = $esCredito ? 'Crédito' : 'Débito';
$desde = (string)($p['fecha_desde'] ?? '');
$hasta = (string)($p['fecha_hasta'] ?? '');
$img = (string)($p['imagen'] ?? '');
?>

<div class="page" style="max-width:680px;margin:0 auto">
    <p style="margin:0 0 18px">
        <a href="/promociones" style="color:var(--gold);text-decoration:none;font-size:14px">&larr; Ver todas las promociones</a>
    </p>

    <div class="promo-card">
        <?php if ($img !== ''): ?>
            <div class="card-img-wrap">
                <img src="/upload/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars((string)($p['banco'] ?? '')) ?>" />
            </div>
        <?php else: ?>
            <div class="card-icon"><?= $icono ?></div>
        <?php endif; ?>
        <div class="card-banco"><?= htmlspecialchars((string)($p['banco'] ?? '')) ?></div>
        <div class="card-tipo"><?= $tipoLabel ?></div>
        <div class="card-descripcion"><?= nl2br(htmlspecialchars((string)($p['descripcion'] ?? ''))) ?></div>
        <?php if (trim((string)($p['detalle_promo'] ?? '')) !== ''): ?>
            <div class="card-detalle"><?= nl2br(htmlspecialchars((string)($p['detalle_promo'] ?? ''))) ?></div>
        <?php endif; ?>
        <?php if ($desde !== '' || $hasta !== ''): ?>
            <div class="card-vigencia">
                <i>📅</i>
                <?php if ($desde !== '' && $hasta !== ''): ?>
                    Vigente del <?= htmlspecialchars(date('d/m/Y', strtotime($desde))) ?> al <?= htmlspecialchars(date('d/m/Y', strtotime($hasta))) ?>
                <?php elseif ($desde !== ''): ?>
                    Desde el <?= htmlspecialchars(date('d/m/Y', strtotime($desde))) ?>
                <?php else: ?>
                    Hasta el <?= htmlspecialchars(date('d/m/Y', strtotime($hasta))) ?>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($share['url'])): ?>
    <div class="share">
        <span class="share-label">Compartir</span>
        <button class="share-btn gold" type="button" onclick="sharePromo()" title="Compartir (abre el menú nativo del teléfono)"><i class="bi bi-share"></i></button>
        <a class="share-btn" href="<?= htmlspecialchars($share['facebook']) ?>" target="_blank" rel="noopener" title="Compartir en Facebook"><i class="bi bi-facebook"></i></a>
        <a class="share-btn" href="<?= htmlspecialchars($share['x']) ?>" target="_blank" rel="noopener" title="Compartir en X"><i class="bi bi-twitter-x"></i></a>
        <a class="share-btn" href="<?= htmlspecialchars($share['whatsapp']) ?>" target="_blank" rel="noopener" title="Compartir en WhatsApp"><i class="bi bi-whatsapp"></i></a>
        <a class="share-btn" href="<?= htmlspecialchars($share['telegram']) ?>" target="_blank" rel="noopener" title="Compartir en Telegram"><i class="bi bi-send"></i></a>
        <button class="share-btn" type="button" id="shareCopyBtn" onclick="copyPromoLink()" title="Copiar link + texto (pegá en TikTok, Instagram, etc.)"><i class="bi bi-link-45deg"></i></button>
    </div>
    <script>
    var SHARE_PAYLOAD = <?= json_encode([
        'url' => $share['url'],
        'text' => $share['text'] . "\n" . $share['url'],
        'native' => $share['native'],
    ], JSON_UNESCAPED_UNICODE) ?>;
    function sharePromo() {
        if (navigator.share && SHARE_PAYLOAD.native) {
            navigator.share(SHARE_PAYLOAD.native).catch(function(){});
        } else {
            copyPromoLink();
        }
    }
    function copyPromoLink() {
        var payload = SHARE_PAYLOAD.text;
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(payload).then(flashCopy, function(){ legacyCopy(payload); });
        } else {
            legacyCopy(payload);
        }
    }
    function legacyCopy(text) {
        var ta = document.createElement('textarea');
        ta.value = text;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); flashCopy(); } catch(e) {}
        ta.remove();
    }
    function flashCopy() {
        var btn = document.getElementById('shareCopyBtn');
        var old = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(function(){ btn.innerHTML = old; }, 1500);
    }
    </script>
    <?php endif; ?>
</div>
