<?php
$puntoVenta = (int)($puntoVenta ?? 0);
$impresora = $impresora ?? null;
?>
<div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Spooler de tickets</h4>
        <p class="text-muted small mb-0">
            PV <span class="badge bg-primary align-middle"> <?= $puntoVenta ?> </span>
            &middot; Dejá esta pestaña abierta en la PC del mostrador: imprime sola cada factura en cola.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-secondary btn-sm" id="btnSpoolerToggle" type="button"><i class="bi bi-pause-fill"></i> Pausar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/impresion/impresoras"><i class="bi bi-printer"></i> Impresoras</a>
    </div>
</div>

<div id="spoolerBanner"></div>

<div class="row g-2 mb-3">
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2 px-3">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Estado</div>
                <div class="fw-bold" id="spoolerStatus">Iniciando…</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2 px-3">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Pendientes</div>
                <div class="fw-bold" id="spoolerPending">0</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2 px-3">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Última revisión</div>
                <div class="fw-bold" id="spoolerLast">—</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-body py-2 px-3">
                <div class="text-muted" style="font-size:11px;text-transform:uppercase;letter-spacing:.04em">Último impreso</div>
                <div class="fw-bold" id="spoolerLastPrinted">—</div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info small py-2">
    <i class="bi bi-info-circle"></i>
    Para imprimir <strong>sin diálogo</strong>, abrí Chrome con el parámetro <code>--kiosk-printing</code>
    (acceso directo a esta página). Si no, el navegador pide confirmar cada ticket.
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-list-ol"></i> Cola de espera</span>
        <span class="small text-muted">se imprime de a una</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width:70px">Nº</th>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th style="width:150px">Encolado</th>
                    <th style="width:110px" class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="spoolerQueue">
                <tr><td colspan="5" class="text-center text-muted small py-4">Sin pendientes</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="spoolerFrames" style="position:absolute;left:-10000px;top:0;width:1px;height:1px;overflow:hidden;opacity:0"></div>

<script>
(function() {
    var CSRF = <?= json_encode($csrf ?? '') ?>;
    var FORMATO = <?= json_encode((string)(($impresora['formato'] ?? '') ?: '80mm')) ?>;
    if (FORMATO !== '80mm' && FORMATO !== '58mm') FORMATO = '80mm';
    var running = true;
    var busy = false;
    var queue = [];
    var currentFrame = null;

    var btn = document.getElementById('btnSpoolerToggle');
    btn.addEventListener('click', function() {
        running = !running;
        btn.className = running ? 'btn btn-outline-secondary btn-sm' : 'btn btn-accent btn-sm';
        btn.innerHTML = running ? '<i class="bi bi-pause-fill"></i> Pausar' : '<i class="bi bi-play-fill"></i> Reanudar';
        setStatus(running ? 'En espera' : 'Pausado');
        if (running) poll();
    });

    // Banner de impresora asignada / faltante
    (function() {
        var el = document.getElementById('spoolerBanner');
        if (!el) return;
        <?php if (!$impresora): ?>
        el.innerHTML = '<div class="alert alert-warning py-2"><i class="bi bi-exclamation-triangle"></i> '
            + 'No hay impresora activa para el PV <?= $puntoVenta ?>. '
            + '<a href="/admin/impresion/impresoras">Asigná una acá</a>. Las facturas igual quedan en cola.</div>';
        <?php else: ?>
        el.innerHTML = '<div class="alert alert-success py-2"><i class="bi bi-printer"></i> '
            + 'Imprimiendo con <strong><?= htmlspecialchars((string)($impresora['nombre'] ?? '')) ?></strong> '
            + '(<?= htmlspecialchars((string)(($impresora['formato'] ?? '') ?: '80mm')) ?>).</div>';
        <?php endif; ?>
    })();

    function setStatus(t) {
        document.getElementById('spoolerStatus').textContent = t;
    }

    // El ticket (iframe) avisa con postMessage cuando terminó de imprimir.
    window.addEventListener('message', function(ev) {
        if (!ev.data || ev.data.type !== 'ticket_impreso') return;
        var cur = currentFrame;
        if (cur && cur.resolve) cur.resolve('impreso');
    });

    function escSpooler(s) {
        var d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function whatsappText(j) {
        var total = (parseInt(j.total_cents || 0, 10) / 100).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        return 'Hola ' + (j.cliente_nombre || '') + ', le enviamos su comprobante ' + (j.factura_codigo || ('#' + j.factura_id)) + ' por un total de $' + total + '. ¡Gracias por su compra!';
    }

    function stamp() {
        document.getElementById('spoolerLast').textContent = new Date().toLocaleTimeString('es-AR');
    }

    function ack(jobId, estado, mensaje) {
        return fetch('/admin/impresion/cola/ack', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ _csrf: CSRF, job_id: jobId, estado: estado, mensaje: mensaje || null }),
        }).then(function(r) { return r.json(); }).catch(function() { return { ok: false }; });
    }

    function printJob(job) {
        busy = true;
        setStatus('Imprimiendo ' + (job.factura_codigo || ('#' + job.factura_id)) + '…');
        var frame = document.createElement('iframe');
        frame.style.width = FORMATO === '58mm' ? '58mm' : '80mm';
        frame.style.border = '0';
        frame.src = '/admin/facturas/imprimir/' + job.factura_id + '?formato=' + FORMATO + '&auto=1';
        document.getElementById('spoolerFrames').appendChild(frame);

        var settled = false;
        var finish = function(estado, mensaje) {
            if (settled) return;
            settled = true;
            clearTimeout(safety);
            try { frame.remove(); } catch (e) {}
            currentFrame = null;
            ack(job.id, estado, mensaje).then(function() {
                if (estado === 'impreso') {
                    document.getElementById('spoolerLastPrinted').textContent = (job.factura_codigo || ('#' + job.factura_id)) + ' (' + new Date().toLocaleTimeString('es-AR') + ')';
                }
                busy = false;
                poll();
            });
        };
        currentFrame = { resolve: function() { finish('impreso', null); } };
        // Si el navegador no emite postMessage (sin --kiosk-printing puede pasar),
        // damos por impreso a los 15s para no trabar la cola.
        var safety = setTimeout(function() { finish('impreso', null); }, 15000);
    }

    function renderQueue(jobs) {
        var tb = document.getElementById('spoolerQueue');
        document.getElementById('spoolerPending').textContent = jobs.length;
        if (!jobs.length) {
            tb.innerHTML = '<tr><td colspan="5" class="text-muted text-center small">Sin pendientes</td></tr>';
            return;
        }
        tb.innerHTML = '';
        jobs.forEach(function(j) {
            var tr = document.createElement('tr');
            var phone = String(j.cliente_tele || '').replace(/\D/g, '');
            var wText = whatsappText(j);
            var waAttr = function(s) {
                return String(s || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/'/g, '&#39;');
            };
            var waBtn = phone
                ? '<a class="btn btn-sm btn-outline-success py-0 px-1 wa-send-comprobante" target="_blank" title="Enviar por WhatsApp con imagen del ticket" href="https://wa.me/' + phone + '?text=' + encodeURIComponent(wText) + '" data-wa-id="' + (j.factura_id || 0) + '" data-wa-phone="' + waAttr(phone) + '" data-wa-text="' + waAttr(wText) + '"><i class="bi bi-whatsapp"></i></a>'
                : '<button class="btn btn-sm btn-outline-secondary py-0 px-1" type="button" disabled title="Sin teléfono"><i class="bi bi-whatsapp"></i></button>';
            tr.innerHTML = '<td class="small">#' + j.id + '</td>'
                + '<td><strong>' + (j.factura_codigo || ('#' + j.factura_id)) + '</strong></td>'
                + '<td class="small">' + escSpooler(j.cliente_nombre || '') + '</td>'
                + '<td class="small text-muted">' + (j.created_at || '') + '</td>'
                + '<td><div class="d-flex gap-1">'
                + '<a class="btn btn-sm btn-outline-secondary py-0 px-1" target="_blank" title="Imprimir" href="/admin/facturas/imprimir/' + j.factura_id + '?formato=' + FORMATO + '"><i class="bi bi-printer"></i></a>'
                + waBtn
                + '</div></td>';
            tb.appendChild(tr);
        });
    }

    function poll() {
        if (!running || busy) return;
        fetch('/admin/impresion/cola')
            .then(function(r) { return r.json(); })
            .then(function(d) {
                stamp();
                var jobs = (d && d.pendientes) || [];
                renderQueue(jobs);
                if (!d.impresora) {
                    setStatus('Sin impresora asignada al PV');
                    return;
                }
                if (!jobs.length) {
                    setStatus('En espera');
                    return;
                }
                queue = jobs;
                printJob(queue.shift());
            })
            .catch(function() { setStatus('Error de conexión'); });
    }

    setStatus('En espera');
    poll();
    setInterval(poll, 5000);
})();
</script>
<?php require __DIR__ . '/../facturas/wa_enviar_modal.php'; ?>
