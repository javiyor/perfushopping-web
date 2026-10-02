<?php
$bancos = $bancos ?? [];
$bancosLista = $bancosLista ?? [];
$csrfToken = $csrf ?? '';
$tipo = $tipo ?? 'propio';
$isTercero = $tipo === 'tercero';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= $isTercero ? 'Cargar cheque de tercero' : 'Emitir cheque propio' ?></h4>
        <p class="text-muted small"><?= $isTercero ? 'Cheque recibido de un cliente/proveedor, ingresa a cartera' : 'Nuevo cheque de la empresa' ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/cheques">Volver</a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
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

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">N° de cheque</label>
                            <input class="form-control" name="numero_cheque" placeholder="Ej: 00012345" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Banco emisor</label>
                            <?php if ($isTercero): ?>
                                <select class="form-select" name="banco_id">
                                    <option value="">— Seleccionar —</option>
                                    <?php foreach ($bancosLista as $b): ?>
                                        <option value="<?= (int)$b['idban'] ?>"><?= htmlspecialchars((string)($b['nombanc'] ?? '')) ?><?= isset($b['numbanc']) && $b['numbanc'] !== '' && $b['numbanc'] !== null ? ' — ' . htmlspecialchars((string)$b['numbanc']) : '' ?></option>
                                    <?php endforeach; ?>
                                </select>
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

                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <label class="form-label small fw-semibold">Monto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input class="form-control" name="monto_cents" type="number" min="1" required placeholder="En centavos" />
                            </div>
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
