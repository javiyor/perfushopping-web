<?php
/**
 * Modal de envío de comprobante por WhatsApp con vista previa (imagen) del ticket.
 * Se incluye desde templates/admin/facturas/list.php e impresion/spooler.php.
 * Los botones de envío usan la clase .wa-send-comprobante con data-wa-id,
 * data-wa-phone y data-wa-text.
 */
?>
<div class="modal fade" id="waEnviarModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h5 class="modal-title fs-6"><i class="bi bi-whatsapp text-success"></i> Enviar comprobante por WhatsApp</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="alert alert-info py-1 px-2 small mb-2 text-start">
                    1) Descarg&aacute; el PDF del comprobante &middot; 2) Abr&iacute; WhatsApp &middot; 3) Adjuntalo en el chat.
                </div>
                <div id="waEnvioPreview" style="min-height:120px">
                    <div class="spinner-border text-success" role="status"></div>
                    <div class="small text-muted mt-2">Generando vista previa del ticket…</div>
                </div>
                <div id="waEnvioMsg" class="small mt-2"></div>
            </div>
            <div class="modal-footer py-2 flex-nowrap">
                <a class="btn btn-danger btn-sm text-nowrap" id="waBtnPdf" href="#" download><i class="bi bi-file-earmark-pdf"></i> PDF</a>
                <button class="btn btn-outline-secondary btn-sm text-nowrap" id="waBtnCopiar" disabled><i class="bi bi-clipboard"></i> Copiar imagen</button>
                <button class="btn btn-outline-secondary btn-sm text-nowrap" id="waBtnCompartir" style="display:none"><i class="bi bi-share"></i> Compartir.</button>
                <button class="btn btn-success btn-sm text-nowrap" id="waBtnAbrir"><i class="bi bi-whatsapp"></i> Abrir WhatsApp</button>
            </div>
        </div>
    </div>
</div>
<div id="waEnvioFrames" style="display:none"></div>

<script>
(function() {
    var H2C = 'https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js';
    var state = { id: 0, phone: '', text: '', blob: null, file: null };
    var modalEl = document.getElementById('waEnviarModal');
    var modal = null;
    var msgEl = document.getElementById('waEnvioMsg');
    var prevEl = document.getElementById('waEnvioPreview');
    var framesEl = document.getElementById('waEnvioFrames');

    function $(id) { return document.getElementById(id); }

    function cargarHtml2canvas() {
        return new Promise(function(res, rej) {
            if (window.html2canvas) return res(window.html2canvas);
            var s = document.createElement('script');
            s.src = H2C;
            s.onload = function() { res(window.html2canvas); };
            s.onerror = function() { rej(new Error('librería de captura no disponible')); };
            document.head.appendChild(s);
        });
    }

    function formatoTicket() {
        var fmt = '80mm';
        try { fmt = localStorage.getItem('perfushopping_print_format') || '80mm'; } catch (e) {}
        if (fmt !== '80mm' && fmt !== '58mm' && fmt !== 'a4') fmt = '80mm';
        return fmt;
    }

    function esperaImagenes(doc) {
        var imgs = Array.prototype.slice.call(doc.images || []);
        return Promise.all(imgs.map(function(img) {
            if (img.complete) return Promise.resolve();
            return new Promise(function(res) {
                img.addEventListener('load', res, { once: true });
                img.addEventListener('error', res, { once: true });
                setTimeout(res, 4000);
            });
        }));
    }

    function capturar(id) {
        var fmt = formatoTicket();
        return cargarHtml2canvas().then(function(h2c) {
            return new Promise(function(resolve, reject) {
                var frame = document.createElement('iframe');
                frame.style.width = fmt === 'a4' ? '210mm' : fmt;
                frame.style.border = '0';
                var to = setTimeout(function() { cleanup(); reject(new Error('timeout')); }, 20000);
                function cleanup() {
                    clearTimeout(to);
                    try { frame.remove(); } catch (e) {}
                }
                frame.onload = function() {
                    var doc = frame.contentDocument;
                    Promise.resolve()
                        .then(function() { return esperaImagenes(doc); })
                        .then(function() {
                            return h2c(doc.body, {
                                scale: 2,
                                backgroundColor: '#ffffff',
                                useCORS: true,
                                logging: false,
                                ignoreElements: function(el) {
                                    return !!(el && el.classList && el.classList.contains('no-print'));
                                },
                            });
                        })
                        .then(function(canvas) { cleanup(); resolve(canvas); })
                        .catch(function(e) { cleanup(); reject(e); });
                };
                frame.onerror = function() { cleanup(); reject(new Error('no se pudo cargar el ticket')); };
                frame.src = '/admin/facturas/imprimir/' + id + '?formato=' + encodeURIComponent(fmt);
                framesEl.appendChild(frame);
            });
        });
    }

    function mostrarError(detalle) {
        prevEl.innerHTML = '<div class="text-muted small py-3"><i class="bi bi-exclamation-triangle"></i> No se pudo generar la imagen del ticket'
            + (detalle ? ' (' + detalle + ')' : '') + '.</div>';
        msgEl.textContent = 'Podés abrir igual y enviar el mensaje de texto.';
    }

    function cargarPuntos(id) {
        fetch('/admin/facturas/puntos/' + id)
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (!d) return;
                var extra = '';
                if (d.obtenidos > 0 && d.totales > 0) {
                    extra = ' Sumaste ' + d.obtenidos + ' puntos. Total acumulado: ' + d.totales + ' puntos.';
                } else if (d.obtenidos > 0) {
                    extra = ' Sumaste ' + d.obtenidos + ' puntos.';
                }
                if (extra) state.text += extra;
            })
            .catch(function() {});
    }

    function cargarPdf(id) {
        var btn = $('waBtnPdf');
        if (btn) btn.href = '#';
        fetch('/admin/facturas/pdf/' + id)
            .then(function(r) { return r.blob(); })
            .then(function(b) {
                if (!b) return;
                state.pdfFile = new File([b], 'comprobante-' + id + '.pdf', { type: 'application/pdf' });
                if (btn) btn.href = URL.createObjectURL(b);
                if (navigator.canShare && navigator.canShare({ files: [state.pdfFile] })) {
                    state.file = state.pdfFile;
                    $('waBtnCompartir').style.display = '';
                }
            })
            .catch(function() {});
    }

    function abrir(id, phone, text) {
        state = { id: id, phone: phone, text: text, blob: null, file: null };
        framesEl.innerHTML = '';
        $('waBtnCopiar').disabled = true;
        $('waBtnCompartir').style.display = 'none';
        msgEl.textContent = '';
        prevEl.innerHTML = '<div class="spinner-border text-success" role="status"></div><div class="small text-muted mt-2">Generando vista previa del ticket.</div>';
        if (!modal) modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
        if (!id || !phone) {
            mostrarError('faltan datos');
            return;
        }
        cargarPuntos(id);
        cargarPdf(id);
        capturar(id).then(function(canvas) {
            prevEl.innerHTML = '';
            var img = document.createElement('img');
            img.src = canvas.toDataURL('image/png');
            img.className = 'img-fluid rounded border';
            img.style.maxHeight = '58vh';
            img.alt = 'Vista previa del ticket';
            prevEl.appendChild(img);
            canvas.toBlob(function(b) {
                if (!b) return;
                state.blob = b;
                $('waBtnCopiar').disabled = false;
                try {
                    var file = new File([b], 'comprobante.png', { type: 'image/png' });
                    if (navigator.canShare && navigator.canShare({ files: [file] })) {
                        state.file = file;
                        $('waBtnCompartir').style.display = '';
                    }
                } catch (e) {}
            }, 'image/png');
        }).catch(function(e) {
            mostrarError(e && e.message ? e.message : null);
        });
    }

    document.addEventListener('click', function(e) {
        var a = e.target && e.target.closest ? e.target.closest('a.wa-send-comprobante') : null;
        if (!a) return;
        e.preventDefault();
        abrir(
            parseInt(a.getAttribute('data-wa-id') || '0', 10),
            String(a.getAttribute('data-wa-phone') || '').replace(/\D/g, ''),
            String(a.getAttribute('data-wa-text') || '')
        );
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
        framesEl.innerHTML = '';
    });

    $('waBtnAbrir').addEventListener('click', function() {
        if (!state.phone) return;
        window.open('https://wa.me/' + state.phone + '?text=' + encodeURIComponent(state.text), '_blank');
    });

    $('waBtnCopiar').addEventListener('click', function() {
        if (!state.blob) return;
        if (!navigator.clipboard || !window.ClipboardItem) {
            msgEl.innerHTML = '<span class="text-danger">Tu navegador no permite copiar la imagen; usá "Compartir…".</span>';
            return;
        }
        navigator.clipboard.write([new ClipboardItem({ 'image/png': state.blob })]).then(function() {
            msgEl.innerHTML = '<span class="text-success">Imagen copiada. Abrí WhatsApp y pegala en el chat.</span>';
            $('waBtnAbrir').focus();
        }).catch(function() {
            msgEl.innerHTML = '<span class="text-danger">No se pudo copiar. Probá con "Compartir…".</span>';
        });
    });

    $('waBtnCompartir').addEventListener('click', function() {
        if (!state.file) return;
        if (navigator.share) {
            navigator.share({ files: [state.file], text: state.text }).catch(function() {});
        }
    });
})();
</script>
