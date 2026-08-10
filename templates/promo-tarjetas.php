<?php
/** @var array $promos */
/** @var array $shareByPromo */
$promos = $promos ?? [];
$shareByPromo = $shareByPromo ?? [];
?>

<div class="promo-hero">
    <h1>Promociones Bancarias Vigentes</h1>
    <p>Aprovechá los beneficios exclusivos que tenemos para vos con tu banco</p>
</div>

<?php if (!$promos): ?>
    <div class="promo-empty">
        <div style="font-size:48px;margin-bottom:12px">💳</div>
        <p>No hay promociones vigentes en este momento.<br>Volvé a consultar pronto.</p>
    </div>
<?php else: ?>
    <div class="promo-grid">
        <?php foreach ($promos as $p):
            $promoId = (int)($p['id'] ?? 0);
            $esCredito = (string)($p['tipo_tarjeta'] ?? '') === 'credito';
            $icono = $esCredito ? '💳' : '🏦';
            $tipoLabel = $esCredito ? 'Crédito' : 'Débito';
            $desde = (string)($p['fecha_desde'] ?? '');
            $hasta = (string)($p['fecha_hasta'] ?? '');
            $img = (string)($p['imagen'] ?? '');
            $share = $shareByPromo[$promoId] ?? null;
        ?>
            <div class="promo-card" id="promo-<?= $promoId ?>">
                <?php if ($img !== ''): ?>
                    <div class="card-img-wrap">
                        <img src="/upload/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars((string)($p['banco'] ?? '')) ?>" loading="lazy" />
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
                <?php if ($share): ?>
                    <div class="card-share">
                        <button class="share-btn sm gold" type="button" onclick="sharePromo(<?= $promoId ?>)" title="Compartir (abre el menú nativo del teléfono)"><i class="bi bi-share"></i></button>
                        <a class="share-btn sm" href="<?= htmlspecialchars((string)$share['facebook']) ?>" target="_blank" rel="noopener" title="Compartir en Facebook"><i class="bi bi-facebook"></i></a>
                        <a class="share-btn sm" href="<?= htmlspecialchars((string)$share['x']) ?>" target="_blank" rel="noopener" title="Compartir en X"><i class="bi bi-twitter-x"></i></a>
                        <a class="share-btn sm" href="<?= htmlspecialchars((string)$share['whatsapp']) ?>" target="_blank" rel="noopener" title="Compartir en WhatsApp"><i class="bi bi-whatsapp"></i></a>
                        <a class="share-btn sm" href="<?= htmlspecialchars((string)$share['telegram']) ?>" target="_blank" rel="noopener" title="Compartir en Telegram"><i class="bi bi-send"></i></a>
                        <button class="share-btn sm" type="button" data-copy="<?= $promoId ?>" onclick="copyPromoLink(<?= $promoId ?>)" title="Copiar link + texto (pegá en TikTok, Instagram, etc.)"><i class="bi bi-link-45deg"></i></button>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
var PROMO_SHARES = <?= json_encode(array_map(static function (array $s): array {
    return [
        'text' => (string)$s['text'] . "\n" . (string)$s['url'],
        'native' => $s['native'],
    ];
}, $shareByPromo), JSON_UNESCAPED_UNICODE) ?>;
function sharePromo(id) {
    var p = PROMO_SHARES[id];
    if (navigator.share && p && p.native) {
        navigator.share(p.native).catch(function(){});
    } else {
        copyPromoLink(id);
    }
}
function copyPromoLink(id) {
    var p = PROMO_SHARES[id];
    if (!p) return;
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(p.text).then(function(){ flashCopied(id); }, function(){ legacyCopyPromo(p.text, id); });
    } else {
        legacyCopyPromo(p.text, id);
    }
}
function legacyCopyPromo(text, id) {
    var ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.opacity = '0';
    document.body.appendChild(ta);
    ta.select();
    try { document.execCommand('copy'); flashCopied(id); } catch(e) {}
    ta.remove();
}
function flashCopied(id) {
    var btn = document.querySelector('.promo-card .share-btn[data-copy="' + id + '"]');
    if (!btn) return;
    var old = btn.innerHTML;
    btn.innerHTML = '<i class="bi bi-check-lg"></i>';
    setTimeout(function(){ btn.innerHTML = old; }, 1500);
}
</script>
