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
    <div class="d-flex gap-2">
        <a class="btn btn-outline-primary btn-sm" href="/admin/caja/cierre/imprimir" target="_blank"><i class="bi bi-printer"></i> Imprimir resumen</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
    </div>
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
                    <input type="hidden" name="detalle_efectivo" id="detalleEfectivo" value="" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Detalle por billete (conteo final)</label>
                        <div class="table-responsive">
                            <table class="table table-bordered table-sm align-middle mb-2" id="detalleTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:40%">Denominación</th>
                                        <th style="width:25%">Cantidad</th>
                                        <th style="width:30%">Subtotal</th>
                                        <th style="width:5%"></th>
                                    </tr>
                                </thead>
                                <tbody id="detalleBody">
                                </tbody>
                            </table>
                        </div>
                        <button class="btn btn-sm btn-outline-primary" type="button" onclick="addRow()">Agregar fila</button>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Monto final de cierre</label>
                        <input type="hidden" id="esperadoEfectivo" value="<?= (float)($esperadoEfectivo / 100) ?>" />
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_cierre_cents" id="montoCierre" type="number" value="<?= (int)round($esperadoEfectivo / 100) ?>" min="0" step="0.01" />
                        </div>
                        <div class="form-text">Efectivo físico contado al cierre, en pesos. Se completa solo con el detalle.</div>
                        <div id="difAlerta" class="mt-1" style="display:none"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Pasaje a Caja General (solo efectivo)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_retirado_cents" id="montoRetirado" type="number" value="0" min="0" max="<?= (int)round($esperadoEfectivo / 100) ?>" step="0.01" />
                        </div>
                        <div class="form-text">Solo efectivo. Máximo disponible: <?= Format::moneyFromCents($esperadoEfectivo) ?>.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Saldo para apertura del siguiente turno</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_proxima_cents" id="montoProxima" type="number" value="0" min="0" step="0.01" />
                        </div>
                        <div class="form-text">Se calcula solo: efectivo contado menos el pasaje. Podés ajustarlo a mano.</div>
                    </div>

                    <div class="mb-3 bg-light p-3 rounded small">
                        <div class="d-flex justify-content-between">
                            <span>Queda en caja:</span>
                            <strong id="quedaEnCaja">$0,00</strong>
                        </div>
                    </div>

                    <div class="alert alert-info small py-2">
                        <i class="bi bi-info-circle"></i>
                        El conteo de arriba queda guardado como arqueo del cierre. Al cerrar se finaliza el registro y no se podrán agregar más movimientos.
                    </div>

                    <button class="btn btn-warning w-100" type="submit" onclick="return confirmarCierre()"><i class="bi bi-stop-fill"></i> Cerrar caja</button>

<script>
const DENOMINACIONES_SUGERIDAS = [20000, 10000, 5000, 2000, 1000, 500, 200, 100];

function addRow(denom, qty) {
    const tbody = document.getElementById('detalleBody');
    const tr = document.createElement('tr');

    const tdDenom = document.createElement('td');
    const denomInput = document.createElement('input');
    denomInput.type = 'number';
    denomInput.className = 'form-control form-control-sm denom-input';
    denomInput.value = denom || '';
    denomInput.placeholder = 'Ej: 20000';
    denomInput.min = '1';
    denomInput.step = '1';
    denomInput.oninput = recalcDetalle;
    tdDenom.appendChild(denomInput);

    const tdQty = document.createElement('td');
    const qtyInput = document.createElement('input');
    qtyInput.type = 'number';
    qtyInput.className = 'form-control form-control-sm qty-input';
    qtyInput.value = qty || '';
    qtyInput.placeholder = '0';
    qtyInput.min = '0';
    qtyInput.step = '1';
    qtyInput.oninput = recalcDetalle;
    tdQty.appendChild(qtyInput);

    const tdSub = document.createElement('td');
    const subSpan = document.createElement('span');
    subSpan.className = 'subtotal-display';
    subSpan.textContent = '$0';
    tdSub.appendChild(subSpan);

    const tdDel = document.createElement('td');
    tdDel.className = 'text-center';
    const delBtn = document.createElement('button');
    delBtn.type = 'button';
    delBtn.className = 'btn btn-sm btn-outline-danger border-0';
    delBtn.innerHTML = '<i class="bi bi-x"></i>';
    delBtn.onclick = function() {
        tr.remove();
        recalcDetalle();
    };
    tdDel.appendChild(delBtn);

    tr.appendChild(tdDenom);
    tr.appendChild(tdQty);
    tr.appendChild(tdSub);
    tr.appendChild(tdDel);
    tbody.appendChild(tr);

    recalcDetalle();
}

function recalcDetalle() {
    let total = 0;
    const rows = document.querySelectorAll('#detalleBody tr');
    rows.forEach(tr => {
        const denom = parseInt(tr.querySelector('.denom-input').value) || 0;
        const qty = parseInt(tr.querySelector('.qty-input').value) || 0;
        const sub = denom * qty;
        total += sub;
        tr.querySelector('.subtotal-display').textContent = '$' + sub.toLocaleString('es-AR');
    });
    let hasData = false;
    rows.forEach(tr => {
        const denom = parseInt(tr.querySelector('.denom-input').value) || 0;
        const qty = parseInt(tr.querySelector('.qty-input').value) || 0;
        if (denom > 0 && qty > 0) hasData = true;
    });
    if (hasData) {
        document.getElementById('montoCierre').value = total;
    }
    document.getElementById('detalleEfectivo').value = JSON.stringify(getDetalle());
    calcQueda();
}

function getDetalle() {
    const detalle = [];
    document.querySelectorAll('#detalleBody tr').forEach(tr => {
        const denom = parseInt(tr.querySelector('.denom-input').value) || 0;
        const qty = parseInt(tr.querySelector('.qty-input').value) || 0;
        if (denom > 0 && qty > 0) {
            detalle.push({ denominacion: denom, cantidad: qty, subtotal: denom * qty });
        }
    });
    return detalle;
}

document.getElementById('montoCierre').addEventListener('input', calcQueda);
document.getElementById('montoRetirado').addEventListener('input', calcQueda);
document.addEventListener('DOMContentLoaded', function() {
    DENOMINACIONES_SUGERIDAS.forEach(d => addRow(d, 0));
});
function calcQueda() {
    const cierre = Math.round((parseFloat(document.getElementById('montoCierre').value) || 0) * 100);
    const retiro = Math.round((parseFloat(document.getElementById('montoRetirado').value) || 0) * 100);
    const queda = cierre - retiro;
    document.getElementById('quedaEnCaja').textContent = '$' + (queda / 100).toLocaleString('es-AR', {minimumFractionDigits:2});
    document.getElementById('quedaEnCaja').className = queda < 0 ? 'text-danger' : queda > 0 ? 'text-success' : '';
    const proximaEl = document.getElementById('montoProxima');
    if (proximaEl && document.activeElement !== proximaEl) {
        proximaEl.value = (Math.max(0, queda) / 100).toFixed(2);
    }
    mostrarDiferencia(cierre);
}
calcQueda();

function diferenciaCierre() {
    const cierre = Math.round((parseFloat(document.getElementById('montoCierre').value) || 0) * 100);
    const esperadoEl = document.getElementById('esperadoEfectivo');
    const esperado = Math.round((parseFloat(esperadoEl ? esperadoEl.value : 0) || 0) * 100);
    return cierre - esperado;
}
function mostrarDiferencia(cierre) {
    const box = document.getElementById('difAlerta');
    if (!box) return;
    const esperadoEl = document.getElementById('esperadoEfectivo');
    const esperado = Math.round((parseFloat(esperadoEl ? esperadoEl.value : 0) || 0) * 100);
    const dif = (typeof cierre === 'number' ? cierre : Math.round((parseFloat(document.getElementById('montoCierre').value) || 0) * 100)) - esperado;
    if (dif === 0) {
        box.style.display = 'none';
        box.innerHTML = '';
        return;
    }
    const monto = '$' + (Math.abs(dif) / 100).toLocaleString('es-AR', {minimumFractionDigits: 2});
    const sobra = dif > 0;
    box.style.display = '';
    box.innerHTML = '<div class="alert ' + (sobra ? 'alert-success' : 'alert-danger') + ' small py-2 mb-0">'
        + '<i class="bi bi-exclamation-triangle"></i> Diferencia: <strong>' + (sobra ? '+' : '−') + monto
        + ' (' + (sobra ? 'sobrante' : 'faltante') + ')</strong>. Se puede cerrar igual y la caja queda marcada <strong>con dif</strong>.</div>';
}
function fmtDiferencia(dif) {
    return (dif < 0 ? '−' : '+') + '$' + (Math.abs(dif) / 100).toLocaleString('es-AR', {minimumFractionDigits: 2 });
}
function confirmarCierre() {
    const dif = diferenciaCierre();
    if (dif === 0) {
        return confirm('¿Confirmar el cierre de caja? Verificá los montos antes de continuar.');
    }
    return confirm('ATENCIÓN: hay diferencia de ' + fmtDiferencia(dif) + ' (' + (dif < 0 ? 'faltante' : 'sobrante') + ') vs el esperado. ¿Cerrar igual y marcar la caja con dif?');
}

// Evita que el auto-update recargue la página mientras se cuentan billetes.
window.__conteoEnProceso = function() {
    var rows = document.querySelectorAll('#detalleBody tr');
    for (var i = 0; i < rows.length; i++) {
        var d = rows[i].querySelector('.denom-input');
        var q = rows[i].querySelector('.qty-input');
        if (d && q && (parseInt(d.value) || 0) > 0 && (parseInt(q.value) || 0) > 0) return true;
    }
    return false;
};
</script>
                </form>
            </div>
        </div>
    </div>
</div>
