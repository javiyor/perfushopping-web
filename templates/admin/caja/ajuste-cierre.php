<?php
use Perfushopping\Web\Support\Format;

$caja = $caja ?? [];
$campos = $campos ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Solicitar corrección de cierre #<?= (int)($caja['id'] ?? 0) ?></h4>
        <p class="text-muted small">La corrección queda pendiente hasta que un administrador la apruebe</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Valores del cierre (en pesos)</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/cierre/ajuste/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="caja_id" value="<?= (int)($caja['id'] ?? 0) ?>" />

                    <?php foreach ($campos as $campo => $label): ?>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold"><?= htmlspecialchars($label) ?></label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="<?= htmlspecialchars($campo) ?>" type="number" min="0" step="0.01" value="<?= (int)round(((int)($caja[$campo] ?? 0)) / 100) ?>" />
                        </div>
                        <div class="form-text">Actual: <?= Format::moneyFromCents((int)($caja[$campo] ?? 0)) ?></div>
                    </div>
                    <?php endforeach; ?>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Descripción del error</label>
                        <textarea class="form-control" name="motivo" rows="3" required minlength="10" placeholder="Ej: el cierre se cargó con 10000 de más, el conteo real fue..."></textarea>
                        <div class="form-text">Explicá qué salió mal para que el administrador pueda revisarlo.</div>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-send"></i> Enviar para aprobación</button>
                    <a class="btn btn-outline-secondary" href="/admin/caja">Cancelar</a>
                </form>
                <div class="alert alert-info small mt-3 mb-0">
                    Solo se crean solicitudes por los valores que cambies. Si se aprueba un cambio en el pasaje,
                    también se ajusta el movimiento correspondiente en Caja General.
                </div>
            </div>
        </div>
    </div>
</div>
