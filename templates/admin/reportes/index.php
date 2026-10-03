<?php
use Perfushopping\Web\Support\Format;

$desde = (string)($desde ?? date('Y-m-01'));
$hasta = (string)($hasta ?? date('Y-m-d'));
$puntoVenta = (int)($puntoVenta ?? 0);
$puntosVenta = $puntosVenta ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Reportes</h4>
        <p class="text-muted small">Ventas, cobranza y rendimiento</p>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form class="row g-2" id="reporteForm">
            <div class="col-lg-3">
                <label class="form-label small">Desde</label>
                <input class="form-control form-control-sm" type="date" name="desde" value="<?= htmlspecialchars($desde) ?>" />
            </div>
            <div class="col-lg-3">
                <label class="form-label small">Hasta</label>
                <input class="form-control form-control-sm" type="date" name="hasta" value="<?= htmlspecialchars($hasta) ?>" />
            </div>
            <div class="col-lg-3">
                <label class="form-label small">Punto de venta</label>
                <select class="form-control form-control-sm" name="punto_venta">
                    <option value="0" <?= $puntoVenta === 0 ? 'selected' : '' ?>>Todos los puntos de venta</option>
                    <?php foreach ($puntosVenta as $pv): ?>
                        <option value="<?= (int)($pv['punto_venta'] ?? 0) ?>" <?= $puntoVenta === (int)($pv['punto_venta'] ?? 0) && $puntoVenta !== 0 ? 'selected' : '' ?>>PV <?= (int)($pv['punto_venta'] ?? 0) ?> — <?= htmlspecialchars((string)($pv['nombre'] ?? '')) ?> (<?= (int)($pv['comprobantes'] ?? 0) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3 d-flex align-items-end">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Consultar</button>
            </div>
            <div class="col-lg-3 d-flex align-items-end">
                <button class="btn btn-outline-secondary btn-sm w-100" type="button" id="btnExportar"><i class="bi bi-download"></i> Exportar CSV</button>
            </div>
        </form>
    </div>
</div>

<div id="reporteLoader" class="text-center py-5" style="display:none">
    <div class="spinner-border text-secondary" role="status"></div>
    <div class="text-muted small mt-2">Cargando reportes...</div>
</div>

<div id="reporteContent">
    <!-- KPIs -->
    <div class="row g-3 mb-4" id="kpiRow">
        <div class="col-6 col-md-3">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0" id="kpiFacturas">-</div>
                <div class="small text-muted">Facturas emitidas</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0" id="kpiTotal">-</div>
                <div class="small text-muted">Total vendido</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0" id="kpiIVA">-</div>
                <div class="small text-muted">IVA total</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0" id="kpiRecibos">-</div>
                <div class="small text-muted">Cobrado (recibos)</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4" id="kpiRow2">
        <div class="col-6 col-md-4">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0" id="kpiTicket">-</div>
                <div class="small text-muted">Ticket promedio</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0 text-success" id="kpiGanancia">-</div>
                <div class="small text-muted">Ganancia neta (sin IVA)</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0" id="kpiMargen">-</div>
                <div class="small text-muted">Margen neto %</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Comparativas del mes (del día 1 al mismo día)</div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>Período</th><th class="text-end">Facturas</th><th class="text-end">Total</th><th class="text-end">Var. vs actual</th></tr></thead>
                        <tbody id="compBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <!-- Chart -->
        <div class="col-lg-8">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Ventas diarias</div>
                <div class="card-body">
                    <canvas id="ventasChart" height="220"></canvas>
                </div>
            </div>
        </div>
        <!-- Por forma de pago -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Formas de pago</div>
                <div class="card-body">
                    <canvas id="formasChart" height="180"></canvas>
                </div>
                <div class="card-body p-0">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>Forma</th><th class="text-end">Monto</th></tr></thead>
                        <tbody id="formaPagoBody"></tbody>
                    </table>
                    <div id="equipoTarjetaBox" class="border-top" style="display:none">
                        <div class="px-3 pt-2 pb-1 small fw-semibold text-muted">Tarjetas por equipo POS</div>
                        <table class="table table-admin mb-0">
                            <thead><tr><th>Equipo</th><th class="text-end">Pagos</th><th class="text-end">Monto</th></tr></thead>
                            <tbody id="equipoTarjetaBody"></tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <!-- Mensual -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Ventas mensuales (24 meses)</div>
                <div class="card-body">
                    <canvas id="mensualChart" height="220"></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <!-- Por sucursal -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Ventas por sucursal</div>
                <div class="card-body">
                    <canvas id="sucursalChart" height="140"></canvas>
                </div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead>
                            <tr>
                                <th>Sucursal</th>
                                <th class="text-end">Comprobantes</th>
                                <th class="text-end">Ventas totales</th>
                                <th class="text-end">Neto sin IVA</th>
                                <th class="text-end">Descuentos</th>
                                <th class="text-end">Costo</th>
                                <th class="text-end">Ganancia neta</th>
                                <th class="text-end">Margen neto</th>
                                <th class="text-end">Ticket prom.</th>
                                <th class="text-end">Gastos</th>
                            </tr>
                        </thead>
                        <tbody id="sucursalBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <!-- Top ganancia -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Top productos por ganancia <span class="text-muted fw-normal" style="font-size:11px">(bruta, sin prorratear descuentos)</span></div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>#</th><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Neto</th><th class="text-end">Costo</th><th class="text-end">Ganancia</th></tr></thead>
                        <tbody id="topGananciaBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Margen por departamento -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Margen por departamento</div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>Departamento</th><th class="text-end">Cant.</th><th class="text-end">Neto</th><th class="text-end">Ganancia</th><th class="text-end">Margen</th></tr></thead>
                        <tbody id="margenDeptoBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <!-- Top productos -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Top productos vendidos</div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>#</th><th>Producto</th><th>Var.</th><th class="text-end">Cant.</th><th class="text-end">Total</th><th class="text-end">Facturas</th></tr></thead>
                        <tbody id="topProductosBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
        <!-- Por departamento -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Ventas por departamento</div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>Departamento</th><th>Actividad</th><th class="text-end">Cant.</th><th class="text-end">Total</th><th style="width:35%"></th></tr></thead>
                        <tbody id="deptoBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mt-2">
        <!-- Por tipo comprobante -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Facturas por tipo</div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>Tipo</th><th class="text-end">Cant.</th><th class="text-end">Total</th></tr></thead>
                        <tbody id="tipoBody"></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
let ventasChartInstance = null;
let mensualChartInstance = null;
let sucursalChartInstance = null;
let formasChartInstance = null;

function fmtPct(actual, previo) {
    previo = parseInt(previo || 0);
    if (!previo) return '—';
    const v = (parseInt(actual || 0) - previo) / previo * 100;
    const cls = v > 0 ? 'text-success' : (v < 0 ? 'text-danger' : 'text-muted');
    return '<span class="' + cls + '">' + (v > 0 ? '+' : '') + v.toFixed(1) + '%</span>';
}

function chartColors(n) {
    const base = [
        'rgba(216,178,90,0.7)', 'rgba(13,110,253,0.6)', 'rgba(25,135,84,0.6)',
        'rgba(220,53,69,0.6)', 'rgba(111,66,193,0.6)', 'rgba(253,126,20,0.6)',
        'rgba(32,201,151,0.6)', 'rgba(13,202,240,0.6)',
    ];
    const out = [];
    for (let i = 0; i < n; i++) out.push(base[i % base.length]);
    return out;
}

function fmtCents(c) {
    let sign = c < 0 ? '-' : '';
    c = Math.abs(c);
    let val = (c / 100).toLocaleString('es-AR', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    return sign + '$' + val;
}

function cargarReportes() {
    const form = document.getElementById('reporteForm');
    const fd = new FormData(form);
    const params = new URLSearchParams(fd);

    document.getElementById('reporteLoader').style.display = 'block';
    document.getElementById('reporteContent').style.opacity = '0.4';

    fetch('/admin/reportes/data?' + params.toString())
        .then(r => r.json())
        .then(d => {
            if (d && d.ok === false) {
                alert('Error en reportes (' + (d.step || '?') + '): ' + (d.error || 'desconocido'));
                throw new Error(d.error || 'reportes');
            }
            const res = d.resumen || {};
            document.getElementById('kpiFacturas').textContent = (res.cantidad ?? 0);
            document.getElementById('kpiTotal').textContent = fmtCents(parseInt(res.total_cents ?? 0));
            document.getElementById('kpiIVA').textContent = fmtCents(parseInt(res.iva_cents ?? 0));

            const rec = d.recibos || {};
            document.getElementById('kpiRecibos').textContent = fmtCents(parseInt(rec.total_cents ?? 0));

            // Ticket, ganancia y margen
            document.getElementById('kpiTicket').textContent = fmtCents(parseInt(d.ticket ?? 0));
            const gan = d.ganancia || {};
            const ganancia = parseInt(gan.ganancia_cents ?? 0);
            const neto = parseInt(gan.neto_cents ?? 0);
            document.getElementById('kpiGanancia').textContent = fmtCents(ganancia);
            document.getElementById('kpiMargen').textContent = neto > 0 ? (ganancia / neto * 100).toFixed(1) + '%' : '—';

            // Comparativas
            const compBody = document.getElementById('compBody');
            compBody.innerHTML = '';
            const comp = d.comparativas || {};
            const actual = comp.mesActual || {};
            const filasComp = [
                ['Mes actual', actual],
                ['Mes anterior', comp.mesAnterior || {}],
                ['Hace 1 año', comp.hace1Anio || {}],
                ['Hace 2 años', comp.hace2Anios || {}],
            ];
            filasComp.forEach((f, i) => {
                const nombre = f[0];
                const r = f[1];
                const rango = (r.desde && r.hasta) ? ' <span class="text-muted" style="font-size:11px">' + escHtml(r.desde) + ' al ' + escHtml(r.hasta) + '</span>' : '';
                const varPct = i === 0 ? '—' : fmtPct(actual.total_cents, r.total_cents);
                compBody.innerHTML += '<tr><td>' + escHtml(nombre) + rango + '</td><td class="text-end">' + parseInt(r.cantidad || 0) + '</td><td class="text-end">' + fmtCents(parseInt(r.total_cents || 0)) + '</td><td class="text-end">' + varPct + '</td></tr>';
            });

            // Diarias chart
            const diarias = d.diarias || [];
            const labels = diarias.map(x => x.fecha);
            const dataVentas = diarias.map(x => parseInt(x.total_cents || 0) / 100);
            const dataCant = diarias.map(x => parseInt(x.cantidad || 0));

            if (ventasChartInstance) ventasChartInstance.destroy();
            const ctx = document.getElementById('ventasChart').getContext('2d');
            ventasChartInstance = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [
                        {
                            label: 'Ventas ($)',
                            data: dataVentas,
                            backgroundColor: 'rgba(216, 178, 90, 0.7)',
                            borderColor: 'rgba(216, 178, 90, 1)',
                            borderWidth: 1,
                            yAxisID: 'y',
                        },
                        {
                            label: 'Facturas',
                            data: dataCant,
                            backgroundColor: 'rgba(13, 110, 253, 0.3)',
                            borderColor: 'rgba(13, 110, 253, 0.6)',
                            borderWidth: 1,
                            yAxisID: 'y1',
                        }
                    ]
                },
                options: {
                    responsive: true,
                    plugins: { legend: { position: 'top' } },
                    scales: {
                        y: { beginAtZero: true, title: { display: true, text: '$' } },
                        y1: { beginAtZero: true, position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'Facturas' } }
                    }
                }
            });

            // Forma de pago
            const fpLabels = d.formasPagoLabels || {};
            const fpBody = document.getElementById('formaPagoBody');
            fpBody.innerHTML = '';
            const formas = d.porFormaPago || [];
            if (!formas.length) {
                fpBody.innerHTML = '<tr><td colspan="2" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                formas.forEach(f => {
                    const nombre = fpLabels[f.forma_pago] || f.forma_pago;
                    fpBody.innerHTML += '<tr><td>' + escHtml(nombre) + '</td><td class="text-end">' + fmtCents(parseInt(f.total_cents || 0)) + '</td></tr>';
                });
            }
            if (formasChartInstance) formasChartInstance.destroy();
            if (formas.length) {
                formasChartInstance = new Chart(document.getElementById('formasChart').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: formas.map(f => fpLabels[f.forma_pago] || f.forma_pago),
                        datasets: [{ data: formas.map(f => parseInt(f.total_cents || 0) / 100), backgroundColor: chartColors(formas.length) }],
                    },
                    options: { responsive: true, plugins: { legend: { position: 'bottom' } } },
                });
            }

            // Tarjetas por equipo POS
            const eqBody = document.getElementById('equipoTarjetaBody');
            const eqBox = document.getElementById('equipoTarjetaBox');
            eqBody.innerHTML = '';
            const equipos = d.porEquipoTarjeta || [];
            if (eqBox) eqBox.style.display = equipos.length ? '' : 'none';
            equipos.forEach(e => {
                eqBody.innerHTML += '<tr><td>' + escHtml(e.equipo) + '</td><td class="text-end">' + parseInt(e.pagos || 0) + '</td><td class="text-end">' + fmtCents(parseInt(e.total_cents || 0)) + '</td></tr>';
            });

            // Mensuales
            if (mensualChartInstance) mensualChartInstance.destroy();
            const mens = d.mensuales || [];
            if (mens.length) {
                mensualChartInstance = new Chart(document.getElementById('mensualChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: mens.map(x => x.mes),
                        datasets: [{ label: 'Ventas ($)', data: mens.map(x => parseInt(x.total_cents || 0) / 100), backgroundColor: 'rgba(216, 178, 90, 0.7)', borderColor: 'rgba(216, 178, 90, 1)', borderWidth: 1 }],
                    },
                    options: { responsive: true, plugins: { legend: { position: 'top' } }, scales: { y: { beginAtZero: true } } },
                });
            }

            // Sucursales
            const sucBody = document.getElementById('sucursalBody');
            sucBody.innerHTML = '';
            const sucs = d.porSucursal || [];
            if (!sucs.length) {
                sucBody.innerHTML = '<tr><td colspan="10" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                sucs.forEach(s => {
                    const total = parseInt(s.total_cents || 0);
                    const cant = parseInt(s.cantidad || 0);
                    const neto = parseInt(s.neto_cents || 0);
                    const desc = parseInt(s.descuento_cents || 0) + parseInt(s.puntos_cents || 0);
                    const costo = parseInt(s.costo_cents || 0);
                    const gana = parseInt(s.ganancia_cents || 0);
                    const gastos = parseInt(s.gastos_cents || 0);
                    const margen = (s.margen_pct !== null && s.margen_pct !== undefined) ? parseFloat(s.margen_pct).toFixed(1) + '%' : '—';
                    const ticket = cant > 0 ? fmtCents(Math.round(total / cant)) : '—';
                    sucBody.innerHTML += '<tr>'
                        + '<td>' + escHtml(s.sucursal) + '</td>'
                        + '<td class="text-end">' + cant + '</td>'
                        + '<td class="text-end">' + fmtCents(total) + '</td>'
                        + '<td class="text-end">' + fmtCents(neto) + '</td>'
                        + '<td class="text-end">' + fmtCents(desc) + '</td>'
                        + '<td class="text-end">' + fmtCents(costo) + '</td>'
                        + '<td class="text-end text-success">' + fmtCents(gana) + '</td>'
                        + '<td class="text-end">' + margen + '</td>'
                        + '<td class="text-end">' + ticket + '</td>'
                        + '<td class="text-end text-danger">' + fmtCents(gastos) + '</td>'
                        + '</tr>';
                });
            }
            if (sucursalChartInstance) sucursalChartInstance.destroy();
            if (sucs.length) {
                sucursalChartInstance = new Chart(document.getElementById('sucursalChart').getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels: sucs.map(s => s.sucursal),
                        datasets: [
                            { label: 'Ventas ($)', data: sucs.map(s => parseInt(s.total_cents || 0) / 100), backgroundColor: 'rgba(216, 178, 90, 0.7)', borderColor: 'rgba(216, 178, 90, 1)', borderWidth: 1 },
                            { label: 'Gastos ($)', data: sucs.map(s => parseInt(s.gastos_cents || 0) / 100), backgroundColor: 'rgba(220, 53, 69, 0.6)', borderColor: 'rgba(220, 53, 69, 1)', borderWidth: 1 },
                        ],
                    },
                    options: { responsive: true, plugins: { legend: { display: true } }, scales: { y: { beginAtZero: true } } },
                });
            }

            // Top ganancia
            const tgBody = document.getElementById('topGananciaBody');
            tgBody.innerHTML = '';
            const tgs = d.topGanancia || [];
            if (!tgs.length) {
                tgBody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                tgs.forEach((p, i) => {
                    tgBody.innerHTML += '<tr><td>' + (i + 1) + '</td><td>' + escHtml(p.producto) + '</td><td class="text-end">' + parseInt(p.qty_total || 0) + '</td><td class="text-end">' + fmtCents(parseInt(p.neto_cents || 0)) + '</td><td class="text-end">' + fmtCents(parseInt(p.costo_cents || 0)) + '</td><td class="text-end text-success">' + fmtCents(parseInt(p.ganancia_cents || 0)) + '</td></tr>';
                });
            }

            // Margen por departamento
            const mdBody = document.getElementById('margenDeptoBody');
            mdBody.innerHTML = '';
            const mds = d.margenDepto || [];
            if (!mds.length) {
                mdBody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                mds.forEach(dp => {
                    const n = parseInt(dp.neto_cents || 0);
                    const g = parseInt(dp.ganancia_cents || 0);
                    const mg = n > 0 ? (g / n * 100).toFixed(1) + '%' : '—';
                    mdBody.innerHTML += '<tr><td>' + escHtml(dp.departamento) + '</td><td class="text-end">' + parseInt(dp.qty_total || 0) + '</td><td class="text-end">' + fmtCents(n) + '</td><td class="text-end text-success">' + fmtCents(g) + '</td><td class="text-end">' + mg + '</td></tr>';
                });
            }

            // Top productos
            const topBody = document.getElementById('topProductosBody');
            topBody.innerHTML = '';
            const top = d.topProductos || [];
            if (!top.length) {
                topBody.innerHTML = '<tr><td colspan="6" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                top.forEach((p, i) => {
                    topBody.innerHTML += '<tr><td>' + (i + 1) + '</td><td>' + escHtml(p.producto) + '</td><td>' + escHtml(p.variedad || '-') + '</td><td class="text-end">' + parseInt(p.qty_total || 0) + '</td><td class="text-end">' + fmtCents(parseInt(p.total_cents || 0)) + '</td><td class="text-end">' + parseInt(p.facturas || 0) + '</td></tr>';
                });
            }

            // Depto
            const deptoBody = document.getElementById('deptoBody');
            deptoBody.innerHTML = '';
            const deptos = d.porDepartamento || [];
            if (!deptos.length) {
                deptoBody.innerHTML = '<tr><td colspan="5" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                const maxTotal = Math.max(...deptos.map(x => parseInt(x.total_cents || 0)), 1);
                deptos.forEach(dp => {
                    const pct = (parseInt(dp.total_cents || 0) / maxTotal * 100).toFixed(0);
                    deptoBody.innerHTML += '<tr><td>' + escHtml(dp.departamento) + '</td><td>' + escHtml(dp.codactiv || '-') + '</td><td class="text-end">' + parseInt(dp.qty_total || 0) + '</td><td class="text-end">' + fmtCents(parseInt(dp.total_cents || 0)) + '</td><td><div class="progress" style="height:6px"><div class="progress-bar bg-accent" style="width:' + pct + '%"></div></div></td></tr>';
                });
            }

            // Tipo
            const tipoBody = document.getElementById('tipoBody');
            tipoBody.innerHTML = '';
            const tipos = d.porTipo || [];
            if (!tipos.length) {
                tipoBody.innerHTML = '<tr><td colspan="3" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                tipos.forEach(t => {
                    tipoBody.innerHTML += '<tr><td>' + escHtml(t.tipo_comprobante) + '</td><td class="text-end">' + parseInt(t.cantidad || 0) + '</td><td class="text-end">' + fmtCents(parseInt(t.total_cents || 0)) + '</td></tr>';
                });
            }
        })
        .catch(e => {
            console.error(e);
            alert('Error al cargar reportes');
        })
        .finally(() => {
            document.getElementById('reporteLoader').style.display = 'none';
            document.getElementById('reporteContent').style.opacity = '1';
        });
}

function escHtml(s) {
    if (!s) return '';
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

function exportarCSV() {
    const form = document.getElementById('reporteForm');
    const fd = new FormData(form);
    const params = new URLSearchParams(fd);

    fetch('/admin/reportes/data?' + params.toString())
        .then(r => r.json())
        .then(d => {
            let csv = '\uFEFF';
            csv += 'Reporte de Ventas\n';
            csv += 'Periodo,' + params.get('desde') + ',' + params.get('hasta') + '\n\n';

            const res = d.resumen || {};
            csv += 'Resumen\n';
            csv += 'Facturas,' + (res.cantidad ?? 0) + '\n';
            csv += 'Total,' + (parseInt(res.total_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'IVA,' + (parseInt(res.iva_cents ?? 0) / 100).toFixed(2) + '\n\n';

            csv += 'Ventas Diarias\n';
            csv += 'Fecha,Cantidad,Total\n';
            (d.diarias || []).forEach(x => {
                csv += x.fecha + ',' + (x.cantidad ?? 0) + ',' + (parseInt(x.total_cents ?? 0) / 100).toFixed(2) + '\n';
            });

            csv += '\nTop Productos\n';
            csv += 'Producto,Variedad,Cantidad,Total,Facturas\n';
            (d.topProductos || []).forEach(p => {
                csv += (p.producto || '') + ',' + (p.variedad || '') + ',' + (p.qty_total ?? 0) + ',' + (parseInt(p.total_cents ?? 0) / 100).toFixed(2) + ',' + (p.facturas ?? 0) + '\n';
            });

            csv += '\nPor Departamento\n';
            csv += 'Departamento,Actividad,Cantidad,Total\n';
            (d.porDepartamento || []).forEach(dp => {
                csv += (dp.departamento || '') + ',' + (dp.codactiv || '') + ',' + (dp.qty_total ?? 0) + ',' + (parseInt(dp.total_cents ?? 0) / 100).toFixed(2) + '\n';
            });

            csv += '\nPor Forma de Pago\n';
            csv += 'Forma,Monto\n';
            (d.porFormaPago || []).forEach(f => {
                csv += '"' + String(f.forma_pago || '').replace(/"/g, '""') + '",' + (parseInt(f.total_cents ?? 0) / 100).toFixed(2) + '\n';
            });

            csv += '\nTarjetas por equipo POS\n';
            csv += 'Equipo,Pagos,Monto\n';
            (d.porEquipoTarjeta || []).forEach(e => {
                csv += '"' + String(e.equipo || '').replace(/"/g, '""') + '",' + (e.pagos ?? 0) + ',' + (parseInt(e.total_cents ?? 0) / 100).toFixed(2) + '\n';
            });

            csv += '\nComparativas\n';
            csv += 'Periodo,Desde,Hasta,Facturas,Total\n';
            const comp = d.comparativas || {};
            [['Mes actual', comp.mesActual], ['Mes anterior', comp.mesAnterior], ['Hace 1 año', comp.hace1Anio], ['Hace 2 años', comp.hace2Anios]].forEach(pair => {
                const r = pair[1] || {};
                csv += pair[0] + ',' + (r.desde || '') + ',' + (r.hasta || '') + ',' + (r.cantidad ?? 0) + ',' + (parseInt(r.total_cents ?? 0) / 100).toFixed(2) + '\n';
            });

            csv += '\nVentas Mensuales\n';
            csv += 'Mes,Facturas,Total\n';
            (d.mensuales || []).forEach(x => {
                csv += x.mes + ',' + (x.cantidad ?? 0) + ',' + (parseInt(x.total_cents ?? 0) / 100).toFixed(2) + '\n';
            });

            csv += '\nPor Sucursal\n';
            csv += 'Sucursal,Comprobantes,Ventas totales,Neto sin IVA,Descuentos,Costo,Ganancia neta,Margen neto %,Ticket promedio,Gastos\n';
            (d.porSucursal || []).forEach(s => {
                const total = parseInt(s.total_cents || 0);
                const cant = parseInt(s.cantidad || 0);
                const neto = parseInt(s.neto_cents || 0);
                const desc = parseInt(s.descuento_cents || 0) + parseInt(s.puntos_cents || 0);
                const gana = parseInt(s.ganancia_cents || 0);
                const margen = neto > 0 ? (gana / neto * 100).toFixed(1) : '0';
                const ticket = cant > 0 ? (total / cant / 100).toFixed(2) : '0';
                csv += '"' + String(s.sucursal || '').replace(/"/g, '""') + '",' + cant + ','
                    + (total / 100).toFixed(2) + ','
                    + (neto / 100).toFixed(2) + ','
                    + (desc / 100).toFixed(2) + ','
                    + (parseInt(s.costo_cents || 0) / 100).toFixed(2) + ','
                    + (gana / 100).toFixed(2) + ','
                    + margen + ','
                    + ticket + ','
                    + (parseInt(s.gastos_cents || 0) / 100).toFixed(2) + '\n';
            });

            const gan = d.ganancia || {};
            csv += '\nGanancia\n';
            csv += 'Neto sin IVA,' + (parseInt(gan.neto_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Costo,' + (parseInt(gan.costo_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Descuentos,' + (parseInt(gan.descuento_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Ganancia,' + (parseInt(gan.ganancia_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Ticket promedio,' + (parseInt(d.ticket ?? 0) / 100).toFixed(2) + '\n';

            csv += '\nTop por Ganancia\n';
            csv += 'Producto,Variedad,Cantidad,Neto,Costo,Ganancia\n';
            (d.topGanancia || []).forEach(p => {
                csv += (p.producto || '') + ',' + (p.variedad || '') + ',' + (p.qty_total ?? 0) + ',' + (parseInt(p.neto_cents ?? 0) / 100).toFixed(2) + ',' + (parseInt(p.costo_cents ?? 0) / 100).toFixed(2) + ',' + (parseInt(p.ganancia_cents ?? 0) / 100).toFixed(2) + '\n';
            });

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'reportes_' + params.get('desde') + '_' + params.get('hasta') + '.csv';
            link.click();
            URL.revokeObjectURL(link.href);
        });
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('reporteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        cargarReportes();
    });
    document.getElementById('btnExportar').addEventListener('click', exportarCSV);
    cargarReportes();
});
</script>

<style>
.bg-accent { background-color: #d8b25a !important; }
.progress { background-color: #e9ecef; }
</style>
