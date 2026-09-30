<?php
$ultimas = $ultimas ?? [];
$tokens = $tokens ?? [];
$usuarios = $usuarios ?? [];
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
                    <th>Estado</th>
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

<div class="card shadow-sm mt-3">
    <div class="card-header bg-white fw-semibold">Tracking con la app cerrada</div>
    <div class="card-body small">
        <p class="text-muted mb-2">La PWA solo reporta con la app abierta. Para que reporte <strong>aunque esté cerrada</strong>, instalá en el celular <strong>OwnTracks</strong> (Android/iPhone) o <strong>GPSLogger</strong> (Android) y configuralo con la URL del token generado abajo. Cada token es por usuario y se puede revocar.</p>
        <ul class="text-muted mb-3 ps-3">
            <li><strong>OwnTracks:</strong> modo HTTP, URL = la del token, usuario = cualquiera. Intervalo de reporte: 5 min.</li>
            <li><strong>GPSLogger:</strong> registro → URL personalizada: <code>/api/ubicacion?token=XXX&amp;lat=%LAT&amp;lon=%LON</code>, intervalo 5 min.</li>
        </ul>
        <form method="post" action="/admin/ubicaciones/token/crear" class="row g-2 align-items-end">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="col-md-5">
                <label class="form-label small">Usuario</label>
                <select name="admin_user_id" class="form-select form-select-sm" required>
                    <option value="">— Elegir —</option>
                    <?php foreach ($usuarios as $u): ?>
                        <option value="<?= (int)$u['id'] ?>"><?= htmlspecialchars((string)($u['nombre'] ?? '')) ?> (<?= htmlspecialchars((string)($u['rol'] ?? '')) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small">Dispositivo</label>
                <input type="text" name="dispositivo" class="form-control form-control-sm" placeholder="Ej: Moto de reparto" />
            </div>
            <div class="col-md-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-key"></i> Generar token</button>
            </div>
        </form>
    </div>
    <?php if ($tokens): ?>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Dispositivo</th>
                    <th>URL</th>
                    <th>Estado</th>
                    <th style="width:90px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tokens as $t): ?>
                    <tr>
                        <td class="small fw-semibold"><?= htmlspecialchars((string)($t['usuario_nombre'] ?? '')) ?></td>
                        <td class="small"><?= htmlspecialchars((string)($t['dispositivo'] ?? '')) ?></td>
                        <td class="small"><code>/api/ubicacion?token=<?= htmlspecialchars((string)$t['token']) ?></code>
                            <button class="btn btn-sm btn-outline-secondary py-0 px-1" type="button" onclick="copiarToken('<?= htmlspecialchars((string)$t['token']) ?>', this)" title="Copiar URL completa"><i class="bi bi-clipboard"></i></button>
                        </td>
                        <td><?= !empty($t['activo']) ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Revocado</span>' ?></td>
                        <td>
                            <?php if (!empty($t['activo'])): ?>
                            <form method="post" action="/admin/ubicaciones/token/revocar" class="d-inline" onsubmit="return confirm('¿Revocar este token? Dejará de reportar.')">
                                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-1">Revocar</button>
                            </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
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

function estadoUbi(fecha) {
    var t = new Date((fecha || '').replace(' ', 'T')).getTime();
    if (!t) return '<span class="badge bg-secondary">Sin datos</span>';
    var min = (Date.now() - t) / 60000;
    if (min <= 15) return '<span class="badge bg-success">Activo</span>';
    return '<span class="badge bg-secondary">Inactivo</span>';
}

function copiarToken(token, btn) {
    var url = window.location.origin + '/api/ubicacion?token=' + token;
    var done = function() {
        btn.innerHTML = '<i class="bi bi-check-lg"></i>';
        setTimeout(function() { btn.innerHTML = '<i class="bi bi-clipboard"></i>'; }, 1500);
    };
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(done, function() {});
    } else {
        var ta = document.createElement('textarea');
        ta.value = url;
        ta.style.position = 'fixed';
        ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        try { document.execCommand('copy'); done(); } catch (e) {}
        ta.remove();
    }
}

function dibujarUbicaciones(rows) {
    var body = document.getElementById('ubicacionesBody');
    body.innerHTML = '';
    if (!rows.length) {
        body.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">Todavía nadie compartió su ubicación.</td></tr>';
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
            + '<td>' + estadoUbi(r.created_at) + '</td>'
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
