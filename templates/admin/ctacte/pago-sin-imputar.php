<?php
$cliente = $cliente ?? [];
$formasPago = $formasPago ?? [];
$idclien = (int)($cliente['idclien'] ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Registrar pago a cuenta</h4>
        <p class="text-muted small mb-0"><?= htmlspecialchars((string)($cliente['razon'] ?? '')) ?> · El pago quedará sin imputar a facturas.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/ctacte/erp/<?= $idclien ?>">Volver a cuenta corriente</a>
</div>

<div class="card shadow-sm" style="max-width:900px">
    <div class="card-body">
        <form method="post" action="/admin/ctacte/erp/pago-sin-imputar" id="pagoCuentaForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="idclien" value="<?= $idclien ?>" />
            <div class="alert alert-info small">Este pago se registrará como crédito disponible en la cuenta corriente. No cancelará ni modificará facturas pendientes.</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Importe recibido</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input class="form-control" type="number" name="monto" min="0.01" step="0.01" inputmode="decimal" required />
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Forma de cobro</label>
                    <select class="form-select" name="forma_pago" id="formaPagoCuenta" required>
                        <?php foreach ($formasPago as $forma): ?>
                            <option value="<?= htmlspecialchars((string)$forma['codigo']) ?>" data-tipo="<?= htmlspecialchars((string)($forma['tipo'] ?? '')) ?>">
                                <?= htmlspecialchars((string)$forma['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Fecha</label>
                    <input class="form-control" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Concepto</label>
                    <input class="form-control" name="concepto" maxlength="255" value="Pago a cuenta sin imputar facturas" />
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Notas</label>
                    <input class="form-control" name="notas" maxlength="1000" />
                </div>
                <div class="col-12" id="pagoCuentaCheque" style="display:none">
                    <div class="row g-2 p-3 bg-light rounded">
                        <div class="col-md-3"><label class="form-label small">Banco</label><input class="form-control form-control-sm" name="cheque_banco" /></div>
                        <div class="col-md-2"><label class="form-label small">Número de cheque</label><input class="form-control form-control-sm" name="cheque_numero" /></div>
                        <div class="col-md-3"><label class="form-label small">Titular</label><input class="form-control form-control-sm" name="cheque_titular" /></div>
                        <div class="col-md-2"><label class="form-label small">CUIT titular</label><input class="form-control form-control-sm" name="cheque_cuit" /></div>
                        <div class="col-md-2"><label class="form-label small">Vencimiento</label><input class="form-control form-control-sm" type="date" name="cheque_vencimiento" /></div>
                    </div>
                </div>
                <div class="col-12 d-flex justify-content-end gap-2">
                    <button class="btn btn-success" type="submit"><i class="bi bi-cash-stack"></i> Registrar pago sin imputar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function() {
    const forma = document.getElementById('formaPagoCuenta');
    const cheque = document.getElementById('pagoCuentaCheque');
    function actualizar() {
        const option = forma.options[forma.selectedIndex];
        cheque.style.display = option?.dataset.tipo === 'cheque' ? '' : 'none';
    }
    forma.addEventListener('change', actualizar);
    actualizar();
})();
</script>
