<?php
$rubros = $rubros ?? [];
$subrubros = $subrubros ?? [];
$departamentos = $departamentos ?? [];
$proveedores = $proveedores ?? [];
$ivaOptions = $ivaOptions ?? [];
?>

<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/productos">Productos</a></li>
        <li class="breadcrumb-item active">Nuevo producto</li>
    </ol>
</nav>

<form method="post" action="/admin/productos/crear">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

    <div class="row g-3">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-3">
                <div class="card-header bg-white fw-semibold">Datos del producto</div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Nombre del producto <span class="text-danger">*</span></label>
                        <input class="form-control" name="produ" placeholder="Nombre del producto" required />
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Precio minorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio_gross" placeholder="0.00" inputmode="decimal" required />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Precio mayorista <span class="text-muted">(IVA incl.)</span></label>
                            <input class="form-control form-control-sm" name="precio1_gross" placeholder="0.00" inputmode="decimal" required />
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Categoría</label>
                            <select class="form-select form-select-sm" name="codrub">
                                <option value="">— Sin categoría —</option>
                                <?php foreach ($rubros as $rub): ?>
                                    <option value="<?= (int)($rub['codrub'] ?? 0) ?>"><?= htmlspecialchars((string)($rub['nomrub'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Marca / Subrubro</label>
                            <select class="form-select form-select-sm" name="codsub">
                                <option value="">— Sin marca —</option>
                                <?php foreach ($subrubros as $sub): ?>
                                    <option value="<?= (int)($sub['codsub'] ?? 0) ?>"><?= htmlspecialchars((string)($sub['nomsub'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small">Departamento</label>
                            <select class="form-select form-select-sm" name="codepar">
                                <option value="">— Sin departamento —</option>
                                <?php foreach ($departamentos as $dep): ?>
                                    <option value="<?= (int)($dep['codepar'] ?? 0) ?>"><?= htmlspecialchars((string)($dep['nomdepar'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small">Proveedor</label>
                            <select class="form-select form-select-sm" name="codprove">
                                <option value="">— Sin proveedor —</option>
                                <?php foreach ($proveedores as $prov): ?>
                                    <option value="<?= htmlspecialchars((string)($prov['codprove'] ?? '')) ?>"><?= htmlspecialchars((string)($prov['razon'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">IVA</label>
                        <select class="form-select form-select-sm" name="iva">
                            <?php foreach ($ivaOptions as $iva): ?>
                                <option value="<?= (int)($iva['codivaprodu'] ?? 0) ?>"><?= htmlspecialchars((string)($iva['tiva'] ?? '')) ?>%</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-white fw-semibold">Acciones</div>
                <div class="card-body">
                    <p class="small text-muted">Completá los datos básicos. Después podés agregar descripción, imágenes, variedades y datos logísticos desde la edición.</p>
                    <button class="btn btn-accent w-100" type="submit"><i class="bi bi-plus-lg"></i> Crear producto</button>
                    <a class="btn btn-outline-secondary w-100 mt-2" href="/admin/productos">Cancelar</a>
                </div>
            </div>
        </div>
    </div>
</form>
