<?php
/** @var array $promos */
$promos = $promos ?? [];
?>
<style>
.promo-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
    gap: 22px;
    margin: 30px 0;
}
.promo-card {
    position: relative;
    background: var(--card);
    border: 1px solid rgba(216,178,90,0.15);
    border-radius: 18px;
    padding: 24px 22px;
    transition: transform .25s ease, box-shadow .3s ease, border-color .3s ease;
    overflow: hidden;
}
.promo-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at 30% 40%, rgba(216,178,90,0.06), transparent 60%);
    opacity: 0;
    transition: opacity .4s ease;
    pointer-events: none;
}
.promo-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 20px 50px rgba(216,178,90,0.12), 0 0 0 1px rgba(216,178,90,0.25);
    border-color: var(--gold);
}
.promo-card:hover::before {
    opacity: 1;
}
.promo-card .card-icon {
    font-size: 38px;
    margin-bottom: 10px;
    display: inline-block;
    filter: drop-shadow(0 0 8px rgba(216,178,90,0.35));
}
.promo-card .card-banco {
    font-size: 18px;
    font-weight: 700;
    color: var(--gold);
    margin: 0 0 4px;
}
.promo-card .card-tipo {
    display: inline-block;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: .04em;
    padding: 2px 10px;
    border-radius: 20px;
    background: rgba(216,178,90,0.12);
    color: var(--gold2);
    margin-bottom: 12px;
}
.promo-card .card-descripcion {
    font-size: 15px;
    line-height: 1.5;
    color: var(--text);
    margin-bottom: 8px;
}
.promo-card .card-detalle {
    font-size: 13px;
    line-height: 1.5;
    color: var(--muted);
    margin-bottom: 14px;
}
.promo-card .card-img-wrap {
    width: 100%;
    height: 160px;
    overflow: hidden;
    border-radius: 12px;
    margin-bottom: 14px;
    background: rgba(216,178,90,0.04);
    display: flex;
    align-items: center;
    justify-content: center;
}
.promo-card .card-img-wrap img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform .4s ease;
}
.promo-card:hover .card-img-wrap img {
    transform: scale(1.04);
}
.promo-card .card-vigencia {
    font-size: 12px;
    color: var(--muted2);
    display: flex;
    align-items: center;
    gap: 6px;
    border-top: 1px solid rgba(216,178,90,0.1);
    padding-top: 12px;
}
.promo-card .card-vigencia i {
    font-size: 14px;
}
.promo-hero {
    text-align: center;
    padding: 40px 0 16px;
}
.promo-hero h1 {
    font-family: Georgia, "Times New Roman", serif;
    font-size: 32px;
    font-weight: 400;
    letter-spacing: .8px;
    color: var(--gold);
    margin: 0 0 8px;
}
.promo-hero p {
    color: var(--muted);
    font-size: 15px;
    max-width: 520px;
    margin: 0 auto;
}
.promo-empty {
    text-align: center;
    padding: 60px 20px;
    color: var(--muted2);
}
@media (max-width: 600px) {
    .promo-grid { grid-template-columns: 1fr; }
    .promo-hero h1 { font-size: 24px; }
}
</style>

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
            $esCredito = (string)($p['tipo_tarjeta'] ?? '') === 'credito';
            $icono = $esCredito ? '💳' : '🏦';
            $tipoLabel = $esCredito ? 'Crédito' : 'Débito';
            $desde = (string)($p['fecha_desde'] ?? '');
            $hasta = (string)($p['fecha_hasta'] ?? '');
            $img = (string)($p['imagen'] ?? '');
        ?>
            <div class="promo-card">
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
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>