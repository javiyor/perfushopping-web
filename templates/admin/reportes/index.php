<?php
use Perfushopping\Web\Support\Format;

$desde = (string)($desde ?? date('Y-m-d'));
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
            <div class="col-12 d-flex flex-wrap align-items-center gap-2 mt-1">
                <span class="small text-muted">Rangos rápidos:</span>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-reporte-rango="hoy">Hoy</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-reporte-rango="mes-actual">Mes actual</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-reporte-rango="mes-anterior">Mes anterior</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" data-reporte-rango="anio-actual">Año actual</button>
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

    <div class="row g-3 mb-4" id="kpiRow3">
        <div class="col-6 col-md-4">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0 text-success" id="kpiAutorizadas">-</div>
                <div class="small text-muted">Autorizadas ARCA (<span id="kpiAutorizadasCant">-</span> compr.)</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0 text-danger" id="kpiNoAutorizadas">-</div>
                <div class="small text-muted">No autorizadas (<span id="kpiNoAutorizadasCant">-</span> compr.)</div>
            </div>
        </div>
        <div class="col-6 col-md-4">
            <div class="card-dashboard text-center">
                <div class="h3 fw-bold mb-0" id="kpiPromDiario">-</div>
                <div class="small text-muted">Promedio diario (<span id="kpiPromDiarioDias">-</span> días)</div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Comparativa del período</div>
                <div class="card-body">
                    <canvas id="comparativaPeriodoChart" height="110"></canvas>
                    <div class="small text-muted mt-2" id="comparativaPeriodoDetalle"></div>
                    <div class="small text-muted">Las ventas y la ganancia respetan el punto de venta seleccionado. Compras y gastos son globales y se filtran por fecha; las compras incluyen comprobantes no anulados, las notas de crédito restan y las monedas extranjeras usan su tipo de cambio.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-4">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Valor de mercadería en existencia</div>
                <div class="card-body">
                    <div class="h3 fw-bold mb-1" id="inventarioSnapshotValor">—</div>
                    <div class="small text-muted" id="inventarioSnapshotFecha">Esperando primera captura mensual.</div>
                    <div class="small text-warning mt-2" id="inventarioSnapshotAviso" style="display:none"></div>
                    <div class="small text-muted mt-2">Foto global del stock de depósitos operativos, valorado al costo unitario vigente (precomp), independiente del punto de venta seleccionado. Se guarda en la primera consulta de reportes del mes.</div>
                </div>
            </div>
        </div>
        <div class="col-lg-8">
            <div class="card shadow-sm h-100">
                <div class="card-header bg-white fw-semibold">Comparativa mensual del valor de inventario</div>
                <div class="card-body">
                    <canvas id="inventarioChart" height="150"></canvas>
                    <div class="small text-muted mt-2" id="inventarioSinHistorial" style="display:none">Todavía no hay capturas de meses anteriores. Se irán agregando en la primera consulta de cada mes.</div>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <span>Productos con stock sin costo válido — no modificados</span>
                    <div class="d-flex align-items-center gap-2">
                        <span class="badge bg-warning text-dark" id="inventarioSinCostoCount">0</span>
                        <button class="btn btn-outline-secondary btn-sm" type="button" id="btnImprimirSinCosto" onclick="imprimirProductosSinCosto()" disabled>
                            <i class="bi bi-printer"></i> Imprimir listado
                        </button>
                    </div>
                </div>
                <div class="card-body pb-2">
                    <div class="small text-muted" id="inventarioSinCostoEstado">Se listan productos con existencia y precomp ausente o igual a cero. El reporte no modifica la tabla producto.</div>
                </div>
                <div class="table-responsive">
                    <table class="table table-admin table-sm mb-0">
                        <thead><tr><th>ID producto</th><th>Descripción</th><th>Códigos de barra</th><th class="text-end">Precomp</th><th class="text-end">Precio</th><th class="text-end">Precio con IVA</th><th class="text-end">Stock</th></tr></thead>
                        <tbody id="inventarioSinCostoBody"><tr><td colspan="7" class="text-muted text-center">Cargando…</td></tr></tbody>
                    </table>
                </div>
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
                                <th class="text-end">Prom. diario</th>
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
        <!-- Por vendedor -->
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Ventas por vendedor</div>
                <div class="table-responsive">
                    <table class="table table-admin mb-0">
                        <thead>
                            <tr>
                                <th>Vendedor</th>
                                <th class="text-end">Comprobantes</th>
                                <th class="text-end">Total</th>
                                <th class="text-end">Ticket prom.</th>
                            </tr>
                        </thead>
                        <tbody id="vendedorBody"></tbody>
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
                <div class="card-header bg-white fw-semibold">Ventas por actividad/departamento (departa.codactiv)</div>
                <div class="card-body">
                    <canvas id="departamentoChart" height="150"></canvas>
                </div>
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
let comparativaPeriodoChartInstance = null;
let inventarioChartInstance = null;
let departamentoChartInstance = null;
let productosSinCostoParaImprimir = [];
let detalleSinCostoEsSnapshot = false;
let fechaDetalleSinCosto = '';

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

            // Autorizadas vs no autorizadas + promedio diario.
            // Total vendido, ticket promedio y promedio diario suman ambos grupos.
            const aut = (d.autorizacion && d.autorizacion.autorizadas) || {};
            const noaut = (d.autorizacion && d.autorizacion.no_autorizadas) || {};
            const totAll = parseInt(aut.total_cents ?? 0) + parseInt(noaut.total_cents ?? 0);
            const cantAll = parseInt(aut.cantidad ?? 0) + parseInt(noaut.cantidad ?? 0);
            const dias = Math.max(1, parseInt(d.dias ?? 1));
            document.getElementById('kpiTotal').textContent = fmtCents(totAll);
            document.getElementById('kpiTicket').textContent = cantAll > 0 ? fmtCents(Math.round(totAll / cantAll)) : '—';

            // Comparativa financiera del período. Compras y gastos no se asignan a un PV.
            const cg = d.comprasYGastos || {};
            const comprasCents = parseInt(cg.compras_cents || 0);
            const gastosCents = parseInt(cg.gastos_cents || 0);
            const cgTotal = parseInt(cg.total_cents || 0);
            const comparativaDetalle = document.getElementById('comparativaPeriodoDetalle');
            if (comparativaDetalle) {
                comparativaDetalle.textContent = 'Compras: ' + fmtCents(comprasCents) + ' · Gastos: ' + fmtCents(gastosCents) + ' · Compras + gastos: ' + fmtCents(cgTotal);
            }
            if (comparativaPeriodoChartInstance) comparativaPeriodoChartInstance.destroy();
            comparativaPeriodoChartInstance = new Chart(document.getElementById('comparativaPeriodoChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Ventas totales', 'Ganancia neta', 'Compras + gastos'],
                    datasets: [{
                        label: 'Monto ($)',
                        data: [totAll / 100, ganancia / 100, cgTotal / 100],
                        backgroundColor: ['rgba(216,178,90,0.75)', 'rgba(25,135,84,0.7)', 'rgba(220,53,69,0.65)'],
                        borderColor: ['rgb(216,178,90)', 'rgb(25,135,84)', 'rgb(220,53,69)'],
                        borderWidth: 1,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {legend: {display: false}, tooltip: {callbacks: {label: ctx => fmtCents(Math.round(ctx.parsed.x * 100))}}},
                    scales: {x: {beginAtZero: true, ticks: {callback: value => '$' + Number(value).toLocaleString('es-AR')}}},
                },
            });

            // Valor de inventario capturado una sola vez por mes, sin reconstruir meses anteriores.
            const inventario = d.inventario || {};
            const snapshot = inventario.snapshot || null;
            document.getElementById('inventarioSnapshotValor').textContent = snapshot ? fmtCents(parseInt(snapshot.valor_cents || 0)) : '—';
            document.getElementById('inventarioSnapshotFecha').textContent = snapshot
                ? 'Capturado ' + (snapshot.fecha_captura || '') + ' · Período ' + String(snapshot.periodo_mes || '').slice(0, 7)
                : (inventario.error || 'No se pudo registrar una captura mensual.');
            const inventarioAviso = document.getElementById('inventarioSnapshotAviso');
            if (inventarioAviso) {
                const sinCosto = parseInt(snapshot?.variantes_sin_costo || 0);
                inventarioAviso.textContent = sinCosto > 0 ? sinCosto + ' variante(s) con stock no tienen costo precomp válido y aportan $0 a la valoración.' : '';
                inventarioAviso.style.display = sinCosto > 0 ? '' : 'none';
            }
            const inventarioHistory = inventario.historial || [];
            const inventarioSinHistorial = document.getElementById('inventarioSinHistorial');
            if (inventarioSinHistorial) inventarioSinHistorial.style.display = inventarioHistory.length <= 1 ? '' : 'none';
            if (inventarioChartInstance) inventarioChartInstance.destroy();
            inventarioChartInstance = new Chart(document.getElementById('inventarioChart').getContext('2d'), {
                type: 'line',
                data: {
                    labels: inventarioHistory.map(x => String(x.periodo_mes || '').slice(0, 7)),
                    datasets: [{
                        label: 'Valor de inventario ($)',
                        data: inventarioHistory.map(x => parseInt(x.valor_cents || 0) / 100),
                        borderColor: 'rgb(13,110,253)',
                        backgroundColor: 'rgba(13,110,253,0.15)',
                        fill: true,
                        tension: 0.2,
                    }],
                },
                options: {responsive: true, plugins: {tooltip: {callbacks: {label: ctx => fmtCents(Math.round(ctx.parsed.y * 100))}}}, scales: {y: {beginAtZero: true}}},
            });

            const detalleSnapshotDisponible = !!snapshot && !!snapshot.detalle_sin_costo_disponible;
            const productosSinCosto = detalleSnapshotDisponible
                ? (snapshot.productos_sin_costo || [])
                : (snapshot?.productos_sin_costo_actual || []);
            productosSinCostoParaImprimir = productosSinCosto;
            detalleSinCostoEsSnapshot = detalleSnapshotDisponible;
            fechaDetalleSinCosto = (snapshot?.fecha_captura || '');
            const sinCostoBody = document.getElementById('inventarioSinCostoBody');
            const sinCostoCount = document.getElementById('inventarioSinCostoCount');
            const sinCostoEstado = document.getElementById('inventarioSinCostoEstado');
            const imprimirSinCostoBtn = document.getElementById('btnImprimirSinCosto');
            if (sinCostoCount) sinCostoCount.textContent = productosSinCosto.length;
            if (imprimirSinCostoBtn) imprimirSinCostoBtn.disabled = productosSinCosto.length === 0;
            if (sinCostoEstado) {
                sinCostoEstado.textContent = !snapshot
                    ? (inventario.error || 'No hay una captura mensual disponible.')
                    : (detalleSnapshotDisponible
                        ? 'Detalle guardado con la captura del ' + (snapshot.fecha_captura || '') + '. Los productos con precomp cero o ausente aportan $0 a la valuación y no se modifica producto.'
                        : 'El snapshot de este mes no guardó el detalle por producto. Se muestra la lista actual; no implica una actualización ni reconstruye el estado histórico.');
            }
            if (sinCostoBody) {
                sinCostoBody.innerHTML = productosSinCosto.length
                    ? productosSinCosto.map(p => '<tr>'
                        + '<td>' + parseInt(p.idprodu || 0) + '</td>'
                        + '<td>' + escHtml(p.producto || '') + '</td>'
                        + '<td class="small"><code>' + escHtml(p.codscan || '') + '</code></td>'
                        + '<td class="text-end">' + (p.precomp === null ? '—' : fmtCents(Math.round(parseFloat(p.precomp || 0) * 100))) + '</td>'
                        + '<td class="text-end">' + fmtCents(Math.round(parseFloat(p.precio || 0) * 100)) + '</td>'
                        + '<td class="text-end">' + fmtCents(Math.round(parseFloat(p.precio_iva || 0) * 100)) + '</td>'
                        + '<td class="text-end">' + Number(p.stock_unidades || 0).toLocaleString('es-AR', {maximumFractionDigits: 3}) + '</td>'
                        + '</tr>').join('')
                    : '<tr><td colspan="7" class="text-muted text-center">No hay productos con stock y costo precomp ausente o igual a cero.</td></tr>';
            }
            document.getElementById('kpiAutorizadas').textContent = fmtCents(parseInt(aut.total_cents ?? 0));
            document.getElementById('kpiAutorizadasCant').textContent = parseInt(aut.cantidad ?? 0);
            document.getElementById('kpiNoAutorizadas').textContent = fmtCents(parseInt(noaut.total_cents ?? 0));
            document.getElementById('kpiNoAutorizadasCant').textContent = parseInt(noaut.cantidad ?? 0);
            document.getElementById('kpiPromDiario').textContent = fmtCents(Math.round(totAll / dias));
            document.getElementById('kpiPromDiarioDias').textContent = dias;

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
            const diasS = Math.max(1, parseInt(d.dias ?? 1));
            if (!sucs.length) {
                sucBody.innerHTML = '<tr><td colspan="11" class="text-muted text-center">Sin datos</td></tr>';
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
                    const promDiario = fmtCents(Math.round(total / diasS));
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
                        + '<td class="text-end">' + promDiario + '</td>'
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
            if (departamentoChartInstance) departamentoChartInstance.destroy();
            departamentoChartInstance = new Chart(document.getElementById('departamentoChart').getContext('2d'), {
                type: 'bar',
                data: {
                    labels: deptos.map(x => String(x.codactiv || '0') + ' · ' + (x.departamento || 'Sin actividad')),
                    datasets: [{
                        label: 'Ventas por actividad ($)',
                        data: deptos.map(x => parseInt(x.total_cents || 0) / 100),
                        backgroundColor: chartColors(deptos.length),
                        borderWidth: 1,
                    }],
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    plugins: {legend: {display: false}, tooltip: {callbacks: {label: ctx => fmtCents(Math.round(ctx.parsed.x * 100))}}},
                    scales: {x: {beginAtZero: true, ticks: {callback: value => '$' + Number(value).toLocaleString('es-AR')}}},
                },
            });
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

            // Vendedores
            const vendBody = document.getElementById('vendedorBody');
            vendBody.innerHTML = '';
            const vends = d.porVendedor || [];
            if (!vends.length) {
                vendBody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                vends.forEach(v => {
                    const vTotal = parseInt(v.total_cents || 0);
                    const vCant = parseInt(v.cantidad || 0);
                    const vTicket = vCant > 0 ? fmtCents(Math.round(vTotal / vCant)) : '—';
                    vendBody.innerHTML += '<tr><td>' + escHtml(v.vendedor) + '</td><td class="text-end">' + vCant + '</td><td class="text-end">' + fmtCents(vTotal) + '</td><td class="text-end">' + vTicket + '</td></tr>';
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

function imprimirProductosSinCosto() {
    if (!productosSinCostoParaImprimir.length) return;
    const popup = window.open('', '_blank', 'width=1000,height=750');
    if (!popup) {
        alert('Permití las ventanas emergentes para imprimir el listado.');
        return;
    }
    const rows = productosSinCostoParaImprimir.map(p => '<tr>'
        + '<td>' + parseInt(p.idprodu || 0) + '</td>'
        + '<td>' + escHtml(p.producto || '') + '</td>'
        + '<td>' + escHtml(p.codscan || '') + '</td>'
        + '<td class="num">' + (p.precomp === null ? '—' : fmtCents(Math.round(parseFloat(p.precomp || 0) * 100))) + '</td>'
        + '<td class="num">' + fmtCents(Math.round(parseFloat(p.precio || 0) * 100)) + '</td>'
        + '<td class="num">' + fmtCents(Math.round(parseFloat(p.precio_iva || 0) * 100)) + '</td>'
        + '</tr>').join('');
    const alcance = detalleSinCostoEsSnapshot
        ? 'Detalle del snapshot mensual capturado el ' + fechaDetalleSinCosto + '.'
        : 'Estado actual: el detalle de productos no se guardó en el snapshot mensual.';
    popup.document.write('<!doctype html><html lang="es"><head><meta charset="utf-8"><title>Productos sin costo válido</title>'
        + '<style>body{font:14px Arial,sans-serif;padding:24px}h1{font-size:20px}p{color:#555}table{border-collapse:collapse;width:100%}th,td{border:1px solid #bbb;padding:6px;text-align:left}.num{text-align:right}@media print{button{display:none}}</style>'
        + '</head><body><h1>Productos con stock sin costo válido</h1><p>' + escHtml(alcance) + ' Listado informativo de precomp ausente o igual a cero. No se modificó la tabla producto.</p>'
        + '<table><thead><tr><th>ID producto</th><th>Descripción</th><th>Codscan</th><th>Precomp</th><th>Precio</th><th>Precio con IVA</th></tr></thead><tbody>' + rows + '</tbody></table></body></html>');
    popup.document.close();
    popup.focus();
    popup.print();
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
            const autCsv = (d.autorizacion && d.autorizacion.autorizadas) || {};
            const noautCsv = (d.autorizacion && d.autorizacion.no_autorizadas) || {};
            const totCsv = parseInt(autCsv.total_cents ?? 0) + parseInt(noautCsv.total_cents ?? 0);
            const cantCsv = parseInt(autCsv.cantidad ?? 0) + parseInt(noautCsv.cantidad ?? 0);
            const diasCsv = Math.max(1, parseInt(d.dias ?? 1));
            csv += 'Total,' + (totCsv / 100).toFixed(2) + '\n';
            csv += 'Ticket promedio,' + (cantCsv > 0 ? (totCsv / cantCsv / 100).toFixed(2) : '0') + '\n';
            csv += 'IVA,' + (parseInt(res.iva_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Autorizadas cant.,' + (autCsv.cantidad ?? 0) + '\n';
            csv += 'Autorizadas total,' + (parseInt(autCsv.total_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'No autorizadas cant.,' + (noautCsv.cantidad ?? 0) + '\n';
            csv += 'No autorizadas total,' + (parseInt(noautCsv.total_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Dias periodo,' + diasCsv + '\n';
            csv += 'Promedio diario,' + (totCsv / diasCsv / 100).toFixed(2) + '\n\n';

            const gananciasCsv = d.ganancia || {};
            const comprasGastosCsv = d.comprasYGastos || {};
            const invCsv = d.inventario || {};
            const invSnapshotCsv = invCsv.snapshot || {};
            csv += 'Comparativa financiera del período\n';
            csv += 'Ganancia neta,' + (parseInt(gananciasCsv.ganancia_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Compras,' + (parseInt(comprasGastosCsv.compras_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Gastos,' + (parseInt(comprasGastosCsv.gastos_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Compras + gastos,' + (parseInt(comprasGastosCsv.total_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Inventario capturado,' + (invSnapshotCsv.fecha_captura || 'Sin captura') + ',' + (parseInt(invSnapshotCsv.valor_cents ?? 0) / 100).toFixed(2) + '\n\n';

            csv += 'Historial mensual del valor de inventario\n';
            csv += 'Período,Fecha captura,Valor,Variantes con stock,Variantes sin costo\n';
            (invCsv.historial || []).forEach(s => {
                csv += (s.periodo_mes || '') + ',' + (s.fecha_captura || '') + ','
                    + (parseInt(s.valor_cents || 0) / 100).toFixed(2) + ','
                    + (s.variantes_con_stock ?? 0) + ',' + (s.variantes_sin_costo ?? 0) + '\n';
            });

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
            csv += 'Sucursal,Comprobantes,Ventas totales,Neto sin IVA,Descuentos,Costo,Ganancia neta,Margen neto %,Ticket promedio,Promedio diario,Gastos\n';
            (d.porSucursal || []).forEach(s => {
                const total = parseInt(s.total_cents || 0);
                const cant = parseInt(s.cantidad || 0);
                const neto = parseInt(s.neto_cents || 0);
                const desc = parseInt(s.descuento_cents || 0) + parseInt(s.puntos_cents || 0);
                const gana = parseInt(s.ganancia_cents || 0);
                const margen = neto > 0 ? (gana / neto * 100).toFixed(1) : '0';
                const ticket = cant > 0 ? (total / cant / 100).toFixed(2) : '0';
                const promD = (total / diasCsv / 100).toFixed(2);
                csv += '"' + String(s.sucursal || '').replace(/"/g, '""') + '",' + cant + ','
                    + (total / 100).toFixed(2) + ','
                    + (neto / 100).toFixed(2) + ','
                    + (desc / 100).toFixed(2) + ','
                    + (parseInt(s.costo_cents || 0) / 100).toFixed(2) + ','
                    + (gana / 100).toFixed(2) + ','
                    + margen + ','
                    + ticket + ','
                    + promD + ','
                    + (parseInt(s.gastos_cents || 0) / 100).toFixed(2) + '\n';
            });

            csv += '\nPor Vendedor\n';
            csv += 'Vendedor,Comprobantes,Total,Ticket promedio\n';
            (d.porVendedor || []).forEach(v => {
                const vTotal = parseInt(v.total_cents || 0);
                const vCant = parseInt(v.cantidad || 0);
                const vTicket = vCant > 0 ? (vTotal / vCant / 100).toFixed(2) : '0';
                csv += '"' + String(v.vendedor || '').replace(/"/g, '""') + '",' + vCant + ',' + (vTotal / 100).toFixed(2) + ',' + vTicket + '\n';
            });

            const gan = d.ganancia || {};
            csv += '\nGanancia\n';
            csv += 'Neto sin IVA,' + (parseInt(gan.neto_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Costo,' + (parseInt(gan.costo_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Descuentos,' + (parseInt(gan.descuento_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Ganancia,' + (parseInt(gan.ganancia_cents ?? 0) / 100).toFixed(2) + '\n';
            csv += 'Ticket promedio,' + (cantCsv > 0 ? (totCsv / cantCsv / 100).toFixed(2) : '0') + '\n';

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

function fechaLocalISO(fecha) {
    return fecha.getFullYear() + '-' + String(fecha.getMonth() + 1).padStart(2, '0') + '-' + String(fecha.getDate()).padStart(2, '0');
}

function aplicarRangoReporte(tipo) {
    const hoy = new Date();
    const hoyIso = fechaLocalISO(hoy);
    let desde = hoyIso;
    let hasta = hoyIso;
    if (tipo === 'mes-actual') {
        desde = fechaLocalISO(new Date(hoy.getFullYear(), hoy.getMonth(), 1));
    } else if (tipo === 'mes-anterior') {
        desde = fechaLocalISO(new Date(hoy.getFullYear(), hoy.getMonth() - 1, 1));
        hasta = fechaLocalISO(new Date(hoy.getFullYear(), hoy.getMonth(), 0));
    } else if (tipo === 'anio-actual') {
        desde = hoy.getFullYear() + '-01-01';
    }
    document.querySelector('#reporteForm [name="desde"]').value = desde;
    document.querySelector('#reporteForm [name="hasta"]').value = hasta;
    cargarReportes();
}

document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('reporteForm').addEventListener('submit', function(e) {
        e.preventDefault();
        cargarReportes();
    });
    document.getElementById('btnExportar').addEventListener('click', exportarCSV);
    document.querySelectorAll('[data-reporte-rango]').forEach(function(btn) {
        btn.addEventListener('click', function() { aplicarRangoReporte(btn.dataset.reporteRango); });
    });
    cargarReportes();
});
</script>

<style>
.bg-accent { background-color: #d8b25a !important; }
.progress { background-color: #e9ecef; }
</style>
