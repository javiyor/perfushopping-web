<?php
use Perfushopping\Web\Support\Format;

$desde = (string)($desde ?? date('Y-m-01'));
$hasta = (string)($hasta ?? date('Y-m-d'));
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
                <div class="card-body p-0">
                    <table class="table table-admin mb-0">
                        <thead><tr><th>Forma</th><th class="text-end">Monto</th></tr></thead>
                        <tbody id="formaPagoBody"></tbody>
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
                        <thead><tr><th>Departamento</th><th class="text-end">Cant.</th><th class="text-end">Total</th><th style="width:40%"></th></tr></thead>
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
            const res = d.resumen || {};
            document.getElementById('kpiFacturas').textContent = (res.cantidad ?? 0);
            document.getElementById('kpiTotal').textContent = fmtCents(parseInt(res.total_cents ?? 0));
            document.getElementById('kpiIVA').textContent = fmtCents(parseInt(res.iva_cents ?? 0));

            const rec = d.recibos || {};
            document.getElementById('kpiRecibos').textContent = fmtCents(parseInt(rec.total_cents ?? 0));

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
            const fpBody = document.getElementById('formaPagoBody');
            fpBody.innerHTML = '';
            const formas = d.porFormaPago || [];
            if (!formas.length) {
                fpBody.innerHTML = '<tr><td colspan="2" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                formas.forEach(f => {
                    fpBody.innerHTML += '<tr><td>' + escHtml(f.forma_pago) + '</td><td class="text-end">' + fmtCents(parseInt(f.total_cents || 0)) + '</td></tr>';
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
                deptoBody.innerHTML = '<tr><td colspan="4" class="text-muted text-center">Sin datos</td></tr>';
            } else {
                const maxTotal = Math.max(...deptos.map(x => parseInt(x.total_cents || 0)), 1);
                deptos.forEach(dp => {
                    const pct = (parseInt(dp.total_cents || 0) / maxTotal * 100).toFixed(0);
                    deptoBody.innerHTML += '<tr><td>' + escHtml(dp.departamento) + '</td><td class="text-end">' + parseInt(dp.qty_total || 0) + '</td><td class="text-end">' + fmtCents(parseInt(dp.total_cents || 0)) + '</td><td><div class="progress" style="height:6px"><div class="progress-bar bg-accent" style="width:' + pct + '%"></div></div></td></tr>';
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
            csv += 'Departamento,Cantidad,Total\n';
            (d.porDepartamento || []).forEach(dp => {
                csv += (dp.departamento || '') + ',' + (dp.qty_total ?? 0) + ',' + (parseInt(dp.total_cents ?? 0) / 100).toFixed(2) + '\n';
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
