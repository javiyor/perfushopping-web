<?php
$empresa = $empresa ?? null;
$tiposIva = $tiposIva ?? [];
$sucursales = $sucursales ?? [];
$depositos = $depositos ?? [];
if (!$empresa):
?>
    <div class="alert alert-warning">Empresa no encontrada.</div>
<?php return; endif;
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item active">Datos de la empresa</li>
    </ol>
</nav>

<form method="post" action="/admin/empresa/guardar" enctype="multipart/form-data">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
    <input type="hidden" name="id" value="<?= (int)($empresa['idempre'] ?? 0) ?>" />

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Identificación</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Nombre de fantasía</label>
                            <input class="form-control form-control-sm" name="nomemp" value="<?= htmlspecialchars((string)($empresa['nomemp'] ?? '')) ?>" />
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Razón social</label>
                            <input class="form-control form-control-sm" name="razon_emp" value="<?= htmlspecialchars((string)($empresa['razon_emp'] ?? '')) ?>" />
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">CUIT</label>
                            <input class="form-control form-control-sm" name="cuit" value="<?= htmlspecialchars((string)($empresa['cuit'] ?? '')) ?>" />
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Ingresos Brutos</label>
                            <input class="form-control form-control-sm" name="ing_brutos" value="<?= htmlspecialchars((string)($empresa['ing_brutos'] ?? '')) ?>" />
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Condición IVA</label>
                            <select class="form-select form-select-sm" name="codtip">
                                <option value="">— Seleccionar —</option>
                                <?php foreach ($tiposIva as $t): ?>
                                <option value="<?= (int)$t['codtipiva'] ?>" <?= (int)($empresa['codtip'] ?? 0) === (int)$t['codtipiva'] ? 'selected' : '' ?>><?= htmlspecialchars($t['tipiva'] ?? '') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Sitio web</label>
                            <input class="form-control form-control-sm" name="web" value="<?= htmlspecialchars((string)($empresa['web'] ?? '')) ?>" placeholder="https://www.perfushoping.com" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Contacto</div>
                <div class="card-body">
                    <div class="row g-2">
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Dirección</label>
                            <input class="form-control form-control-sm" name="dire_emp" value="<?= htmlspecialchars((string)($empresa['dire_emp'] ?? '')) ?>" />
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Teléfono</label>
                            <input class="form-control form-control-sm" name="telefono" value="<?= htmlspecialchars((string)($empresa['telefono'] ?? '')) ?>" />
                        </div>
                        <div class="col-md-6 mb-2">
                            <label class="form-label small">Email</label>
                            <input class="form-control form-control-sm" name="mail" value="<?= htmlspecialchars((string)($empresa['mail'] ?? '')) ?>" />
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Logo</div>
                <div class="card-body text-center">
                    <?php if ($empresa['logo'] ?? ''): ?>
                        <img src="<?= htmlspecialchars(\Perfushopping\Web\Support\Format::uploadUrl((string)$empresa['logo'])) ?>" style="max-width:100%;max-height:120px;border-radius:8px;margin-bottom:8px" alt="Logo" />
                        <div class="mb-2">
                            <button class="btn btn-sm btn-outline-danger" type="button" onclick="if(confirm('Eliminar logo?'))document.getElementById('removeLogoForm').submit()">
                                <i class="bi bi-trash"></i> Eliminar
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="text-muted" style="font-size:60px;margin-bottom:8px"><i class="bi bi-image"></i></div>
                        <p class="small text-muted">Sin logo</p>
                    <?php endif; ?>
                    <input class="form-control form-control-sm" type="file" name="logo" accept="image/png,image/jpeg,image/gif,image/webp" />
                </div>
            </div>
        </div>
    </div>

    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Guardar empresa</button>
    <a class="btn btn-outline-secondary" href="/admin">Cancelar</a>
</form>

<form id="removeLogoForm" method="post" action="/admin/empresa/logo/eliminar" style="display:none">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
</form>

<hr class="my-4" />

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="fw-bold mb-0"><i class="bi bi-building"></i> Sucursales</h5>
    <button class="btn btn-accent btn-sm" onclick="abrirModalSuc(null)"><i class="bi bi-plus-lg"></i> Nueva</button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Dirección</th>
                    <th>Teléfono</th>
                    <th>Email</th>
                    <th>Pto. Venta</th>
                    <th>Depósito</th>
                    <th>Activo</th>
                    <th style="width:50px"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sucursales as $s): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($s['nomsuc'] ?? '')) ?></td>
                    <td class="small text-muted"><?= htmlspecialchars((string)($s['direccion'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($s['telefono'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($s['email'] ?? '')) ?></td>
                    <td><?= (int)($s['punto_venta'] ?? 0) ?></td>
                    <td class="small">
                        <?php
                        $depoId = (int)($s['iddepo'] ?? 0);
                        $depoNombre = '';
                        foreach ($depositos as $d) {
                            if ((int)$d['iddepo'] === $depoId) { $depoNombre = $d['nomdepo']; break; }
                        }
                        echo htmlspecialchars($depoNombre ?: '—');
                        ?>
                    </td>
                    <td><?= !empty($s['activo']) ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
                    <td><button class="btn btn-sm btn-outline-secondary" onclick="abrirModalSuc(<?= (int)$s['id'] ?>)"><i class="bi bi-pencil"></i></button></td>
                </tr>
                <?php endforeach; ?>
                <?php if (!$sucursales): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No hay sucursales.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Sucursal Modal -->
<div class="modal fade" id="modalSucursal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="/admin/sucursales/save" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" id="sucId" value="" />
            <div class="modal-header">
                <h5 class="modal-title" id="sucModalTitle">Nueva sucursal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Nombre</label>
                    <input type="text" name="nomsuc" id="sucNomsuc" class="form-control form-control-sm" required />
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label small">Dirección</label>
                        <input type="text" name="direccion" id="sucDireccion" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Teléfono</label>
                        <input type="text" name="telefono" id="sucTelefono" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Email</label>
                        <input type="text" name="email" id="sucEmail" class="form-control form-control-sm" />
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-md-4">
                        <label class="form-label small">Núm. Sucursal</label>
                        <input type="text" name="numsuc" id="sucNumsuc" class="form-control form-control-sm" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Punto de venta</label>
                        <input type="number" name="punto_venta" id="sucPuntoVenta" class="form-control form-control-sm" value="1" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Depósito</label>
                        <select name="iddepo" id="sucIddepo" class="form-select form-select-sm">
                            <option value="">—</option>
                            <?php foreach ($depositos as $d): ?>
                            <option value="<?= (int)$d['iddepo'] ?>"><?= htmlspecialchars((string)($d['nomdepo'] ?? '')) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="activo" id="sucActivo" class="form-check-input" value="1" checked />
                    <label class="form-check-label small" for="sucActivo">Activa</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent btn-sm">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
const sucursalesData = <?= json_encode(array_map(function($s) {
    return [
        'id' => (int)$s['id'],
        'nomsuc' => $s['nomsuc'] ?? '',
        'direccion' => $s['direccion'] ?? '',
        'telefono' => $s['telefono'] ?? '',
        'email' => $s['email'] ?? '',
        'numsuc' => $s['numsuc'] ?? '',
        'punto_venta' => (int)($s['punto_venta'] ?? 0),
        'iddepo' => (int)($s['iddepo'] ?? 0),
        'activo' => !empty($s['activo']),
    ];
}, $sucursales)) ?>;

function abrirModalSuc(id) {
    const modal = new bootstrap.Modal(document.getElementById('modalSucursal'));
    document.getElementById('sucId').value = '';
    document.getElementById('sucNomsuc').value = '';
    document.getElementById('sucDireccion').value = '';
    document.getElementById('sucTelefono').value = '';
    document.getElementById('sucEmail').value = '';
    document.getElementById('sucNumsuc').value = '';
    document.getElementById('sucPuntoVenta').value = '1';
    document.getElementById('sucIddepo').value = '';
    document.getElementById('sucActivo').checked = true;
    document.getElementById('sucModalTitle').textContent = 'Nueva sucursal';

    if (id) {
        const s = sucursalesData.find(x => x.id === id);
        if (s) {
            document.getElementById('sucId').value = s.id;
            document.getElementById('sucNomsuc').value = s.nomsuc;
            document.getElementById('sucDireccion').value = s.direccion;
            document.getElementById('sucTelefono').value = s.telefono;
            document.getElementById('sucEmail').value = s.email;
            document.getElementById('sucNumsuc').value = s.numsuc;
            document.getElementById('sucPuntoVenta').value = s.punto_venta;
            document.getElementById('sucIddepo').value = s.iddepo || '';
            document.getElementById('sucActivo').checked = s.activo;
            document.getElementById('sucModalTitle').textContent = 'Editar sucursal';
        }
    }
    modal.show();
}
</script>
