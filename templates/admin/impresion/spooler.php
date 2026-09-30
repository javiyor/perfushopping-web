<?php
$puntoVenta = (int)($puntoVenta ?? 0);
$impresora = $impresora ?? null;
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Spooler de tickets — PV <?= $puntoVenta ?></h4>
        <p class="text-muted small">Dejá esta página abierta en la PC del punto de venta: imprime sola cada factura que entra en cola</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-success btn-sm" id="btnSpoolerToggle" type="button"><i class="bi bi-pause-fill"></i> Pausar</button>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/impresion/impresoras">Impresoras</a>
    </div>
</div>

<?php if (!$impresora): ?>
<div class="alert alert-warning">
    <i class="bi bi-exclamation-triangle"></i>
    No hay impresora activa asignada al punto de venta <?= $puntoVenta ?>.
    <a href="/admin/impresion/impresoras">Asigná una acá</a>: las facturas igual quedan en cola.
</div>
<?php else: ?>
<div class="alert alert-success py-2 small">
    <i class="bi bi-printer"></i>
    Impresora asignada: <strong><?= htmlspecialchars((string)($impresora['nombre'] ?? '')) ?></strong>
</div>
<?php endif; ?>

<div class="alert alert-info small">
    Para impresión <strong>sin diálogo</strong>, abrí Chrome con el parámetro <code>--kiosk-printing</code>
    (acceso directo a esta página). Si no, el navegador pide confirmar cada ticket.
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Estado</span>
        <span class="small text-muted" id="spoolerStatus">Iniciando…</span>
    </div>
    <div class="card-body small">
        Última revisión: <strong id="spoolerLast">—</strong> ·
        Pendientes: <strong id="spoolerPending">0</strong> ·
        Último impreso: <strong id="spoolerLastPrinted">—</strong>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Cola pendiente</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Factura</th>
                    <th>Cliente</th>
                    <th>Encolado</th>
                    <th style="width:100px"></th>
                </tr>
            </thead>
            <tbody id="spoolerQueue">
                <tr><td colspan="4" class="text-muted text-center small">Sin pendientes</td></tr>
            </tbody>
        </table>
    </div>
</div>

<div id="spoolerFrames" style="display:none"></div>

<script>
(function() {
    var CSRF = <?= json_encode($csrf ?? '') ?>;
    var running = true;
    var busy = false;
    var queue = [];

    var btn = document.getElementById('btnSpoolerToggle');
    btn.addEventListener('click', function() {
        running = !running;
        btn.className = running ? 'btn btn-success btn-sm' : 'btn btn-outline-secondary btn-sm';
        btn.innerHTML = running ? '<i class="bi bi-pause-fill"></i> Pausar' : '<i class="bi bi-play-fill"></i> Reanudar';
        setStatus(running ? 'En espera' : 'Pausado');
        if (running) poll();
    });

    function setStatus(t) {
        document.getElementById('spoolerStatus').textContent = t;
    }

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
        var done = false;
        var finish = function(estado, mensaje) {
            if (done) return;
            done = true;
            ack(job.id, estado, mensaje).then(function() {
                document.getElementById('spoolerLastPrinted').textContent = (job.factura_codigo || ('#' + job.factura_id)) + ' (' + new Date().toLocaleTimeString('es-AR') + ')';
                busy = false;
                poll();
            });
        };
        try {
            var frame = document.createElement('iframe');
            var to = setTimeout(function() { finish('error', 'timeout de impresión'); }, 45000);
            frame.onload = function() {
                setTimeout(function() { clearTimeout(to); finish('impreso', null); }, 2000);
            };
            frame.onerror = function() { clearTimeout(to); finish('error', 'no se pudo cargar el ticket'); };
            frame.src = '/admin/facturas/imprimir/' + job.factura_id + '?formato=80mm&auto=1';
            document.getElementById('spoolerFrames').appendChild(frame);
            setTimeout(function() { try { frame.remove(); } catch (e) {} }, 60000);
        } catch (e) {
            finish('error', String(e && e.message || e));
        }
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
            var waBtn = phone
                ? '<a class="btn btn-sm btn-outline-success py-0 px-1" target="_blank" title="Enviar por WhatsApp" href="https://wa.me/' + phone + '?text=' + encodeURIComponent(whatsappText(j)) + '"><i class="bi bi-whatsapp"></i></a>'
                : '<button class="btn btn-sm btn-outline-secondary py-0 px-1" type="button" disabled title="Sin teléfono"><i class="bi bi-whatsapp"></i></button>';
            tr.innerHTML = '<td class="small">#' + j.id + '</td>'
                + '<td><strong>' + (j.factura_codigo || ('#' + j.factura_id)) + '</strong></td>'
                + '<td class="small">' + escSpooler(j.cliente_nombre || '') + '</td>'
                + '<td class="small text-muted">' + (j.created_at || '') + '</td>'
                + '<td><div class="d-flex gap-1">'
                + '<a class="btn btn-sm btn-outline-secondary py-0 px-1" target="_blank" title="Imprimir" href="/admin/facturas/imprimir/' + j.factura_id + '?formato=80mm"><i class="bi bi-printer"></i></a>'
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
