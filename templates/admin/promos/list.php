<?php
$promos = $promos ?? [];
$banners = $banners ?? [];
$brands = $brands ?? [];
$q = (string)($q ?? '');
$searchResults = $searchResults ?? [];
$csrf = (string)($csrf ?? '');
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-percent"></i> Promociones web</h4>
        <p class="text-muted small mb-0">Precio normal tachado + precio promo en grande en el carousel de la portada, con botón directo al carrito.</p>
    </div>
    <a class="btn btn-sm btn-outline-secondary" href="/" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver portada</a>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Promos activas (<?= count($promos) ?>)</div>
    <div class="card-body p-0">
        <?php if (!$promos): ?>
            <div class="p-4 text-center text-muted small">Todavía no hay promos. Buscá abajo y agregalas.</div>
        <?php else: ?>
            <form method="post" action="/admin/promos/orden">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <div class="table-responsive">
                    <table class="table table-sm table-admin mb-0">
                        <thead><tr><th style="width:70px">Orden</th><th>Producto</th><th class="text-end">Normal</th><th class="text-end">Promo</th><th class="text-end">OFF</th><th>Estado</th><th style="width:90px"></th></tr></thead>
                        <tbody>
                            <?php foreach ($promos as $pr):
                                $tiva = (float)($pr['tiva'] ?? 0);
                                $normalNet = (float)($pr['precio'] ?? 0);
                                $promoNet = (float)($pr['promo_precio'] ?? 0);
                                $normalFin = $normalNet * (1 + $tiva / 100);
                                $promoFin = $promoNet * (1 + $tiva / 100);
                                $off = $normalNet > 0 ? (int)round((1 - $promoNet / $normalNet) * 100) : 0;
                            ?>
                            <tr class="<?= empty($pr['activo']) ? 'table-secondary' : '' ?>">
                                <td><input type="number" class="form-control form-control-sm" name="orden[<?= (int)$pr['idprodu'] ?>]" value="<?= (int)($pr['orden'] ?? 0) ?>" min="0" max="9999" style="width:65px" /></td>
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars((string)($pr['produ'] ?? '')) ?></div>
                                    <div class="text-muted" style="font-size:11px">#<?= (int)$pr['idprodu'] ?><?= empty($pr['enweb']) ? ' · <span class="badge bg-secondary">oculto web</span>' : '' ?></div>
                                </td>
                                <td class="text-end small text-muted"><s>$<?= number_format($normalFin, 0, ',', '.') ?></s></td>
                                <td class="text-end fw-bold">$<?= number_format($promoFin, 0, ',', '.') ?></td>
                                <td class="text-end"><span class="badge bg-danger">-<?= $off ?>%</span></td>
                                <td>
                                    <button class="btn btn-sm <?= !empty($pr['activo']) ? 'btn-success' : 'btn-outline-secondary' ?>" type="button" onclick="promoPost('/admin/promos/activo', <?= (int)$pr['idprodu'] ?>)"><?= !empty($pr['activo']) ? 'Activa' : 'Pausada' ?></button>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-outline-danger" type="button" onclick="if(confirm('Quitar promo?')) promoPost('/admin/promos/quitar', <?= (int)$pr['idprodu'] ?>)"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-2">
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-arrow-down-up"></i> Guardar orden</button>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>

<script>
function promoPost(action, id, field) {
    const f = document.createElement('form');
    f.method = 'post';
    f.action = action;
    const csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_csrf'; csrf.value = <?= json_encode($csrf) ?>;
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = field || 'idprodu'; inp.value = id;
    f.appendChild(csrf); f.appendChild(inp);
    document.body.appendChild(f); f.submit();
}
</script>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold">Banners de texto (sin producto) — van al carousel y enlazan a la marca (<?= count($banners) ?>)</div>
    <div class="card-body p-0">
        <?php if ($banners): ?>
            <form method="post" action="/admin/promos/banner/orden">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                <div class="table-responsive">
                    <table class="table table-sm table-admin mb-0">
                        <thead><tr><th style="width:70px">Orden</th><th>Banner</th><th>Destino</th><th>Estado</th><th style="width:90px"></th></tr></thead>
                        <tbody>
                            <?php foreach ($banners as $b): ?>
                            <tr class="<?= empty($b['activo']) ? 'table-secondary' : '' ?>">
                                <td><input type="number" class="form-control form-control-sm" name="orden[<?= (int)$b['id'] ?>]" value="<?= (int)($b['orden'] ?? 0) ?>" min="0" max="9999" style="width:65px" /></td>
                                <td>
                                    <div class="fw-semibold small"><?= htmlspecialchars((string)($b['titulo'] ?? '')) ?></div>
                                    <div class="text-muted" style="font-size:11px"><?= htmlspecialchars(mb_substr((string)($b['texto'] ?? ''), 0, 80)) ?></div>
                                </td>
                                <td class="small"><?= (int)($b['codsub'] ?? 0) > 0 ? 'Marca: ' . htmlspecialchars((string)($b['nomsub'] ?? '')) : htmlspecialchars((string)($b['link'] ?? '—')) ?></td>
                                <td>
                                    <button class="btn btn-sm <?= !empty($b['activo']) ? 'btn-success' : 'btn-outline-secondary' ?>" type="button" onclick="promoPost('/admin/promos/banner/activo', <?= (int)$b['id'] ?>, 'id')"><?= !empty($b['activo']) ? 'Activo' : 'Pausado' ?></button>
                                </td>
                                <td class="text-nowrap">
                                    <button class="btn btn-sm btn-outline-primary" type="button" title="Editar"
                                        data-id="<?= (int)$b['id'] ?>"
                                        data-titulo="<?= htmlspecialchars((string)($b['titulo'] ?? ''), ENT_QUOTES) ?>"
                                        data-texto="<?= htmlspecialchars((string)($b['texto'] ?? ''), ENT_QUOTES) ?>"
                                        data-codsub="<?= (int)($b['codsub'] ?? 0) ?>"
                                        data-link="<?= htmlspecialchars((string)($b['link'] ?? ''), ENT_QUOTES) ?>"
                                        data-color="<?= htmlspecialchars((string)($b['color'] ?? '#FFFFFF'), ENT_QUOTES) ?>"
                                        data-bg="<?= htmlspecialchars((string)($b['bg'] ?? ''), ENT_QUOTES) ?>"
                                        onclick="editarBanner(this)"><i class="bi bi-pencil"></i></button>
                                    <button class="btn btn-sm btn-outline-danger" type="button" onclick="if(confirm('Quitar banner?')) promoPost('/admin/promos/banner/quitar', <?= (int)$b['id'] ?>, 'id')"><i class="bi bi-x-lg"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="p-2">
                    <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-arrow-down-up"></i> Guardar orden</button>
                </div>
            </form>
        <?php else: ?>
            <div class="p-3 text-muted small">Sin banners. Creá el primero abajo. Ej: "3 Protenat + 1 gratis".</div>
        <?php endif; ?>
    </div>
</div>

<div class="card shadow-sm mb-4">
    <div class="card-header bg-white fw-semibold" id="bannerFormTitle">Nuevo banner de texto</div>
    <div class="card-body">
        <form method="post" action="/admin/promos/banner/guardar" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
            <input type="hidden" name="id" id="bannerId" value="0" />
            <div class="col-md-4">
                <label class="form-label small">Título *</label>
                <input class="form-control form-control-sm" name="titulo" id="bannerTitulo" maxlength="120" required placeholder="Ej: 3 Protenat + 1 gratis" />
            </div>
            <div class="col-md-4">
                <label class="form-label small">Texto / condición</label>
                <input class="form-control form-control-sm" name="texto" id="bannerTexto" maxlength="255" placeholder="Ej: Comprando 3 productos Protenat el 4° va gratis" />
            </div>
            <div class="col-md-4">
                <label class="form-label small">Marca (el botón lleva a la marca)</label>
                <select class="form-select form-select-sm" name="codsub" id="bannerMarca">
                    <option value="0">— Sin marca —</option>
                    <?php foreach ($brands as $brand): ?>
                    <option value="<?= (int)($brand['codsub'] ?? 0) ?>"><?= htmlspecialchars((string)($brand['nomsub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Link (si no hay marca)</label>
                <input class="form-control form-control-sm" name="link" id="bannerLink" maxlength="255" placeholder="/ruta o https://..." />
            </div>
            <div class="col-md-2">
                <label class="form-label small">Color texto</label>
                <input type="color" class="form-control form-control-sm" name="color" id="bannerColor" value="#FFFFFF" style="height:32px;padding:2px" />
            </div>
            <div class="col-md-2">
                <label class="form-label small">Fondo (vacío = tema)</label>
                <input class="form-control form-control-sm" name="bg" id="bannerBg" maxlength="7" placeholder="#C0392B" />
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar banner</button>
                <button class="btn btn-outline-secondary btn-sm" type="button" onclick="limpiarBanner()">Limpiar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarBanner(btn) {
    document.getElementById('bannerId').value = btn.dataset.id || '0';
    document.getElementById('bannerTitulo').value = btn.dataset.titulo || '';
    document.getElementById('bannerTexto').value = btn.dataset.texto || '';
    document.getElementById('bannerMarca').value = btn.dataset.codsub || '0';
    document.getElementById('bannerLink').value = btn.dataset.link || '';
    document.getElementById('bannerColor').value = /^#[0-9a-fA-F]{6}$/.test(btn.dataset.color || '') ? btn.dataset.color : '#FFFFFF';
    document.getElementById('bannerBg').value = btn.dataset.bg || '';
    document.getElementById('bannerFormTitle').textContent = 'Editar banner #' + (btn.dataset.id || '');
    document.getElementById('bannerTitulo').focus();
    window.scrollTo({top: document.getElementById('bannerFormTitle').offsetTop - 80, behavior: 'smooth'});
}
function limpiarBanner() {
    document.getElementById('bannerId').value = '0';
    document.getElementById('bannerTitulo').value = '';
    document.getElementById('bannerTexto').value = '';
    document.getElementById('bannerMarca').value = '0';
    document.getElementById('bannerLink').value = '';
    document.getElementById('bannerColor').value = '#FFFFFF';
    document.getElementById('bannerBg').value = '';
    document.getElementById('bannerFormTitle').textContent = 'Nuevo banner de texto';
}
</script>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Agregar promo</div>
    <div class="card-body">
        <form method="get" action="/admin/promos" class="mb-3">
            <div class="input-group input-group-sm">
                <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por nombre, código..." />
                <button class="btn btn-outline-secondary" type="submit"><i class="bi bi-search"></i> Buscar</button>
            </div>
        </form>

        <?php if ($q !== ''): ?>
            <?php if (!$searchResults): ?>
                <div class="text-muted small">Sin resultados para "<?= htmlspecialchars($q) ?>".</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-sm table-admin">
                        <thead><tr><th>Producto</th><th class="text-end">Normal final</th><th style="width:230px">Promo</th><th style="width:60px"></th></tr></thead>
                        <tbody>
                            <?php foreach ($searchResults as $it):
                                $itTiva = (float)($it['tiva'] ?? 0);
                                $itFinal = (float)($it['precio'] ?? 0) * (1 + $itTiva / 100);
                            ?>
                            <tr>
                                <td>
                                    <div class="small fw-semibold"><?= htmlspecialchars((string)($it['produ'] ?? '')) ?></div>
                                    <div class="text-muted" style="font-size:11px">#<?= (int)$it['idprodu'] ?></div>
                                </td>
                                <td class="text-end small fw-bold">$<?= number_format($itFinal, 0, ',', '.') ?></td>
                                <td>
                                    <form method="post" action="/admin/promos/agregar" class="d-flex gap-1">
                                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                                        <input type="hidden" name="idprodu" value="<?= (int)$it['idprodu'] ?>" />
                                        <input class="form-control form-control-sm" name="valor" placeholder="$ o %" required style="max-width:90px" />
                                        <select class="form-select form-select-sm" name="modo" style="max-width:110px">
                                            <option value="precio">Precio final</option>
                                            <option value="descuento">% OFF</option>
                                        </select>
                                        <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-plus-lg"></i></button>
                                    </form>
                                </td>
                                <td></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-muted small">Precio final = con IVA incluido. % OFF = descuento sobre el minorista.</div>
            <?php endif; ?>
        <?php else: ?>
            <div class="text-muted small">Usá el buscador para encontrar productos y ponerles promo.</div>
        <?php endif; ?>
    </div>
</div>
