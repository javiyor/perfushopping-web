<?php
$config = $config ?? ['modo' => 'auto', 'codrub' => null, 'codsub' => null];
$manual = $manual ?? [];
$rubros = $rubros ?? [];
$marcas = $marcas ?? [];
$q = $q ?? '';
$searchResults = $searchResults ?? [];
$tema = $tema ?? \Perfushopping\Web\Repo\PortadaRepo::tema($config);
$plantillas = $plantillas ?? \Perfushopping\Web\Repo\PortadaRepo::plantillas();
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
$bannerSub = trim((string)($config['banner_sub'] ?? ''));
$bannerSubBold = !empty($config['banner_sub_bold']);
$bannerSubItalic = !empty($config['banner_sub_italic']);
$bannerSubSize = in_array($config['banner_sub_size'] ?? '', ['chico', 'normal', 'grande'], true) ? (string)$config['banner_sub_size'] : 'normal';
$bannerSubColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($config['banner_sub_color'] ?? '')) ? (string)$config['banner_sub_color'] : '#FFFFFF';
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

            <div class="border rounded p-3 mb-3">
                <h6 class="fw-bold mb-2"><i class="bi bi-palette"></i> Diseño de la tienda</h6>
                <p class="text-muted small mb-2">Elegí una plantilla visual y ajustá los colores de fondo, tarjetas, texto y acento.</p>
                <div class="row g-2 mb-3">
                    <?php foreach ($plantillas as $key => $preset): ?>
                        <div class="col-6 col-md-3">
                            <label class="border rounded p-2 d-block h-100" style="cursor:pointer;background:<?= htmlspecialchars($preset['color_fondo']) ?>;color:<?= htmlspecialchars($preset['color_texto']) ?>">
                                <input class="form-check-input me-1 plantilla-radio" type="radio" name="plantilla" value="<?= htmlspecialchars($key) ?>" data-colors="<?= htmlspecialchars(json_encode($preset, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>" <?= ($tema['plantilla'] ?? 'elegante') === $key ? 'checked' : '' ?> />
                                <strong class="small"><?= htmlspecialchars($preset['label']) ?></strong>
                                <div class="mt-2 rounded" style="height:24px;background:<?= htmlspecialchars($preset['color_tarjeta']) ?>;border:1px solid <?= htmlspecialchars($preset['color_acento']) ?>"></div>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="row g-2 align-items-end">
                    <?php foreach ([
                        'color_fondo' => 'Fondo principal',
                        'color_fondo_secundario' => 'Fondo secundario',
                        'color_tarjeta' => 'Fondo tarjetas',
                        'color_acento' => 'Color de acento',
                        'color_texto' => 'Color de texto',
                    ] as $field => $label): ?>
                        <div class="col-6 col-md">
                            <label class="form-label small mb-1"><?= htmlspecialchars($label) ?></label>
                            <input class="form-control form-control-sm tema-color" type="color" name="<?= htmlspecialchars($field) ?>" id="<?= htmlspecialchars($field) ?>" value="<?= htmlspecialchars($tema[$field] ?? '#000000') ?>" />
                        </div>
                    <?php endforeach; ?>
                    <div class="col-12 mt-3"><h6 class="fw-bold mb-2"><i class="bi bi-type"></i> Letras de las tarjetas de productos</h6></div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Tipografía</label>
                        <select class="form-select form-select-sm product-type-control" name="fuente_tarjeta" id="fuente_tarjeta">
                            <option value="system" <?= ($tema['fuente_tarjeta'] ?? 'system') === 'system' ? 'selected' : '' ?>>Moderna</option>
                            <option value="serif" <?= ($tema['fuente_tarjeta'] ?? '') === 'serif' ? 'selected' : '' ?>>Clásica</option>
                            <option value="rounded" <?= ($tema['fuente_tarjeta'] ?? '') === 'rounded' ? 'selected' : '' ?>>Redondeada</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Tamaño nombre</label>
                        <select class="form-select form-select-sm product-type-control" name="tamano_nombre_producto" id="tamano_nombre_producto">
                            <?php foreach ([12, 13, 14, 15, 16, 18, 20] as $size): ?><option value="<?= $size ?>" <?= (int)($tema['tamano_nombre_producto'] ?? 14) === $size ? 'selected' : '' ?>><?= $size ?> px</option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Color nombre</label>
                        <input class="form-control form-control-sm product-type-control" type="color" name="color_nombre_producto" id="color_nombre_producto" value="<?= htmlspecialchars($tema['color_nombre_producto'] ?? '#F6F4EF') ?>" />
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Tamaño precio</label>
                        <select class="form-select form-select-sm product-type-control" name="tamano_precio_producto" id="tamano_precio_producto">
                            <?php foreach ([14, 16, 18, 20, 22, 24, 26] as $size): ?><option value="<?= $size ?>" <?= (int)($tema['tamano_precio_producto'] ?? 18) === $size ? 'selected' : '' ?>><?= $size ?> px</option><?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Color precio</label>
                        <input class="form-control form-control-sm product-type-control" type="color" name="color_precio_producto" id="color_precio_producto" value="<?= htmlspecialchars($tema['color_precio_producto'] ?? '#D8B25A') ?>" />
                    </div>
                    <div class="col-12">
                        <div id="portadaThemePreview" class="rounded p-3" style="background:<?= htmlspecialchars($tema['color_fondo'] ?? '#050505') ?>;color:<?= htmlspecialchars($tema['color_texto'] ?? '#F6F4EF') ?>">
                            <div class="rounded p-3" style="background:<?= htmlspecialchars($tema['color_tarjeta'] ?? '#171411') ?>;border-left:4px solid <?= htmlspecialchars($tema['color_acento'] ?? '#D8B25A') ?>">
                                <strong>Vista previa de la plantilla</strong><div class="small mt-1">Fondo, tarjeta y color de acento</div>
                                <div id="productTypographyPreview" class="mt-2 p-2 rounded" style="max-width:300px;background:rgba(127,127,127,.12)">
                                    <div id="previewProductName">Nombre del producto</div>
                                    <strong id="previewProductPrice" style="display:block;margin-top:4px">$ 25.990</strong>
                                </div>
                            </div>
                        </div>
                    </div>
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
                <input class="form-control form-control-sm mb-2" name="banner_sub" id="bannerSub" maxlength="160" value="<?= htmlspecialchars($bannerSub) ?>" placeholder="Subtítulo (opcional). Ej: Todo para regalarle" oninput="updateBannerPreview()" />
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-4">
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="banner_sub_bold" id="bannerSubBold" value="1" <?= $bannerSubBold ? 'checked' : '' ?> onchange="updateBannerPreview()" />
                            <label class="form-check-label small fw-bold" for="bannerSubBold">Negrita</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="banner_sub_italic" id="bannerSubItalic" value="1" <?= $bannerSubItalic ? 'checked' : '' ?> onchange="updateBannerPreview()" />
                            <label class="form-check-label small fst-italic" for="bannerSubItalic">Cursiva</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Tamaño subtítulo</label>
                        <select class="form-select form-select-sm" name="banner_sub_size" id="bannerSubSize" onchange="updateBannerPreview()">
                            <?php foreach (['chico' => 'Chico', 'normal' => 'Normal', 'grande' => 'Grande'] as $v => $l): ?>
                            <option value="<?= $v ?>" <?= $bannerSubSize === $v ? 'selected' : '' ?>><?= $l ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small mb-1">Color subtítulo</label>
                        <div class="d-flex align-items-center gap-1 flex-wrap">
                            <?php foreach ($presetTexto as $hex => $nom): ?>
                            <button type="button" title="<?= $nom ?>" onclick="setBannerSubColor('<?= $hex ?>')" style="width:22px;height:22px;border-radius:50%;border:2px solid #ccc;background:<?= $hex ?>;padding:0"></button>
                            <?php endforeach; ?>
                            <input type="color" name="banner_sub_color" id="bannerSubColor" value="<?= htmlspecialchars($bannerSubColor) ?>" title="Personalizado" style="width:30px;height:24px;padding:0;border:none" oninput="updateBannerPreview()" />
                        </div>
                    </div>
                </div>
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
                    <div class="p-2 rounded text-center" style="background:#222" id="bannerPreviewWrap"><div id="bannerPreview"></div><div id="bannerPreviewSub"></div></div>
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
function actualizarPreviewTema() {
    const wrap = document.getElementById('portadaThemePreview');
    if (!wrap) return;
    const fondo = document.getElementById('color_fondo').value;
    const tarjeta = document.getElementById('color_tarjeta').value;
    const acento = document.getElementById('color_acento').value;
    const texto = document.getElementById('color_texto').value;
    wrap.style.background = fondo;
    wrap.style.color = texto;
    const sample = wrap.firstElementChild;
    if (sample) {
        sample.style.background = tarjeta;
        sample.style.borderLeftColor = acento;
    }
    actualizarPreviewLetras();
}
function actualizarPreviewLetras() {
    const name = document.getElementById('previewProductName');
    const price = document.getElementById('previewProductPrice');
    if (!name || !price) return;
    const fonts = {
        system: 'ui-sans-serif,system-ui,-apple-system,"Segoe UI",Arial,sans-serif',
        serif: 'Georgia,"Times New Roman",serif',
        rounded: '"Trebuchet MS",Arial,sans-serif'
    };
    const font = document.getElementById('fuente_tarjeta').value;
    name.style.fontFamily = price.style.fontFamily = fonts[font] || fonts.system;
    name.style.fontSize = document.getElementById('tamano_nombre_producto').value + 'px';
    name.style.color = document.getElementById('color_nombre_producto').value;
    price.style.fontSize = document.getElementById('tamano_precio_producto').value + 'px';
    price.style.color = document.getElementById('color_precio_producto').value;
}
function aplicarPlantilla(radio) {
    if (!radio || !radio.checked) return;
    try {
        const preset = JSON.parse(radio.dataset.colors || '{}');
        ['color_fondo','color_fondo_secundario','color_tarjeta','color_acento','color_texto'].forEach(function(key) {
            const field = document.getElementById(key);
            if (field && preset[key]) field.value = preset[key];
        });
    } catch (e) {}
    actualizarPreviewTema();
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
function setBannerSubColor(hex) {
    document.getElementById('bannerSubColor').value = hex;
    updateBannerPreview();
}
function updateBannerPreview() {
    const txt = document.getElementById('bannerText').value.trim() || 'Texto destacado';
    const sub = document.getElementById('bannerSub').value.trim();
    const prev = document.getElementById('bannerPreview');
    const prevSub = document.getElementById('bannerPreviewSub');
    prev.textContent = txt;
    prev.style.fontWeight = document.getElementById('bannerBold').checked ? 'bold' : 'normal';
    prev.style.fontStyle = document.getElementById('bannerItalic').checked ? 'italic' : 'normal';
    const sizes = {chico: '1.1rem', normal: '1.4rem', grande: '1.8rem'};
    prev.style.fontSize = sizes[document.getElementById('bannerSize').value] || '1.8rem';
    prev.style.color = document.getElementById('bannerColor').value;
    prevSub.textContent = sub;
    prevSub.style.display = sub !== '' ? '' : 'none';
    prevSub.style.fontWeight = document.getElementById('bannerSubBold').checked ? 'bold' : 'normal';
    prevSub.style.fontStyle = document.getElementById('bannerSubItalic').checked ? 'italic' : 'normal';
    prevSub.style.fontSize = sizes[document.getElementById('bannerSubSize').value] || '1.4rem';
    prevSub.style.color = document.getElementById('bannerSubColor').value;
    prevSub.style.marginTop = '2px';
    const wrap = document.getElementById('bannerPreviewWrap');
    if (document.getElementById('bannerBgTransp').checked) {
        wrap.style.background = '#222';
        wrap.style.padding = '8px';
        wrap.style.borderRadius = '4px';
    } else {
        wrap.style.background = document.getElementById('bannerBg').value;
        wrap.style.padding = '6px 18px';
        wrap.style.borderRadius = '10px';
    }
    [prev, prevSub].forEach(el => {
        el.style.fontFamily = "Georgia, 'Times New Roman', serif";
        el.style.letterSpacing = '0.7px';
    });
}
document.addEventListener('DOMContentLoaded', updateBannerPreview);
document.querySelectorAll('.plantilla-radio').forEach(function(radio) {
    radio.addEventListener('change', function() { aplicarPlantilla(this); });
});
document.querySelectorAll('.tema-color').forEach(function(input) {
    input.addEventListener('input', actualizarPreviewTema);
});
document.querySelectorAll('.product-type-control').forEach(function(input) {
    input.addEventListener('input', actualizarPreviewLetras);
    input.addEventListener('change', actualizarPreviewLetras);
});
actualizarPreviewTema();
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
