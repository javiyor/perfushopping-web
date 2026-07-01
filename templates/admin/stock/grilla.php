<?php
use Perfushopping\Web\Support\Format;

$productos = $productos ?? [];
$rubros = $rubros ?? [];
$subrubros = $subrubros ?? [];
$proveedores = $proveedores ?? [];
$q = (string)($q ?? '');
$codrub = (int)($codrub ?? 0);
$codsub = (int)($codsub ?? 0);
$codprove = (int)($codprove ?? 0);
$desde = (string)($desde ?? '');
$hasta = (string)($hasta ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Grilla de reposición</h4>
        <p class="text-muted small">Consultá stock, ventas y generá pedidos de compra</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/stock">Volver a Stock</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold py-2">
        <a class="text-decoration-none" data-bs-toggle="collapse" href="#filtrosCollapse" role="button">
            <i class="bi bi-funnel"></i> Filtros
        </a>
    </div>
    <div class="collapse<?= $q !== '' || $codrub > 0 || $codsub > 0 || $codprove > 0 || $desde !== '' || $hasta !== '' ? ' show' : '' ?>" id="filtrosCollapse">
        <div class="card-body">
            <form method="get" action="/admin/stock/grilla" class="row g-2">
                <div class="col-md-3">
                    <label class="small">Rubro</label>
                    <select class="form-select form-select-sm" name="codrub" id="filtroRubro">
                        <option value="">Todos</option>
                        <?php foreach ($rubros as $r): ?>
                            <option value="<?= (int)$r['codrub'] ?>" <?= $codrub === (int)$r['codrub'] ? 'selected' : '' ?>><?= htmlspecialchars($r['nomrub'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Subrubro</label>
                    <select class="form-select form-select-sm" name="codsub" id="filtroSubrubro">
                        <option value="">Todos</option>
                        <?php foreach ($subrubros as $s): ?>
                            <option value="<?= (int)$s['codsub'] ?>" data-rubro="<?= (int)($s['codrub'] ?? 0) ?>" <?= $codsub === (int)$s['codsub'] ? 'selected' : '' ?>><?= htmlspecialchars($s['nomsub'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Proveedor</label>
                    <select class="form-select form-select-sm" name="codprove">
                        <option value="">Todos</option>
                        <?php foreach ($proveedores as $pv): ?>
                            <option value="<?= (int)$pv['codprove'] ?>" <?= $codprove === (int)$pv['codprove'] ? 'selected' : '' ?>><?= htmlspecialchars($pv['nomprovee'] ?? '') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="small">Búsqueda</label>
                    <input class="form-control form-control-sm" name="q" placeholder="Código, descripción, barra..." value="<?= htmlspecialchars($q) ?>" />
                </div>
                <div class="col-md-2">
                    <label class="small">Ventas desde</label>
                    <input class="form-control form-control-sm" name="desde" type="date" value="<?= htmlspecialchars($desde) ?>" />
                </div>
                <div class="col-md-2">
                    <label class="small">Ventas hasta</label>
                    <input class="form-control form-control-sm" name="hasta" type="date" value="<?= htmlspecialchars($hasta) ?>" />
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1">
                    <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Buscar</button>
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/stock/grilla">Limpiar</a>
                </div>
            </form>
        </div>
    </div>
</div>

<form method="post" action="/admin/stock/grilla/generar-oc" id="ocForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

    <div class="d-flex justify-content-between align-items-center mb-2">
        <span class="text-muted small"><?= count($productos) ?> producto(s) — Ingresá las cantidades a pedir</span>
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width:200px">
                <span class="input-group-text">Fecha est.</span>
                <input class="form-control" name="fecha_estimada" type="date" value="<?= date('Y-m-d', strtotime('+7 days')) ?>" />
            </div>
            <button class="btn btn-accent btn-sm" type="submit" id="btnGenerarOC" disabled>
                <i class="bi bi-cart-plus"></i> Generar OC
            </button>
        </div>
    </div>

    <?php if (!$productos): ?>
        <div class="card shadow-sm">
            <div class="card-body text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size:48px"></i>
                <p class="mt-2">Sin resultados. Ajustá los filtros o la búsqueda.</p>
            </div>
        </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" id="grillaTable">
            <thead class="table-light">
                <tr>
                    <th style="width:30px"><input type="checkbox" id="checkAll" checked /></th>
                    <th>Código</th>
                    <th>Descripción</th>
                    <th>Cod. proveedor</th>
                    <th>Cód. barra</th>
                    <th class="text-end">Stock</th>
                    <th class="text-end">Vendidos</th>
                    <th class="text-end">Costo</th>
                    <th style="width:100px" class="text-end">A pedir</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($productos as $p): ?>
                <tr>
                    <td><input type="checkbox" name="productos[]" value="<?= (int)$p['idprodu'] ?>" class="rowCheck" checked /></td>
                    <td class="small"><?= htmlspecialchars((string)($p['codprodu'] ?? '')) ?></td>
                    <td>
                        <span class="fw-semibold small"><?= htmlspecialchars(mb_substr((string)($p['produ'] ?? ''), 0, 60)) ?></span>
                        <?php if (($p['nomprovee'] ?? '') !== ''): ?>
                            <div class="text-muted" style="font-size:10px"><?= htmlspecialchars($p['nomprovee']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars((string)($p['codprodup'] ?? '')) ?></td>
                    <td class="small"><?= htmlspecialchars((string)($p['codscan'] ?? '')) ?></td>
                    <td class="text-end">
                        <span class="badge bg-<?= (int)($p['stocact'] ?? 0) > 10 ? 'success' : ((int)($p['stocact'] ?? 0) > 0 ? 'warning' : 'danger') ?>">
                            <?= (int)($p['stocact'] ?? 0) ?>
                        </span>
                    </td>
                    <td class="text-end small"><?= (int)($p['vendidos'] ?? 0) ?></td>
                    <td class="text-end small"><?= Format::moneyFromCents((int)($p['precomp'] ?? 0)) ?></td>
                    <td>
                        <input type="number" name="cantidad[<?= (int)$p['idprodu'] ?>]" class="form-control form-control-sm text-end cant-input" value="0" min="0" step="1" style="width:90px" data-id="<?= (int)$p['idprodu'] ?>" />
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</form>

<script>
// ── Check all toggle ──
document.getElementById('checkAll').addEventListener('change', function() {
    document.querySelectorAll('.rowCheck').forEach(cb => cb.checked = this.checked);
    toggleGenerateBtn();
});

document.querySelectorAll('.rowCheck').forEach(cb => {
    cb.addEventListener('change', toggleGenerateBtn);
});

// ── Enable/disable generate button ──
function toggleGenerateBtn() {
    const hasChecked = document.querySelectorAll('.rowCheck:checked').length > 0;
    const hasQty = document.querySelectorAll('.cant-input').length > 0;
    document.getElementById('btnGenerarOC').disabled = !hasChecked;
}

// ── Quantity auto-enable — ensure at least one qty > 0 triggers validation ──
document.querySelectorAll('.cant-input').forEach(inp => {
    inp.addEventListener('input', function() {
        const cb = document.querySelector('.rowCheck[value="' + this.dataset.id + '"]');
        if (cb && parseInt(this.value) > 0) cb.checked = true;
    });
});

// ── Subrubro filter by rubro ──
document.getElementById('filtroRubro')?.addEventListener('change', function() {
    const rubro = parseInt(this.value);
    document.querySelectorAll('#filtroSubrubro option').forEach(opt => {
        if (opt.value === '') return;
        opt.style.display = rubro === 0 || parseInt(opt.dataset.rubro) === rubro ? '' : 'none';
    });
    const sel = document.getElementById('filtroSubrubro');
    if (rubro > 0 && parseInt(sel.value) > 0) {
        const opt = sel.querySelector('option[value="' + sel.value + '"]');
        if (opt && opt.style.display === 'none') sel.value = '';
    }
});

// Trigger on load
document.getElementById('filtroRubro')?.dispatchEvent(new Event('change'));
toggleGenerateBtn();
</script>
