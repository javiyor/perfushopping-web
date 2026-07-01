<?php
$formatos = [
    'a4'   => ['label' => 'A4', 'desc' => 'Hoja completa — Listados y reportes'],
    '80mm' => ['label' => 'Ticket 80mm', 'desc' => 'Ticket continuo de 80mm — Facturas y recibos'],
    '58mm' => ['label' => 'Ticket 58mm', 'desc' => 'Ticket continuo de 58mm — Facturas y recibos'],
];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Configuración de impresión</h4>
        <p class="text-muted small">Elegí el formato por defecto para cada tipo de documento</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Facturas y Recibos</div>
            <div class="card-body">
                <p class="small text-muted">Formato que se usará al imprimir comprobantes desde el detalle o desde facturación POS.</p>

                <div class="d-flex flex-column gap-2" id="facturaFormatos">
                    <?php foreach ($formatos as $key => $f): ?>
                    <label class="d-flex align-items-center gap-3 p-3 border rounded cursor-pointer formato-option" data-formato="<?= $key ?>" style="cursor:pointer">
                        <input type="radio" name="formato_factura" value="<?= $key ?>" class="form-check-input mt-0" />
                        <div>
                            <strong class="d-block"><?= htmlspecialchars($f['label']) ?></strong>
                            <small class="text-muted"><?= htmlspecialchars($f['desc']) ?></small>
                        </div>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Otros listados</div>
            <div class="card-body">
                <p class="small text-muted">Reportes, stock, etc. Siempre se imprimen en A4.</p>
                <div class="p-3 border rounded bg-light">
                    <strong>A4</strong>
                    <div class="small text-muted">Hoja completa — tamaño predeterminado</div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="text-center mt-4">
    <button class="btn btn-accent" id="btnGuardar"><i class="bi bi-check-lg"></i> Guardar configuración</button>
    <span id="savedMsg" class="ms-2 text-success small fw-bold" style="display:none">✓ Configuración guardada</span>
</div>

<script>
(function() {
    const KEY = 'perfushopping_print_format';
    const radios = document.querySelectorAll('input[name="formato_factura"]');
    const saved = localStorage.getItem(KEY) || '80mm';

    // Restore saved
    radios.forEach(r => { if (r.value === saved) r.checked = true; });
    // Highlight selected
    document.querySelectorAll('.formato-option').forEach(el => {
        const radio = el.querySelector('input');
        if (radio && radio.checked) el.classList.add('border-primary', 'bg-light');
    });
    radios.forEach(r => {
        r.addEventListener('change', function() {
            document.querySelectorAll('.formato-option').forEach(el => el.classList.remove('border-primary', 'bg-light'));
            if (this.checked) this.closest('.formato-option').classList.add('border-primary', 'bg-light');
        });
    });
    // Also click on label
    document.querySelectorAll('.formato-option').forEach(el => {
        el.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            const radio = this.querySelector('input');
            if (radio) radio.click();
        });
    });

    document.getElementById('btnGuardar').addEventListener('click', function() {
        let selected = '80mm';
        radios.forEach(r => { if (r.checked) selected = r.value; });
        localStorage.setItem(KEY, selected);
        const msg = document.getElementById('savedMsg');
        msg.style.display = 'inline';
        setTimeout(() => { msg.style.display = 'none'; }, 2500);
    });
})();
</script>
