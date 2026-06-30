<?php ?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Abrir caja</h4>
        <p class="text-muted small">Registrar la apertura de caja para el turno actual</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Apertura</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/abrir/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monto inicial en efectivo</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_inicial_cents" type="number" value="0" min="0" step="1" />
                        </div>
                        <div class="form-text">Monto en centavos (ej: 50000 = $500,00)</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Observaciones (opcional)</label>
                        <textarea class="form-control form-control-sm" name="observaciones" rows="2" placeholder="Ej: Cambio inicial, billetes grandes..."></textarea>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Abrir caja</button>
                    <a class="btn btn-outline-secondary" href="/admin/caja">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Info del turno</div>
            <div class="card-body small">
                <p>Al abrir la caja se inicia el registro de movimientos para el turno actual.</p>
                <ul class="mb-0">
                    <li>El monto inicial es lo que hay físicamente en la caja al empezar</li>
                    <li>Se pueden registrar movimientos (ingresos/egresos) durante el turno</li>
                    <li>Hacé arqueos periódicos para controlar el efectivo</li>
                    <li>Al cerrar el turno, se debe cerrar la caja con el conteo final</li>
                </ul>
            </div>
        </div>
    </div>
</div>
