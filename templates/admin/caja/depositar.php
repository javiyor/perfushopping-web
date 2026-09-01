<div class="d-flex justify-content-between align-items-center mb-3">
    <h4 class="fw-bold mb-0">Depositar efectivo en banco</h4>
    <a class="btn btn-sm btn-outline-secondary" href="/admin/caja/general">Volver</a>
</div>
<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" action="/admin/caja/depositar">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small">Origen efectivo</label>
                    <select name="origen" class="form-select form-select-sm">
                        <option value="chica">Caja chica (punto de venta)</option>
                        <option value="general">Caja general</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Banco destino</label>
                    <select name="banco_cuenta_id" class="form-select form-select-sm" required>
                        <option value="">— Seleccionar —</option>
                        <?php foreach ($bancos as $b): ?>
                            <option value="<?= (int)$b['id'] ?>"><?= htmlspecialchars($b['banco'].' - '.$b['numero_cuenta']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label small">Monto</label>
                    <input type="number" step="0.01" name="monto" class="form-control form-control-sm" required />
                </div>
                <div class="col-12">
                    <label class="form-label small">Concepto</label>
                    <input type="text" name="concepto" class="form-control form-control-sm" value="Depósito en banco" required />
                </div>
                <div class="col-12 text-end">
                    <button type="submit" class="btn btn-accent btn-sm">Depositar</button>
                </div>
            </div>
        </form>
    </div>
</div>
