<?php
$config = $config ?? ['modo' => 'auto', 'codrub' => null, 'codsub' => null];
$manual = $manual ?? [];
$rubros = $rubros ?? [];
$marcas = $marcas ?? [];
$q = $q ?? '';
$searchResults = $searchResults ?? [];
$modo = (string)($config['modo'] ?? 'auto');
$codrub = (int)($config['codrub'] ?? 0);
$codsub = (int)($config['codsub'] ?? 0);
?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-easel"></i> Portada</h4>
        <p class="text-muted small mb-0">Elegí qué productos se muestran en la portada de la web pública (cuando no hay búsqueda ni filtro).</p>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="/" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver portada</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="post" action="/admin/portada/guardar">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="mb-3">
                <label class="form-label fw-semibold">Modo de portada</label>
                <div class="d-flex flex-wrap gap-3">
                    <?php
                    $modos = [
                        'auto' => 'Automático (Novedades — 80 productos con fecompra últimos 6 meses)',
                        'rubro' => 'Por rubro',
                        'marca' => 'Por marca',
                        'ultimos' => 'Últimos 100 cargados (idprodu más altos)',
                        'manual' => 'Manual — elijo productos puntuales',
                    ];
                    foreach ($modos as $k => $label):
                    ?>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="modo" value="<?= $k ?>" id="modo_<?= $k ?>" <?= $modo === $k ? 'checked' : '' ?> onchange="togglePortadaFields()" />
                        <label class="form-check-label small" for="modo_<?= $k ?>"><?= htmlspecialchars($label) ?></label>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="row g-3" id="portadaFields">
                <div class="col-md-6" id="fieldRubro" style="<?= $modo === 'rubro' ? '' : 'display:none' ?>">
                    <label class="form-label small">Rubro</label>
                    <select class="form-select form-select-sm" name="codrub">
                        <option value="0">— Elegir rubro —</option>
                        <?php foreach ($rubros as $r): ?>
                        <option value="<?= (int)$r['codrub'] ?>" <?= $codrub === (int)$r['codrub'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$r['nomrub']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-6" id="fieldMarca" style="<?= $modo === 'marca' ? '' : 'display:none' ?>">
                    <label class="form-label small">Marca</label>
                    <select class="form-select form-select-sm" name="codsub">
                        <option value="0">— Elegir marca —</option>
                        <?php foreach ($marcas as $m): ?>
                        <option value="<?= (int)$m['codsub'] ?>" <?= $codsub === (int)$m['codsub'] ? 'selected' : '' ?>><?= htmlspecialchars(trim((string)$m['nomsub'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar portada</button>
            </div>
        </form>
    </div>
</div>

<?php if ($modo === 'manual'): ?>
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Productos en portada (<?= count($manual) ?>)</span>
        <form method="post" action="/admin/portada/manual/vaciar" onsubmit="return confirm('Vaciar portada manual?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i> Vaciar</button>
        </form>
    </div>
    <div class="card-body p-0">
        <?php if (!$manual): ?>
            <div class="p-4 text-center text-muted small">Aún no hay productos seleccionados. Buscá abajo y agregalos.</div>
        <?php else: ?>
            <form method="post" action="/admin/portada/manual/orden" id="formOrden">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                <input type="hidden" name="orden" id="inputOrden" value="" />
                <div class="table-responsive">
                    <table class="table table-sm table-admin mb-0" id="tablaManual">
                        <thead><tr><th style="width:30px">#</th><th>Producto</th><th>Rubro / Marca</th><th style="width:80px"></th></tr></thead>
                        <tbody id="tbodyManual">
                            <?php foreach ($manual as $idx => $p): ?>
                            <tr data-idprodu="<?= (int)$p['idprodu'] ?>">
                                <td class="text-muted small handle" style="cursor:grab"><?= $idx + 1 ?></td>
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars((string)$p['produ']) ?></div>
                                    <div class="text-muted" style="font-size:11px">#<?= (int)$p['idprodu'] ?> · <?= $p['enweb'] ? '<span class="badge bg-success">en web</span>' : '<span class="badge bg-secondary">oculto</span>' ?></div>
                                </td>
                                <td class="small text-muted"><?= htmlspecialchars((string)($p['nomrub'] ?? '')) ?> · <?= htmlspecialchars(trim((string)($p['nomsub'] ?? ''))) ?></td>
                                <td>
                                    <form method="post" action="/admin/portada/manual/quitar" onsubmit="return confirm('Quitar de portada?')">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                        <input type="hidden" name="idprodu" value="<?= (int)$p['idprodu'] ?>" />
                                        <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-2 d-flex gap-2">
                    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="guardarOrden()"><i class="bi bi-arrow-down-up"></i> Guardar orden</button>
                    <span class="small text-muted align-self-center">Arrastrá las filas para ordenar (o usá ↑/↓) y guardá.</span>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Agregar productos a portada</div>
    <div class="card-body">
        <form method="get" action="/admin/portada" class="mb-3">
            <input type="hidden" name="modo" value="manual" />
            <div class="input-group input-group-sm">
                <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, código..." />
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>

        <?php if ($q !== ''): ?>
            <?php if (!$searchResults): ?>
                <div class="text-muted small">Sin resultados para "<?= htmlspecialchars($q) ?>".</div>
            <?php else: ?>
                <form method="post" action="/admin/portada/manual/agregar-varios">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <div class="table-responsive">
                        <table class="table table-sm table-admin">
                            <thead><tr><th style="width:30px"><input type="checkbox" id="chkAll" onchange="toggleAll(this)" /></th><th>Producto</th><th>Rubro</th></tr></thead>
                            <tbody>
                                <?php foreach ($searchResults as $it): ?>
                                <tr>
                                    <td><input type="checkbox" name="idprodu[]" value="<?= (int)$it['idprodu'] ?>" class="chkProd" /></td>
                                    <td>
                                        <div class="small fw-semibold"><?= htmlspecialchars((string)($it['produ'] ?? '')) ?></div>
                                        <div class="text-muted" style="font-size:11px">#<?= (int)$it['idprodu'] ?> · <?= htmlspecialchars((string)($it['codprodu'] ?? '')) ?></div>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($it['nomrub'] ?? $it['codrub'] ?? '')) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-plus-lg"></i> Agregar seleccionados</button>
                </form>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-muted small">Usá el buscador para encontrar productos y agregarlos con el check.</div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<script>
function togglePortadaFields() {
    const modo = document.querySelector('input[name="modo"]:checked')?.value || 'auto';
    document.getElementById('fieldRubro').style.display = (modo === 'rubro') ? '' : 'none';
    document.getElementById('fieldMarca').style.display = (modo === 'marca') ? '' : 'none';
}
function toggleAll(chk) {
    document.querySelectorAll('.chkProd').forEach(c => c.checked = chk.checked);
}
function guardarOrden() {
    const ids = Array.from(document.querySelectorAll('#tbodyManual tr')).map(tr => tr.dataset.idprodu).join(',');
    document.getElementById('inputOrden').value = ids;
    document.getElementById('formOrden').submit();
}
// Drag simple: swap rows on drag
(function(){
    const tbody = document.getElementById('tbodyManual');
    if (!tbody) return;
    let dragEl = null;
    tbody.querySelectorAll('tr').forEach(tr => {
        tr.draggable = true;
        tr.addEventListener('dragstart', e => { dragEl = tr; tr.style.opacity = '0.5'; });
        tr.addEventListener('dragend', e => { tr.style.opacity = ''; });
        tr.addEventListener('dragover', e => { e.preventDefault(); });
        tr.addEventListener('drop', e => {
            e.preventDefault();
            if (dragEl && dragEl !== tr) {
                const rows = Array.from(tbody.children);
                const from = rows.indexOf(dragEl), to = rows.indexOf(tr);
                if (from < to) tr.after(dragEl); else tr.before(dragEl);
                // renumerar
                Array.from(tbody.children).forEach((r,i)=> r.querySelector('.handle').textContent = i+1);
            }
        });
    });
})();
</script>
