<?php
use Perfushopping\Web\Support\Format;
$apertura = $apertura ?? null;
$ventasEfectivo = (int)($ventasEfectivo ?? 0);
$ventasTransferencia = (int)($ventasTransferencia ?? 0);
$totalRecibos = (int)($totalRecibos ?? 0);
$totalesMov = $totalesMov ?? ['total_ingresos' => 0, 'total_egresos' => 0];
$esperadoEfectivo = (int)($esperadoEfectivo ?? 0);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Cierre de caja</h4>
        <p class="text-muted small">Finalizar la caja del turno actual</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-6">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Resumen del turno</div>
            <div class="card-body">
                <dl class="row small mb-0">
                    <dt class="col-sm-6">Apertura</dt>
                    <dd class="col-sm-6 text-end"><?= Format::moneyFromCents((int)($apertura['monto_inicial_cents'] ?? 0)) ?></dd>
                    <dt class="col-sm-6">Ventas en efectivo</dt>
                    <dd class="col-sm-6 text-end text-success">+ <?= Format::moneyFromCents($ventasEfectivo) ?></dd>
                    <dt class="col-sm-6">Ventas transf./MP</dt>
                    <dd class="col-sm-6 text-end text-info"><?= Format::moneyFromCents($ventasTransferencia) ?></dd>
                    <dt class="col-sm-6">Cobrado (recibos)</dt>
                    <dd class="col-sm-6 text-end text-primary"><?= Format::moneyFromCents($totalRecibos) ?></dd>
                    <dt class="col-sm-6">Mov. ingresos extra</dt>
                    <dd class="col-sm-6 text-end text-success">+ <?= Format::moneyFromCents((int)$totalesMov['total_ingresos']) ?></dd>
                    <dt class="col-sm-6">Mov. egresos extra</dt>
                    <dd class="col-sm-6 text-end text-danger">- <?= Format::moneyFromCents((int)$totalesMov['total_egresos']) ?></dd>
                    <hr class="my-1" />
                    <dt class="col-sm-6 fw-bold">Efectivo esperado</dt>
                    <dd class="col-sm-6 text-end fw-bold fs-5"><?= Format::moneyFromCents($esperadoEfectivo) ?></dd>
                </dl>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Cierre</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/cierre/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monto final de cierre</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_cierre_cents" id="montoCierre" type="number" value="<?= (int)round($esperadoEfectivo / 100) ?>" min="0" step="0.01" />
                        </div>
                        <div class="form-text">Efectivo físico contado al cierre, en pesos.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pasaje a Caja General (solo efectivo)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_retirado_cents" id="montoRetirado" type="number" value="0" min="0" step="0.01" />
                        </div>
                        <div class="form-text">Solo efectivo. Máximo disponible: <?= Format::moneyFromCents($esperadoEfectivo) ?>.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Saldo para apertura del siguiente turno</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_proxima_cents" id="montoProxima" type="number" value="0" min="0" step="0.01" />
                        </div>
                        <div class="form-text">Efectivo que queda como fondo para la próxima apertura, en pesos.</div>
                    </div>

                    <div class="mb-3 bg-light p-3 rounded small">
                        <div class="d-flex justify-content-between">
                            <span>Queda en caja:</span>
                            <strong id="quedaEnCaja">$0,00</strong>
                        </div>
                    </div>

                    <div class="alert alert-warning small py-2">
                        <i class="bi bi-exclamation-triangle"></i>
                        Al cerrar la caja se finaliza el registro. No se podrán agregar más movimientos.
                        <strong>¿Hiciste un arqueo antes de cerrar?</strong>
                    </div>

                    <button class="btn btn-warning w-100" type="submit"><i class="bi bi-stop-fill"></i> Cerrar caja</button>

<script>
document.getElementById('montoCierre').addEventListener('input', calcQueda);
document.getElementById('montoRetirado').addEventListener('input', calcQueda);
function calcQueda() {
    const cierre = Math.round((parseFloat(document.getElementById('montoCierre').value) || 0) * 100);
    const retiro = Math.round((parseFloat(document.getElementById('montoRetirado').value) || 0) * 100);
    const queda = cierre - retiro;
    document.getElementById('quedaEnCaja').textContent = '$' + (queda / 100).toLocaleString('es-AR', {minimumFractionDigits:2});
    document.getElementById('quedaEnCaja').className = queda < 0 ? 'text-danger' : queda > 0 ? 'text-success' : '';
}
calcQueda();
</script>
                </form>
            </div>
        </div>
    </div>
</div>
