<?php
use Perfushopping\Web\Support\Format;

$voucher = $voucher ?? [];
$empresa = $empresa ?? [];
$design = $design ?? [];
$branches = $branches ?? [];
$logoUrl = $logoUrl ?? '';
$modeloUrl = $modeloUrl ?? '';
$formato = ($formato ?? 'a5') === 'etiqueta' ? 'etiqueta' : 'a5';
$esEtiqueta = $formato === 'etiqueta';
$modeloDisponible = trim((string)$modeloUrl) !== '';
$phoneTo = preg_replace('/\D+/', '', (string)(($voucher['destinatario_telefono'] ?? '') ?: ($voucher['remitente_telefono'] ?? ''))) ?? '';
$shareText = 'Te enviaron un voucher de regalo de ' . Format::moneyFromCents((int)($voucher['importe_cents'] ?? 0)) . ': ' . (string)($voucherUrl ?? '');
$titulo = (string)($design['titulo'] ?? '¡FELICITACIONES!');
$cinta = (string)($design['cinta'] ?? 'GIFT VOUCHER');
$mensaje = (string)($design['mensaje'] ?? 'Qué alegría compartir este regalo con vos.\n\nGracias por ser parte de este momento.\n\nDeseamos que este regalo te acompañe en recuerdos hermosos.');
$fraseInferior = (string)($design['frase_inferior'] ?? '¡Te esperamos con mucho cariño!');
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($titulo) ?> · <?= htmlspecialchars($voucher['codigo'] ?? '') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Great+Vibes&family=Playfair+Display:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        @page { size: <?= $esEtiqueta ? '100mm 60mm' : 'A5 portrait' ?>; margin: <?= $esEtiqueta ? '2mm' : '8mm' ?>; }
        :root { --gold:#c9b037; --gold-light:#f7e7a0; --gold-dark:#7e5b14; --black:#000; }
        * { box-sizing:border-box; }
        body { margin:0; min-height:100vh; background:#101114; color:#fff; font:14px/1.45 Arial,sans-serif; }
        .toolbar { max-width:1100px; margin:18px auto; padding:12px 16px; display:flex; gap:8px; flex-wrap:wrap; justify-content:center; align-items:center; }
        .toolbar a,.toolbar button { border:1px solid #c9b037; border-radius:5px; padding:9px 13px; color:#f7e7a0; background:#161719; text-decoration:none; cursor:pointer; font-size:13px; }
        .toolbar a:hover,.toolbar button:hover { background:#2a2415; }
        .toolbar button:disabled { opacity:.45; cursor:not-allowed; }
        .missing-photo { max-width:760px; margin:0 auto 14px; padding:10px 14px; background:#362b10; border:1px solid var(--gold); border-radius:5px; color:#ffedb1; font-size:13px; text-align:center; }
        .voucher-artwork { position:relative; isolation:isolate; overflow:hidden; width:min(94vw,760px); aspect-ratio:4/5; margin:0 auto 28px; padding:3.5%; background:#000; color:#fff; border:2px solid var(--gold); box-shadow:0 0 25px #c9b03755; }
        .voucher-artwork::before,.voucher-artwork::after { content:'✦'; position:absolute; z-index:4; color:var(--gold-light); text-shadow:0 0 10px #f5d75e; font-size:clamp(20px,4vw,42px); }
        .voucher-artwork::before { top:1.3%; left:1.4%; }
        .voucher-artwork::after { right:1.4%; bottom:1.3%; }
        .sparkles { position:absolute; inset:0; z-index:-1; opacity:.45; background:radial-gradient(circle at 8% 12%,#f7e7a0 0 1px,transparent 2px),radial-gradient(circle at 92% 19%,#c9b037 0 1px,transparent 2px),radial-gradient(circle at 12% 78%,#c9b037 0 1px,transparent 2px),radial-gradient(circle at 86% 71%,#f7e7a0 0 1px,transparent 2px),radial-gradient(ellipse at 52% 0%,#6e571d35,transparent 42%); background-size:137px 149px,173px 167px,191px 211px,227px 197px,100% 100%; }
        .gold-frame { position:absolute; inset:2.1%; z-index:3; pointer-events:none; border:1px solid #c9b037c9; }
        .gold-frame::before,.gold-frame::after { content:''; position:absolute; width:11%; height:8%; border-color:var(--gold-light); border-style:solid; filter:drop-shadow(0 0 4px #c9b037); }
        .gold-frame::before { top:0; left:0; border-width:2px 0 0 2px; }
        .gold-frame::after { right:0; bottom:0; border-width:0 2px 2px 0; }
        .corner { position:absolute; color:var(--gold-light); font-size:clamp(17px,3vw,30px); line-height:1; text-shadow:0 0 8px #c9b037; }
        .corner-tr { top:-.4%; right:1.5%; }
        .corner-bl { bottom:-.4%; left:1.5%; }
        .voucher-layout { position:relative; z-index:2; height:100%; display:grid; grid-template-rows:auto auto minmax(0,1fr) auto auto auto; gap:1.2%; }
        .brand { display:flex; justify-content:center; align-items:center; min-height:8%; text-align:center; }
        .brand-logo { display:block; max-width:44%; max-height:9vh; object-fit:contain; }
        .title-area { text-align:center; }
        .congrats { margin:0; color:var(--gold-light); font:700 clamp(23px,5vw,50px)/1.05 'Playfair Display',Georgia,serif; letter-spacing:.08em; text-shadow:0 0 11px #c9b03799; }
        .ribbon { position:relative; display:inline-block; margin-top:1%; padding:1.1% 9%; color:#080808; background:linear-gradient(100deg,#8b681b,#f8e7a0 28%,#c9b037 58%,#fff0a8 78%,#94701f); font:700 clamp(12px,2.3vw,21px)/1.1 'Playfair Display',Georgia,serif; letter-spacing:.2em; box-shadow:0 2px 9px #d7b64a88; }
        .hero { display:grid; grid-template-columns:46% 54%; min-height:0; border-top:1px solid #c9b03788; border-bottom:1px solid #c9b03788; overflow:hidden; }
        .model-frame { position:relative; min-height:0; background:linear-gradient(155deg,#181818,#070707); overflow:hidden; }
        .model-photo { display:block; width:100%; height:100%; object-fit:cover; object-position:center 25%; }
        .photo-missing { position:absolute; inset:0; display:grid; place-items:center; padding:8%; text-align:center; color:#d3bd73; font:500 clamp(13px,2.1vw,20px)/1.4 'Playfair Display',Georgia,serif; background:radial-gradient(ellipse at 50% 30%,#40321666,transparent 62%); }
        .message { align-self:center; padding:7%; color:#f4f0e5; font:400 clamp(12px,2vw,19px)/1.5 'Playfair Display',Georgia,serif; white-space:pre-line; }
        .heart { display:block; margin:5% 0 4%; text-align:center; color:var(--gold-light); font-size:clamp(18px,3vw,28px); text-shadow:0 0 10px #e8c954aa; }
        .recipient { margin-top:4%; color:#d2d2d2; text-align:center; font:12px/1.3 Arial,sans-serif; }
        .recipient-name { display:block; margin-top:2%; color:#fff; font:600 clamp(16px,2.7vw,26px)/1.2 'Playfair Display',Georgia,serif; }
        .voucher-data { display:grid; grid-template-columns:1fr 1fr; align-items:center; gap:2% 4%; padding:1% 2%; }
        .value { color:var(--gold-light); font:600 clamp(16px,3vw,29px)/1.2 'Playfair Display',Georgia,serif; }
        .sender { color:#ddd; text-align:right; font:11px/1.3 Arial,sans-serif; }
        .sender strong { color:#fff; font-family:'Playfair Display',Georgia,serif; }
        .voucher-code { grid-column:1/-1; color:var(--gold-light); text-align:center; font:600 clamp(13px,2.4vw,23px)/1.2 'Playfair Display',Georgia,serif; letter-spacing:.17em; }
        .voucher-code span { display:block; margin-bottom:3px; color:#adadad; font:8px/1.2 Arial,sans-serif; letter-spacing:.2em; text-transform:uppercase; }
        .closing-brush { display:block; width:92%; justify-self:center; padding:2% 1%; color:#101010; text-align:center; background:linear-gradient(95deg,#98701d,#f8e9a2 25%,#c9b037 52%,#fff0a8 74%,#8e6819); clip-path:polygon(2% 14%,98% 0,96% 78%,4% 100%); font:400 clamp(17px,3vw,30px)/1.1 'Great Vibes','Brush Script MT',cursive; }
        .pickup-locations { color:#e7d79d; text-align:center; font:500 8px/1.25 Arial,sans-serif; letter-spacing:.02em; }
        .pickup-title { display:block; margin-bottom:1mm; color:var(--gold-light); font:600 10px/1.2 'Playfair Display',Georgia,serif; }
        .pickup-address { margin:.5mm 0; }
        .is-label .voucher-layout { gap:1%; grid-template-rows:auto auto minmax(0,1fr) auto auto auto; }
        .is-label .brand { min-height:0; }
        .is-label .brand-logo { max-height:8mm; }
        .is-label .congrats { font-size:17px; }
        .is-label .ribbon { font-size:10px; margin-top:0; padding:1% 8%; }
        .is-label .hero { grid-template-columns:35% 65%; }
        .is-label .message { padding:4%; font-size:8px; line-height:1.25; }
        .is-label .heart { margin:1% 0; font-size:12px; }
        .is-label .recipient { font-size:7px; margin-top:1%; }
        .is-label .recipient-name { font-size:11px; margin-top:1%; }
        .is-label .value { font-size:12px; }
        .is-label .sender { font-size:6px; }
        .is-label .voucher-code { font-size:11px; }
        .is-label .voucher-code span { font-size:5px; margin-bottom:0; }
        .is-label .closing-brush { display:none; }
        .is-label .pickup-locations { font-size:5px; line-height:1.05; }
        .is-label .pickup-title { font-size:6px; margin-bottom:.3mm; }
        .is-label .pickup-address { margin:0; }
        @media (max-width:600px) {
            .voucher-artwork { width:96vw; }
            .message { padding:5%; }
        }
        @media print {
            body { min-height:0; background:#fff; }
            .toolbar,.missing-photo { display:none !important; }
            .voucher-artwork { width:132mm; height:165mm; max-width:none; aspect-ratio:4/5; margin:0 auto; box-shadow:none; print-color-adjust:exact; -webkit-print-color-adjust:exact; }
            .voucher-artwork.is-label { width:93mm; height:55.8mm; aspect-ratio:5/3; }
            .model-photo { print-color-adjust:exact; -webkit-print-color-adjust:exact; }
        }
    </style>
</head>
<body>
    <nav class="toolbar">
        <button type="button" onclick="window.print()">Imprimir <?= $esEtiqueta ? 'etiqueta 10 × 6 cm' : 'voucher A5' ?></button>
        <?php if (!$esEtiqueta): ?><a href="/voucher/<?= rawurlencode((string)$voucher['codigo']) ?>?formato=etiqueta">Vista etiqueta 10 × 6</a><?php else: ?><a href="/voucher/<?= rawurlencode((string)$voucher['codigo']) ?>">Vista A5</a><?php endif; ?>
        <button type="button" id="downloadPng" <?= $modeloDisponible ? '' : 'disabled' ?>>Descargar PNG</button>
        <button type="button" id="downloadJpg" <?= $modeloDisponible ? '' : 'disabled' ?>>Descargar JPG</button>
        <button type="button" id="downloadPdf" <?= $modeloDisponible ? '' : 'disabled' ?>>Descargar PDF</button>
        <?php if ($phoneTo !== ''): ?><a target="_blank" rel="noopener" href="https://wa.me/<?= htmlspecialchars($phoneTo) ?>?text=<?= rawurlencode($shareText) ?>">Enviar por WhatsApp</a><?php endif; ?>
    </nav>
    <?php if (!$modeloDisponible): ?><div class="missing-photo">Para completar el diseño y habilitar las descargas, cargá una fotografía real de la modelo sosteniendo la bolsa de regalo en la sección “Diseño del voucher”.</div><?php endif; ?>

    <main id="voucherArtwork" class="voucher-artwork<?= $esEtiqueta ? ' is-label' : '' ?>">
        <div class="sparkles"></div>
        <div class="gold-frame"><span class="corner corner-tr">✦</span><span class="corner corner-bl">✦</span></div>
        <div class="voucher-layout">
            <header class="brand">
                <?php if (!empty($logoUrl)): ?><img class="brand-logo" src="<?= htmlspecialchars($logoUrl) ?>" alt="Perfushopping" /><?php endif; ?>
            </header>
            <section class="title-area">
                <h1 class="congrats"><?= htmlspecialchars($design['titulo'] ?? '¡FELICITACIONES!') ?></h1>
                <div class="ribbon"><?= htmlspecialchars($design['cinta'] ?? 'GIFT VOUCHER') ?></div>
            </section>
            <section class="hero">
                <div class="model-frame">
                    <?php if ($modeloDisponible): ?>
                    <img class="model-photo" src="<?= htmlspecialchars($modeloUrl) ?>" alt="Modelo sosteniendo una bolsa de regalo Perfushopping" crossorigin="anonymous" />
                    <?php else: ?><div class="photo-missing">Fotografía de modelo<br />con bolsa de regalo</div><?php endif; ?>
                </div>
                <div class="message">
                    <?= nl2br(htmlspecialchars($design['mensaje'] ?? '')) ?>
                    <span class="heart" aria-hidden="true">♥</span>
                    <div class="recipient">Para:<span class="recipient-name"><?= htmlspecialchars($voucher['destinatario_nombre'] ?? '') ?></span></div>
                </div>
            </section>
            <section class="voucher-data">
                <div class="value">Valor <?= Format::moneyFromCents((int)($voucher['importe_cents'] ?? 0)) ?></div>
                <div class="sender">De <strong><?= htmlspecialchars($voucher['remitente_nombre'] ?? '') ?></strong><br /><?= htmlspecialchars($voucher['remitente_telefono'] ?? '') ?></div>
                <div class="voucher-code"><span>Número de voucher</span><?= htmlspecialchars($voucher['codigo'] ?? '') ?></div>
            </section>
            <footer class="closing-brush"><?= htmlspecialchars($design['frase_inferior'] ?? '¡Te esperamos con mucho cariño!') ?></footer>
            <section class="pickup-locations">
                <strong class="pickup-title">Retirar en:</strong>
                <?php foreach ($branches as $branch): ?>
                    <?php $direccion = trim((string)($branch['direccion'] ?? '')); ?>
                    <?php if ($direccion !== ''): ?><div class="pickup-address"><?= htmlspecialchars($branch['nomsuc'] ?? '') ?> — <?= htmlspecialchars($direccion) ?></div><?php endif; ?>
                <?php endforeach; ?>
            </section>
        </div>
    </main>

    <?php if ($modeloDisponible): ?>
    <script>
    (function(){
        const artwork=document.getElementById('voucherArtwork');
        const format=<?= json_encode($esEtiqueta ? 'etiqueta' : 'a5') ?>;
        const baseName='voucher-<?= htmlspecialchars(preg_replace('/[^A-Z0-9-]/', '', (string)($voucher['codigo'] ?? '')) ?: 'regalo') ?>-'+format;
        const loadCapture=function(){
            return new Promise(function(resolve,reject){
                if(window.html2canvas) return resolve(window.html2canvas);
                const script=document.createElement('script');
                script.src='https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
                script.onload=function(){ resolve(window.html2canvas); };
                script.onerror=function(){ reject(new Error('No se pudo cargar la herramienta de exportación.')); };
                document.head.appendChild(script);
            });
        };
        const renderCanvas=async function(){
            const html2canvas=await loadCapture();
            if(document.fonts && document.fonts.ready) await document.fonts.ready;
            const image=artwork.querySelector('.model-photo');
            if(image && !image.complete) await new Promise((resolve,reject)=>{ image.onload=resolve; image.onerror=()=>reject(new Error('No se pudo cargar la fotografía del voucher.')); });
            return html2canvas(artwork,{scale:3,backgroundColor:'#000000',useCORS:true,allowTaint:false,logging:false});
        };
        const saveBlob=function(blob,extension){
            const url=URL.createObjectURL(blob); const anchor=document.createElement('a');
            anchor.href=url; anchor.download=baseName+'.'+extension; document.body.appendChild(anchor); anchor.click(); anchor.remove();
            setTimeout(()=>URL.revokeObjectURL(url),1500);
        };
        const run=async function(button,action){
            button.disabled=true; const old=button.textContent; button.textContent='Preparando…';
            try { await action(await renderCanvas()); }
            catch(error){ alert(error.message||'No se pudo exportar el voucher.'); }
            finally { button.disabled=false; button.textContent=old; }
        };
        document.getElementById('downloadPng').addEventListener('click',function(){ run(this,canvas=>new Promise((resolve,reject)=>canvas.toBlob(blob=>blob?resolve(saveBlob(blob,'png')):reject(new Error('No se pudo crear la imagen PNG.')),'image/png'))); });
        document.getElementById('downloadJpg').addEventListener('click',function(){ run(this,canvas=>new Promise((resolve,reject)=>canvas.toBlob(blob=>blob?resolve(saveBlob(blob,'jpg')):reject(new Error('No se pudo crear la imagen JPG.')),'image/jpeg',.96))); });
        document.getElementById('downloadPdf').addEventListener('click',function(){
            run(this,async canvas=>{
                const response=await fetch('/voucher/<?= rawurlencode((string)($voucher['codigo'] ?? '')) ?>/pdf',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({image:canvas.toDataURL('image/jpeg',.96),formato:format})});
                if(!response.ok){ const error=await response.json().catch(()=>({})); throw new Error(error.error||'No se pudo generar el PDF.'); }
                saveBlob(await response.blob(),'pdf');
            });
        });
    })();
    </script>
    <?php endif; ?>
</body>
</html>
