<?php
$bancos = $bancos ?? [];
$bancosLista = $bancosLista ?? [];
$csrfToken = $csrf ?? '';
$tipo = $tipo ?? 'propio';
$isTercero = $tipo === 'tercero';
?>
<style>
.cheque-form .form-label { font-size: 11px; text-transform: uppercase; letter-spacing: .05em; margin-bottom: 2px; }
.cheque-form .form-control, .cheque-form .form-select { font-size: 13px; }
.cheque-form .form-text { font-size: 11px; }
.cheque-title-icon { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; background: linear-gradient(135deg, #d8b25a, #b98a2e); color: #fff; }
.cheque-section { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .06em; color: #8a6d1c; border-bottom: 1px solid #f0e3bd; padding-bottom: 4px; margin: 14px 0 10px; }
.banco-opt { border-bottom: 1px solid #eee; border-radius: 0; padding: 6px 10px; width: 100%; text-align: left; background: #fff; }
.banco-opt:last-child { border-bottom: none; }
.banco-opt:hover { background: #fdf6e3; }
</style>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div class="d-flex gap-2 align-items-center">
        <div class="cheque-title-icon"><i class="bi bi-bank"></i></div>
        <div>
            <h4 class="fw-bold mb-0"><?= $isTercero ? 'Cargar cheque de tercero' : 'Emitir cheque propio' ?></h4>
            <p class="text-muted small mb-0"><?= $isTercero ? 'Cheque recibido de un cliente/proveedor, ingresa a cartera' : 'Nuevo cheque de la empresa' ?></p>
        </div>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/cheques">Volver</a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm cheque-form">
            <div class="card-body">
                <form method="post" action="/admin/cheques/emitir/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                    <input type="hidden" name="tipo" value="<?= $isTercero ? 'tercero' : 'propio' ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Tipo de cheque</label>
                        <select class="form-select" id="chequeTipo" onchange="window.location.href='/admin/cheques/emitir?tipo=' + this.value">
                            <option value="propio" <?= !$isTercero ? 'selected' : '' ?>>Cheque propio</option>
                            <option value="tercero" <?= $isTercero ? 'selected' : '' ?>>Cheque de tercero</option>
                        </select>
                    </div>

                    <?php if (!$isTercero): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cuenta bancaria <span class="text-danger">*</span></label>
                        <select class="form-select" name="banco_cuenta_id" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($bancos as $b): ?>
                                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars((string)($b['banco'] ?? '') . ' — ' . ($b['numero_cuenta'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>

                    <div class="cheque-section">Datos del cheque</div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">N° de cheque</label>
                            <input class="form-control" name="numero_cheque" placeholder="Ej: 00012345" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Banco emisor</label>
                            <?php if ($isTercero): ?>
                                <div id="bancoDrop">
                                    <button type="button" class="form-select form-select-sm text-start d-flex justify-content-between align-items-center" id="bancoDropBtn">
                                        <span id="bancoDropLabel" class="text-muted">— Seleccionar —</span>
                                        <i class="bi bi-chevron-down"></i>
                                    </button>
                                    <input type="hidden" name="banco_id" id="bancoIdHidden" value="" />
                                    <div class="card shadow-sm mt-1" id="bancoDropMenu" style="display:none;position:absolute;z-index:1050;width:100%;max-height:260px;overflow-y:auto">
                                        <div class="p-1">
                                            <input class="form-control form-control-sm" id="bancoDropSearch" placeholder="Buscar banco..." autocomplete="off" />
                                        </div>
                                        <div id="bancoDropList">
                                            <?php if (!$bancosLista): ?>
                                                <div class="text-muted small p-2">Sin bancos cargados</div>
                                            <?php else: ?>
                                                <?php foreach ($bancosLista as $b): ?>
                                                    <button type="button" class="banco-opt" data-id="<?= (int)$b['idban'] ?>" data-nombre="<?= htmlspecialchars((string)($b['nombanc'] ?? ''), ENT_QUOTES) ?>">
                                                        <div class="fw-semibold" style="font-size:13px"><?= htmlspecialchars((string)($b['nombanc'] ?? '')) ?></div>
                                                        <?php if (isset($b['numbanc']) && $b['numbanc'] !== '' && $b['numbanc'] !== null): ?>
                                                            <div class="text-muted" style="font-size:11px">N° <?= htmlspecialchars((string)$b['numbanc']) ?></div>
                                                        <?php endif; ?>
                                                    </button>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <input class="form-control" name="banco_emisor" placeholder="Nombre del banco" />
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if ($isTercero): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">¿Quién lo entregó?</label>
                        <input class="form-control" name="quien_entrego" placeholder="Nombre de quien entregó el cheque" />
                    </div>
                    <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Beneficiario <span class="text-danger">*</span></label>
                        <input class="form-control" name="titular" required placeholder="Nombre o razón social" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">CUIT del beneficiario</label>
                        <input class="form-control" name="cuit_titular" placeholder="20-12345678-9" />
                    </div>
                    <?php endif; ?>

                    <div class="cheque-section">Monto y fechas</div>
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text">$</span>
                                <input class="form-control" name="monto" type="number" min="0.01" step="0.01" required placeholder="0,00" />
                            </div>
                            <div class="form-text">En pesos, con decimales</div>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Fecha emisión</label>
                            <input class="form-control" name="fecha_emision" type="date" value="<?= date('Y-m-d') ?>" />
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Vencimiento</label>
                            <input class="form-control" name="fecha_vencimiento" type="date" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Concepto</label>
                        <textarea class="form-control" name="concepto" rows="2" placeholder="Motivo"></textarea>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> <?= $isTercero ? 'Agregar a cartera' : 'Emitir cheque' ?></button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var btn = document.getElementById('bancoDropBtn');
    if (!btn) return;
    var menu = document.getElementById('bancoDropMenu');
    var search = document.getElementById('bancoDropSearch');
    var label = document.getElementById('bancoDropLabel');
    var hidden = document.getElementById('bancoIdHidden');
    btn.addEventListener('click', function(e) {
        e.stopPropagation();
        menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        if (menu.style.display === 'block' && search) search.focus();
    });
    document.addEventListener('click', function(e) {
        var drop = document.getElementById('bancoDrop');
        if (drop && !drop.contains(e.target)) menu.style.display = 'none';
    });
    if (search) {
        search.addEventListener('input', function() {
            var q = this.value.toLowerCase();
            document.querySelectorAll('#bancoDropList .banco-opt').forEach(function(b) {
                b.style.display = b.textContent.toLowerCase().indexOf(q) >= 0 ? '' : 'none';
            });
        });
    }
    document.querySelectorAll('#bancoDropList .banco-opt').forEach(function(b) {
        b.addEventListener('click', function() {
            hidden.value = b.getAttribute('data-id');
            label.textContent = b.getAttribute('data-nombre');
            label.classList.remove('text-muted');
            menu.style.display = 'none';
        });
    });
})();
</script>
