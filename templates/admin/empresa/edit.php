<?php
$empresa = $empresa ?? null;
$tiposIva = $tiposIva ?? [];
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

    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
    <a class="btn btn-outline-secondary" href="/admin">Cancelar</a>
</form>

<form id="removeLogoForm" method="post" action="/admin/empresa/logo/eliminar" style="display:none">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
</form>
