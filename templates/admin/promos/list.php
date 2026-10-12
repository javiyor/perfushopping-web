<?php
$promos = $promos ?? [];
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
function promoPost(action, idprodu) {
    const f = document.createElement('form');
    f.method = 'post';
    f.action = action;
    const csrf = document.createElement('input');
    csrf.type = 'hidden'; csrf.name = '_csrf'; csrf.value = <?= json_encode($csrf) ?>;
    const inp = document.createElement('input');
    inp.type = 'hidden'; inp.name = 'idprodu'; inp.value = idprodu;
    f.appendChild(csrf); f.appendChild(inp);
    document.body.appendChild(f); f.submit();
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
