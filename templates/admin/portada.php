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
$bannerText = trim((string)($config['banner_text'] ?? ''));
$bannerBold = !empty($config['banner_bold']);
$bannerItalic = !empty($config['banner_italic']);
$bannerSize = in_array($config['banner_size'] ?? '', ['chico', 'normal', 'grande'], true) ? (string)$config['banner_size'] : 'grande';
$bannerColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($config['banner_color'] ?? '')) ? (string)$config['banner_color'] : '#C9A227';
$bannerBgRaw = (string)($config['banner_bg'] ?? '');
$bannerBg = preg_match('/^#[0-9a-fA-F]{6}$/', $bannerBgRaw) ? $bannerBgRaw : '';
$bannerBgTransp = $bannerBg === '';
$presetTexto = ['#C9A227' => 'Dorado', '#FFFFFF' => 'Blanco', '#1a1a1a' => 'Negro', '#C0392B' => 'Rojo', '#E84393' => 'Rosa', '#2E86C1' => 'Celeste'];
$presetFondo = ['#1a1a1a' => 'Negro', '#C9A227' => 'Dorado', '#C0392B' => 'Rojo', '#7B241C' => 'Bordó', '#E84393' => 'Rosa', '#FFFFFF' => 'Blanco'];
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

            <div class="mt-3 p-3 border rounded" id="fieldBanner" style="<?= $modo === 'manual' ? '' : 'display:none' ?>">
                <label class="form-label fw-semibold small">Texto destacado (fechas especiales) <span class="text-muted fw-normal">— se muestra en la portada pública en lugar de "Destacados"</span></label>
                <input class="form-control form-control-sm mb-2" name="banner_text" id="bannerText" maxlength="120" value="<?= htmlspecialchars($bannerText) ?>" placeholder="Ej: Para mamá" oninput="updateBannerPreview()" />
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="banner_bold" id="bannerBold" value="1" <?= $bannerBold ? 'checked' : '' ?> onchange="updateBannerPreview()" />
                            <label class="form-check-label small fw-bold" for="bannerBold">Negrita</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="banner_italic" id="bannerItalic" value="1" <?= $bannerItalic ? 'checked' : '' ?> onchange="updateBannerPreview()" />
                            <label class="form-check-label small fst-italic" for="bannerItalic">Cursiva</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Tamaño</label>
                        <select class="form-select form-select-sm" name="banner_size" id="bannerSize" onchange="updateBannerPreview()">
                            <?php foreach (['chico' => 'Chico', 'normal' => 'Normal', 'grande' => 'Grande'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $bannerSize === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Color del texto</label>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <?php foreach ($presetTexto as $hex => $nom): ?>
                            <button type="button" title="<?= $nom ?>" onclick="setBannerColor('<?= $hex ?>')" style="width:22px;height:22px;border-radius:50%;border:2px solid #ccc;background:<?= $hex ?>;padding:0"></button>
                            <?php endforeach; ?>
                            <input type="color" name="banner_color" id="bannerColor" value="<?= htmlspecialchars($bannerColor) ?>" title="Personalizado" style="width:30px;height:24px;padding:0;border:none" oninput="updateBannerPreview()" />
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small mb-1">Fondo</label>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <?php foreach ($presetFondo as $hex => $nom): ?>
                            <button type="button" title="<?= $nom ?>" onclick="setBannerBg('<?= $hex ?>')" style="width:22px;height:22px;border-radius:50%;border:2px solid #ccc;background:<?= $hex ?>;padding:0"></button>
                            <?php endforeach; ?>
                            <input type="color" name="banner_bg" id="bannerBg" value="<?= $bannerBg !== '' ? htmlspecialchars($bannerBg) : '#1a1a1a' ?>" title="Personalizado" style="width:30px;height:24px;padding:0;border:none" oninput="document.getElementById('bannerBgTransp').checked=false;updateBannerPreview()" />
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="checkbox" name="banner_bg_transp" id="bannerBgTransp" value="1" <?= $bannerBgTransp ? 'checked' : '' ?> onchange="updateBannerPreview()" />
                            <label class="form-check-label small" for="bannerBgTransp">Sin fondo</label>
                        </div>
                    </div>
                </div>
                <div class="mt-2">
                    <div class="small text-muted mb-1">Vista previa:</div>
                    <div class="p-2 rounded text-center" style="background:#222" id="bannerPreviewWrap"><span id="bannerPreview"></span></div>
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
    document.getElementById('fieldBanner').style.display = (modo === 'manual') ? '' : 'none';
}
function setBannerColor(hex) {
    document.getElementById('bannerColor').value = hex;
    updateBannerPreview();
}
function setBannerBg(hex) {
    document.getElementById('bannerBg').value = hex;
    document.getElementById('bannerBgTransp').checked = false;
    updateBannerPreview();
}
function updateBannerPreview() {
    const txt = document.getElementById('bannerText').value.trim() || 'Texto destacado';
    const prev = document.getElementById('bannerPreview');
    prev.textContent = txt;
    prev.style.fontWeight = document.getElementById('bannerBold').checked ? 'bold' : 'normal';
    prev.style.fontStyle = document.getElementById('bannerItalic').checked ? 'italic' : 'normal';
    const sizes = {chico: '1.1rem', normal: '1.4rem', grande: '1.8rem'};
    prev.style.fontSize = sizes[document.getElementById('bannerSize').value] || '1.8rem';
    prev.style.color = document.getElementById('bannerColor').value;
    const wrap = document.getElementById('bannerPreviewWrap');
    if (document.getElementById('bannerBgTransp').checked) {
        wrap.style.background = '#222';
        prev.style.background = 'transparent';
        prev.style.padding = '0';
        prev.style.borderRadius = '0';
    } else {
        wrap.style.background = '#222';
        prev.style.background = document.getElementById('bannerBg').value;
        prev.style.padding = '4px 18px';
        prev.style.borderRadius = '8px';
    }
    prev.style.fontFamily = "Georgia, 'Times New Roman', serif";
    prev.style.letterSpacing = '0.7px';
}
document.addEventListener('DOMContentLoaded', updateBannerPreview);
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
