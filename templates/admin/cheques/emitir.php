<?php
$bancos = $bancos ?? [];
$csrfToken = $csrf ?? '';
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Emitir cheque propio</h4>
        <p class="text-muted small">Nuevo cheque de la empresa</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/cheques">Volver</a>
</div>

<div class="row">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="post" action="/admin/cheques/emitir/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cuenta bancaria <span class="text-danger">*</span></label>
                        <select class="form-select" name="banco_cuenta_id" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($bancos as $b): ?>
                                <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars((string)($b['banco'] ?? '') . ' — ' . ($b['numero_cuenta'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">N° de cheque</label>
                            <input class="form-control" name="numero_cheque" placeholder="Ej: 00012345" />
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Banco emisor</label>
                            <input class="form-control" name="banco_emisor" placeholder="Nombre del banco" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Beneficiario <span class="text-danger">*</span></label>
                        <input class="form-control" name="titular" required placeholder="Nombre o razón social" />
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">CUIT del beneficiario</label>
                        <input class="form-control" name="cuit_titular" placeholder="20-12345678-9" />
                    </div>

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
                        <textarea class="form-control" name="concepto" rows="2" placeholder="Motivo de la emisión"></textarea>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Emitir cheque</button>
                </form>
            </div>
        </div>
    </div>
</div>
