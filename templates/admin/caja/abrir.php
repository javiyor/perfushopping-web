<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Abrir caja</h4>
        <p class="text-muted small">Registrar la apertura de caja para el turno actual</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Detalle de efectivo</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/abrir/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="monto_inicial_cents" id="montoInicialCents" value="0" />
                    <input type="hidden" name="detalle_efectivo" id="detalleEfectivo" value="" />

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

                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <button class="btn btn-sm btn-outline-primary" type="button" onclick="addRow()"><i class="bi bi-plus-lg"></i> Agregar fila</button>
                        <div class="fw-bold fs-5">Total: $<span id="totalDisplay">0</span></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Observaciones (opcional)</label>
                        <textarea class="form-control form-control-sm" name="observaciones" rows="2" placeholder="Ej: Cambio inicial..."></textarea>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Abrir caja</button>
                    <a class="btn btn-outline-secondary" href="/admin/caja">Cancelar</a>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Info del turno</div>
            <div class="card-body small">
                <p>Al abrir la caja se inicia el registro de movimientos para el turno actual.</p>
                <ul class="mb-0">
                    <li>Ingresá el detalle del efectivo disponible (billetes y monedas)</li>
                    <li>El total se calcula automáticamente</li>
                    <li>Se pueden registrar movimientos (ingresos/egresos) durante el turno</li>
                    <li>Hacé arqueos periódicos para controlar el efectivo</li>
                    <li>Al cerrar el turno, se debe cerrar la caja con el conteo final</li>
                </ul>
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
    document.getElementById('totalDisplay').textContent = total.toLocaleString('es-AR');
    document.getElementById('montoInicialCents').value = total;
    document.getElementById('detalleEfectivo').value = JSON.stringify(getDetalle());
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

document.addEventListener('DOMContentLoaded', function() {
    DENOMINACIONES_SUGERIDAS.forEach(d => addRow(d, 0));
});
</script>
