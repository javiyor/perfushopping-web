<?php
$ultimas = $ultimas ?? [];
?>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Ubicaciones del personal</h4>
        <p class="text-muted small">Última posición enviada por cada usuario (cada 5 minutos) · Solo visible para administradores</p>
    </div>
    <button class="btn btn-outline-secondary btn-sm" type="button" onclick="cargarUbicaciones()"><i class="bi bi-arrow-repeat"></i> Actualizar</button>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body p-2">
        <div id="mapUbicaciones" style="height:380px;border-radius:8px"></div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Latitud</th>
                    <th>Longitud</th>
                    <th>Precisión</th>
                    <th>Actualizado</th>
                </tr>
            </thead>
            <tbody id="ubicacionesBody"></tbody>
        </table>
    </div>
</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
var mapUbi = null;
var markersUbi = {};

function haceCuanto(fecha) {
    var t = new Date((fecha || '').replace(' ', 'T')).getTime();
    if (!t) return '—';
    var min = Math.floor((Date.now() - t) / 60000);
    if (min < 1) return 'ahora mismo';
    if (min < 60) return 'hace ' + min + ' min';
    var h = Math.floor(min / 60);
    if (h < 24) return 'hace ' + h + ' h';
    return 'hace ' + Math.floor(h / 24) + ' d';
}

function dibujarUbicaciones(rows) {
    var body = document.getElementById('ubicacionesBody');
    body.innerHTML = '';
    if (!rows.length) {
        body.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-4">Todavía nadie compartió su ubicación.</td></tr>';
    }
    if (mapUbi === null) {
        mapUbi = L.map('mapUbicaciones').setView([-34.6, -58.4], 6);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap',
        }).addTo(mapUbi);
    }
    Object.values(markersUbi).forEach(function(m) { mapUbi.removeLayer(m); });
    markersUbi = {};
    var bounds = [];
    rows.forEach(function(r) {
        var nombre = r.usuario_nombre || ('Usuario #' + r.admin_user_id);
        var lat = parseFloat(r.lat), lng = parseFloat(r.lng);
        body.innerHTML += '<tr><td class="fw-semibold">' + escUbi(nombre) + '</td>'
            + '<td class="small text-muted">' + escUbi(r.usuario_rol || '') + '</td>'
            + '<td class="small">' + lat.toFixed(5) + '</td>'
            + '<td class="small">' + lng.toFixed(5) + '</td>'
            + '<td class="small">' + (r.accuracy !== null ? '±' + r.accuracy + ' m' : '—') + '</td>'
            + '<td class="small">' + escUbi(haceCuanto(r.created_at)) + '</td></tr>';
        if (isFinite(lat) && isFinite(lng)) {
            markersUbi[r.admin_user_id] = L.marker([lat, lng]).addTo(mapUbi)
                .bindPopup('<strong>' + escUbi(nombre) + '</strong><br>' + escUbi(haceCuanto(r.created_at)));
            bounds.push([lat, lng]);
        }
    });
    if (bounds.length) {
        mapUbi.fitBounds(bounds, { padding: [40, 40], maxZoom: 15 });
    }
}

function escUbi(s) {
    var d = document.createElement('div');
    d.textContent = s || '';
    return d.innerHTML;
}

function cargarUbicaciones() {
    fetch('/admin/ubicaciones/data')
        .then(function(r) { return r.json(); })
        .then(function(d) { dibujarUbicaciones(d.ultimas || []); })
        .catch(function() {});
}

document.addEventListener('DOMContentLoaded', function() {
    dibujarUbicaciones(<?= json_encode($ultimas, JSON_UNESCAPED_UNICODE) ?>);
    setInterval(cargarUbicaciones, 60000);
});
</script>
