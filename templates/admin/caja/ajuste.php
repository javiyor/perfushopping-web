<?php
use Perfushopping\Web\Support\Format;

$apertura = $apertura ?? [];
$actualCents = (int)($apertura['monto_inicial_cents'] ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Solicitar corrección de apertura</h4>
        <p class="text-muted small">La corrección queda pendiente hasta que un administrador la apruebe</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Corrección del monto inicial</div>
            <div class="card-body">
                <div class="alert alert-info small">
                    Monto actual de apertura: <strong><?= Format::moneyFromCents($actualCents) ?></strong>
                </div>
                <form method="post" action="/admin/caja/apertura/ajuste/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monto correcto (en pesos)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_nuevo_cents" type="number" required min="0" step="0.01" value="<?= (int)round($actualCents / 100) ?>" />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción del error</label>
                        <textarea class="form-control" name="motivo" rows="3" required minlength="10" placeholder="Ej: cargué 43550 en lugar de 435500, me faltó un cero al contar los billetes..."></textarea>
                        <div class="form-text">Explicá qué salió mal para que el administrador pueda revisarlo.</div>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-send"></i> Enviar para aprobación</button>
                    <a class="btn btn-outline-secondary" href="/admin/caja">Cancelar</a>
                </form>
            </div>
        </div>
    </div>
</div>
