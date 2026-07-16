<?php
use Perfushopping\Web\Support\Format;

$list = $list ?? [];
$q = (string)($q ?? '');
$customerCategories = [
    'none' => 'Sin categoría', 'peluquero' => 'Peluquero/a', 'cosmetologa' => 'Cosmetóloga',
    'esteticista' => 'Esteticista', 'manicura' => 'Manicura/o', 'masajista' => 'Masajista',
    'barbero' => 'Barbero/a', 'maquillador' => 'Maquillador/a', 'spa' => 'Spa / centro estético',
    'revendedor' => 'Revendedor/a', 'otro' => 'Otro profesional',
];
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Clientes</h4>
        <p class="text-muted small">Usuarios registrados en la web con historial de compras</p>
    </div>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#arcaModal"><i class="bi bi-cloud-download"></i> Nuevo desde ARCA</button>
    <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrf ?? '') ?>" />
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/clientes" class="row g-2">
            <div class="col-lg-8">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, email o teléfono" />
            </div>
            <div class="col-lg-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
            <div class="col-lg-2">
                <?php if ($q !== ''): ?>
                    <a class="btn btn-outline-secondary btn-sm w-100" href="/admin/clientes"><i class="bi bi-x-lg"></i> Limpiar</a>
                <?php endif; ?>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Teléfono</th>
                    <th>Categoría</th>
                    <th>Pedidos</th>
                    <th>Total gastado</th>
                    <th>Último pedido</th>
                    <th>Registro</th>
                    <th>Estado</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="11" class="text-muted text-center py-4">No se encontraron clientes.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $u): ?>
                        <?php $isBlocked = !empty($u['disabled_at']); ?>
                        <tr class="<?= $isBlocked ? 'table-light text-muted' : '' ?>">
                            <td><?= (int)($u['id'] ?? 0) ?></td>
                            <td>
                                <strong><?= htmlspecialchars((string)($u['name'] ?? 'Sin nombre')) ?></strong>
                                <?php if (($u['wholesale_status'] ?? '') === 'approved'): ?>
                                    <span class="badge bg-info">Mayorista</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars((string)($u['email'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars((string)($u['phone'] ?? '-')) ?></td>
                            <td><?= htmlspecialchars($customerCategories[$u['customer_category'] ?? 'none'] ?? 'Sin categoría') ?></td>
                            <td class="text-center"><?= (int)($u['order_count'] ?? 0) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($u['total_spent_cents'] ?? 0))) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($u['last_order_at'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($u['created_at'] ?? '-')) ?></td>
                            <td>
                                <?php if ($isBlocked): ?>
                                    <span class="badge bg-secondary">Bloqueado</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary" href="/admin/clientes/<?= (int)($u['id'] ?? 0) ?>">
                                    <i class="bi bi-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($list && count($list) >= 60): ?>
        <div class="card-footer text-muted small text-center">Mostrando hasta 60 resultados. Refiná la búsqueda si no encontrás lo que buscás.</div>
    <?php endif; ?>
</div>

<!-- ARCA search modal -->
<div class="modal fade" id="arcaModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-cloud-download"></i> Buscar en ARCA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small">CUIT (11 dígitos) o DNI (7-8 dígitos)</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="arcaInput" placeholder="Ej: 20334942813" maxlength="11" autocomplete="off" />
                        <button class="btn btn-accent" type="button" id="arcaSearchBtn" onclick="buscarArca()"><i class="bi bi-search"></i> Buscar</button>
                    </div>
                </div>
                <div id="arcaResult" style="display:none">
                    <hr />
                    <div class="small fw-semibold mb-2">Datos obtenidos de ARCA:</div>
                    <dl class="row small mb-2" id="arcaData"></dl>
                    <button class="btn btn-accent btn-sm w-100" id="arcaSaveBtn" onclick="crearDesdeArca()"><i class="bi bi-person-plus"></i> Crear cliente</button>
                </div>
                <div id="arcaError" class="alert alert-danger small" style="display:none"></div>
                <div id="arcaSpinner" class="text-center py-3" style="display:none">
                    <div class="spinner-border spinner-border-sm text-accent" role="status"></div>
                    <span class="small ms-2">Consultando ARCA...</span>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let ultimaPersona = null;

function buscarArca() {
    const q = document.getElementById('arcaInput').value.trim();
    if (!q) { alert('Ingresá un CUIT o DNI.'); return; }
    document.getElementById('arcaResult').style.display = 'none';
    document.getElementById('arcaError').style.display = 'none';
    document.getElementById('arcaSpinner').style.display = '';
    document.getElementById('arcaSearchBtn').disabled = true;

    fetch('/admin/clientes/buscar-arca?q=' + encodeURIComponent(q))
        .then(r => r.json())
        .then(res => {
            document.getElementById('arcaSpinner').style.display = 'none';
            document.getElementById('arcaSearchBtn').disabled = false;
            if (!res.ok) {
                document.getElementById('arcaError').textContent = res.error;
                document.getElementById('arcaError').style.display = '';
                return;
            }
            ultimaPersona = res.persona;
            const p = res.persona;
            const condIvaLabel = { 'responsable_inscripto': 'Responsable Inscripto', 'consumidor_final': 'Consumidor Final', 'monotributista': 'Monotributista', 'exento': 'Exento' };
            document.getElementById('arcaData').innerHTML =
                '<dt class="col-sm-4">CUIT</dt><dd class="col-sm-8">' + esc(p.cuit) + '</dd>' +
                '<dt class="col-sm-4">Razón social</dt><dd class="col-sm-8 fw-bold">' + esc(p.razon || p.razonSocial || '') + '</dd>' +
                '<dt class="col-sm-4">Dirección</dt><dd class="col-sm-8">' + esc(p.direc || '-') + '</dd>' +
                '<dt class="col-sm-4">Localidad</dt><dd class="col-sm-8">' + esc(p.localidad || '-') + '</dd>' +
                '<dt class="col-sm-4">Provincia</dt><dd class="col-sm-8">' + esc(p.provincia || '-') + '</dd>' +
                '<dt class="col-sm-4">Cond. IVA</dt><dd class="col-sm-8">' + esc(condIvaLabel[p.condicion_iva] || p.condicion_iva) + '</dd>';
            document.getElementById('arcaResult').style.display = '';
        })
        .catch(err => {
            document.getElementById('arcaSpinner').style.display = 'none';
            document.getElementById('arcaSearchBtn').disabled = false;
            document.getElementById('arcaError').textContent = 'Error de conexión.';
            document.getElementById('arcaError').style.display = '';
        });
}

function crearDesdeArca() {
    if (!ultimaPersona) return;
    const btn = document.getElementById('arcaSaveBtn');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Creando...';

    const form = new FormData();
    form.append('_csrf', document.getElementById('csrfToken').value);
    form.append('cuit', ultimaPersona.cuit);
    form.append('razon', ultimaPersona.razon || ultimaPersona.razonSocial || '');
    form.append('direc', ultimaPersona.direc || '');
    form.append('localidad', ultimaPersona.localidad || '');
    form.append('provincia', ultimaPersona.provincia || '');
    form.append('condicion_iva', ultimaPersona.condicion_iva || 'consumidor_final');

    fetch('/admin/clientes/crear-desde-arca', { method: 'POST', body: form })
        .then(r => r.json())
        .then(res => {
            if (res.ok) {
                window.location.href = '/admin/clientes/' + res.user_id;
            } else {
                alert(res.error || 'Error al crear cliente');
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-person-plus"></i> Crear cliente';
            }
        })
        .catch(() => {
            alert('Error de conexión');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-person-plus"></i> Crear cliente';
        });
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
