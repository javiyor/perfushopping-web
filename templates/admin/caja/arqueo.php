<?php
use Perfushopping\Web\Support\Format;
$apertura = $apertura ?? null;
$ventasEfectivo = (int)($ventasEfectivo ?? 0);
$totalesMov = $totalesMov ?? ['total_ingresos' => 0, 'total_egresos' => 0];
$arqueos = $arqueos ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Arqueo de caja</h4>
        <p class="text-muted small">Conteo físico del efectivo en caja</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Registrar arqueo</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/arqueo/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="detalle_efectivo" id="detalleEfectivo" value="" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Detalle por billete (opcional)</label>
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
                        <label class="form-label small fw-semibold">Total contado (efectivo físico)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="total_cents" type="number" required min="0" step="0.01" id="arqueoTotal" />
                        </div>
                        <div class="form-text">En pesos (ej: 1500 = $1.500,00). Se completa solo con el detalle.</div>
                    </div>

                    <div class="mb-3">
                        <div class="bg-light p-3 rounded small">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Saldo esperado:</span>
                                <strong id="saldoEsperado">
                                    <?php
                                    $montoInicial = (int)($apertura['monto_inicial_cents'] ?? 0);
                                    $esperado = $montoInicial + $ventasEfectivo + (int)$totalesMov['total_ingresos'] - (int)$totalesMov['total_egresos'];
                                    echo Format::moneyFromCents($esperado);
                                    ?>
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>Apertura:</span>
                                <span><?= Format::moneyFromCents($montoInicial) ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>+ Ventas efectivo:</span>
                                <span><?= Format::moneyFromCents($ventasEfectivo) ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>+ Ingresos extra:</span>
                                <span><?= Format::moneyFromCents((int)$totalesMov['total_ingresos']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>- Egresos extra:</span>
                                <span><?= Format::moneyFromCents((int)$totalesMov['total_egresos']) ?></span>
                            </div>
                            <hr class="my-1" />
                            <div class="d-flex justify-content-between fw-bold" id="diferenciaRow">
                                <span>Diferencia:</span>
                                <span id="diferenciaLabel">$0,00</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Observaciones</label>
                        <textarea class="form-control form-control-sm" name="observaciones" rows="2" placeholder="Ej: Billetes contados, faltante, sobrante..."></textarea>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Registrar arqueo</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Arqueos anteriores</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th class="text-end">Total contado</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Diferencia</th>
                            <th>Obs.</th>
                            <th>Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$arqueos): ?>
                            <tr><td colspan="6" class="text-muted text-center">Sin arqueos registrados</td></tr>
                        <?php else: ?>
                            <?php foreach ($arqueos as $a): ?>
                                <?php $dif = (int)($a['total_cents'] ?? 0) - $esperado; ?>
                                <tr>
                                    <td class="small"><?= date('H:i', strtotime($a['created_at'] ?? '')) ?></td>
                                    <td class="text-end"><?= Format::moneyFromCents((int)($a['total_cents'] ?? 0)) ?></td>
                                    <td class="text-end"><?= Format::moneyFromCents($esperado) ?></td>
                                    <td class="text-end <?= $dif < 0 ? 'text-danger' : ($dif > 0 ? 'text-success' : '') ?>"><?= Format::moneyFromCents($dif) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars(mb_substr((string)($a['observaciones'] ?? ''), 0, 30)) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($a['created_by_nombre'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

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
    denomInput.oninput = recalcTotal;
    tdDenom.appendChild(denomInput);

    const tdQty = document.createElement('td');
    const qtyInput = document.createElement('input');
    qtyInput.type = 'number';
    qtyInput.className = 'form-control form-control-sm qty-input';
    qtyInput.value = qty || '';
    qtyInput.placeholder = '0';
    qtyInput.min = '0';
    qtyInput.step = '1';
    qtyInput.oninput = recalcTotal;
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
        recalcTotal();
    };
    tdDel.appendChild(delBtn);

    tr.appendChild(tdDenom);
    tr.appendChild(tdQty);
    tr.appendChild(tdSub);
    tr.appendChild(tdDel);
    tbody.appendChild(tr);

    recalcTotal();
}

function recalcTotal() {
    let total = 0;
    const rows = document.querySelectorAll('#detalleBody tr');
    rows.forEach(tr => {
        const denom = parseInt(tr.querySelector('.denom-input').value) || 0;
        const qty = parseInt(tr.querySelector('.qty-input').value) || 0;
        const sub = denom * qty;
        total += sub;
        tr.querySelector('.subtotal-display').textContent = '$' + sub.toLocaleString('es-AR');
    });
    if (rows.length > 0) {
        document.getElementById('arqueoTotal').value = total;
    }
    document.getElementById('detalleEfectivo').value = JSON.stringify(getDetalle());
    updateDiferencia();
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

function updateDiferencia() {
    const total = Math.round((parseFloat(document.getElementById('arqueoTotal').value) || 0) * 100);
    const esperado = <?= $esperado ?? 0 ?>;
    const dif = total - esperado;
    const el = document.getElementById('diferenciaLabel');
    el.textContent = (dif >= 0 ? '+' : '') + '$' + Math.abs(dif / 100).toLocaleString('es-AR', {minimumFractionDigits:2});
    el.className = dif < 0 ? 'text-danger' : dif > 0 ? 'text-success' : '';
}

document.getElementById('arqueoTotal').addEventListener('input', updateDiferencia);
document.addEventListener('DOMContentLoaded', function() {
    DENOMINACIONES_SUGERIDAS.forEach(d => addRow(d, 0));
    updateDiferencia();
});

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
