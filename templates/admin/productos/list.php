<?php
use Perfushopping\Web\Support\Format;

$q = (string)($q ?? '');
$codsub = (int)($codsub ?? 0);
$codrub = (int)($codrub ?? 0);
$sort = (string)($sort ?? 'id');
$order = (string)($order ?? 'desc');
$view = (string)($view ?? 'cards');
$brands = $brands ?? [];
$categories = $categories ?? [];
$products = $products ?? [];
$page = (int)($page ?? 1);
$perPage = (int)($perPage ?? 60);
$total = (int)($total ?? 0);
$totalPages = $perPage > 0 ? (int)ceil($total / $perPage) : 1;
$from = $total > 0 ? (($page - 1) * $perPage + 1) : 0;
$to = min($page * $perPage, $total);
$preservePage = $preserve ?? [];
$preservePage['page'] = (string)$page;
$preservePage['per_page'] = (string)$perPage;
$pageUrl = static fn(array $extra) => '/admin/productos?' . http_build_query(array_merge($preserve, $extra));

$sortable = ['id'=>'ID','codprodu'=>'Código','produ'=>'Producto','marca'=>'Marca','categoria'=>'Categoría','precio'=>'Precio','fecompra'=>'F.compra'];
$preserve = [];
if ($q !== '') $preserve['q'] = $q;
if ($codsub > 0) $preserve['codsub'] = (string)$codsub;
if ($codrub > 0) $preserve['codrub'] = (string)$codrub;
if ($view !== 'cards') $preserve['view'] = $view;
$preserve['sort'] = $sort;
$preserve['order'] = $order;
$sortLink = static fn(string $col) => '/admin/productos?' . http_build_query(array_merge($preserve, ['sort' => $col, 'order' => ($sort === $col && $order === 'asc') ? 'desc' : 'asc']));
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Productos</h4>
        <p class="text-muted small">Busca productos, edita precios, visibilidad y más</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-accent btn-sm" href="/admin/productos/nuevo"><i class="bi bi-plus-lg"></i> Nuevo</a>
        <a class="btn btn-outline-secondary btn-sm" href="/admin/productos/importar"><i class="bi bi-upload"></i> Importar</a>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/productos" class="row g-2">
            <div class="col-lg-5">
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por id, nombre, código, variedad o codscan" />
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="codsub">
                    <option value="0">Todas las marcas</option>
                    <?php foreach ($brands as $brand): ?>
                        <option value="<?= (int)($brand['codsub'] ?? 0) ?>" <?= $codsub === (int)($brand['codsub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($brand['nomsub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-lg-3">
                <select class="form-select form-select-sm" name="codrub">
                    <option value="0">Todas las categorías</option>
                    <?php foreach ($categories as $category): ?>
                        <option value="<?= (int)($category['codrub'] ?? 0) ?>" <?= $codrub === (int)($category['codrub'] ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars((string)($category['nomrub'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="sort" value="<?= htmlspecialchars($sort) ?>" />
            <input type="hidden" name="order" value="<?= htmlspecialchars($order) ?>" />
            <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>" />
            <div class="col-lg-1 d-flex gap-1">
                <button class="btn btn-accent btn-sm flex-fill" type="submit"><i class="bi bi-search"></i></button>
                <?php if ($q !== '' || $codsub > 0 || $codrub > 0): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="/admin/productos"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
            </div>
        </form>
        <div class="d-flex gap-2 mt-2">
            <div class="btn-group btn-group-sm">
                <a class="btn <?= $view === 'cards' ? 'btn-accent' : 'btn-outline-secondary' ?>" href="/admin/productos?<?= http_build_query(array_merge($preserve, ['view'=>'cards'])) ?>"><i class="bi bi-grid"></i> Tarjetas</a>
                <a class="btn <?= $view === 'table' ? 'btn-accent' : 'btn-outline-secondary' ?>" href="/admin/productos?<?= http_build_query(array_merge($preserve, ['view'=>'table'])) ?>"><i class="bi bi-list"></i> Tabla</a>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
    <div class="small text-muted">
        <?php if ($total > 0): ?>
            Mostrando <?= $from ?>–<?= $to ?> de <?= $total ?> productos
        <?php endif; ?>
    </div>
    <div class="d-flex align-items-center gap-2">
        <select class="form-select form-select-sm" style="width:auto" onchange="window.location.href='<?= htmlspecialchars($pageUrl(['page' => '1', 'per_page' => ''])) ?>' + this.value">
            <?php foreach ([30, 60, 100, 200] as $pp): ?>
                <option value="<?= $pp ?>" <?= $perPage === $pp ? 'selected' : '' ?>><?= $pp ?> por página</option>
            <?php endforeach; ?>
        </select>
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Paginación">
                <ul class="pagination pagination-sm mb-0">
                    <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page - 1])) ?>">&laquo;</a>
                    </li>
                    <?php
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    if ($startPage > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => 1])) ?>">1</a>
                        </li>
                        <?php if ($startPage > 2): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif;
                    endif;
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                            <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $i])) ?>"><?= $i ?></a>
                        </li>
                    <?php endfor;
                    if ($endPage < $totalPages):
                        if ($endPage < $totalPages - 1): ?>
                            <li class="page-item disabled"><span class="page-link">...</span></li>
                        <?php endif; ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $totalPages])) ?>"><?= $totalPages ?></a>
                        </li>
                    <?php endif; ?>
                    <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page + 1])) ?>">&raquo;</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php if (!$products): ?>
    <div class="alert alert-info">No se encontraron productos.</div>
<?php elseif ($view === 'table'): ?>
    <div class="card shadow-sm">
        <div class="table-responsive">
            <table class="table table-sm table-admin mb-0">
                <thead>
                    <tr>
                        <?php foreach ($sortable as $col => $label):
                            $active = $sort === $col;
                        ?>
                            <th class="<?= $active ? 'sort-active' : '' ?>">
                                <a href="<?= htmlspecialchars($sortLink($col)) ?>" class="text-decoration-none d-flex align-items-center gap-1">
                                    <?= htmlspecialchars($label) ?>
                                    <?php if ($active): ?>
                                        <i class="bi bi-chevron-<?= $order === 'asc' ? 'up' : 'down' ?>"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                        <?php endforeach; ?>
                        <th>Var.</th>
                        <th>Web</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($products as $item):
                        $itemId = (int)($item['idprodu'] ?? 0);
                        $itemIva = (float)($item['tiva'] ?? 0);
                        $itemGross = (float)($item['precio'] ?? 0) * (1 + ($itemIva / 100));
                        $query = [];
                        if ($q !== '') $query['q'] = $q;
                        if ($codsub > 0) $query['codsub'] = (string)$codsub;
                        if ($codrub > 0) $query['codrub'] = (string)$codrub;
                        $href = '/admin/productos/' . $itemId . ($query ? '?' . http_build_query($query) : '');
                    ?>
                        <tr>
                            <td><strong>#<?= $itemId ?></strong></td>
                            <td><code><?= htmlspecialchars((string)($item['codprodu'] ?? '-')) ?></code></td>
                            <td><a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none fw-semibold"><?= htmlspecialchars(mb_substr((string)($item['produ'] ?? ''), 0, 60)) ?></a></td>
                            <td class="small"><?= htmlspecialchars((string)($item['nomsub'] ?? '-')) ?></td>
                            <td class="small"><?= htmlspecialchars((string)($item['nomrub'] ?? '-')) ?></td>
                            <td class="text-end"><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($itemGross * 100))) ?></td>
                            <td class="text-center"><?= (int)($item['variants_count'] ?? 0) ?></td>
                            <td class="text-center">
                                <span class="badge <?= ((int)($item['enweb'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>" style="font-size:10px"><?= ((int)($item['enweb'] ?? 0) === 1) ? 'ON' : 'OFF' ?></span>
                            </td>
                            <td>
                                <a class="btn btn-sm btn-outline-secondary py-0 px-1" href="<?= htmlspecialchars($href) ?>" style="font-size:11px"><i class="bi bi-pencil"></i></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php else: ?>
    <div class="row g-3">
        <?php foreach ($products as $item):
            $itemId = (int)($item['idprodu'] ?? 0);
            $itemIva = (float)($item['tiva'] ?? 0);
            $itemGross = (float)($item['precio'] ?? 0) * (1 + ($itemIva / 100));
            $query = [];
            if ($q !== '') $query['q'] = $q;
            if ($codsub > 0) $query['codsub'] = (string)$codsub;
            if ($codrub > 0) $query['codrub'] = (string)$codrub;
            $href = '/admin/productos/' . $itemId . ($query ? '?' . http_build_query($query) : '');
        ?>
            <div class="col-lg-4 col-md-6">
                <a href="<?= htmlspecialchars($href) ?>" class="text-decoration-none">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <strong>#<?= $itemId ?></strong>
                                <span class="badge <?= ((int)($item['enweb'] ?? 0) === 1) ? 'bg-success' : 'bg-secondary' ?>"><?= ((int)($item['enweb'] ?? 0) === 1) ? 'En web' : 'Oculto' ?></span>
                            </div>
                            <h6 class="card-title mb-1" style="font-size:14px"><?= htmlspecialchars((string)($item['produ'] ?? '')) ?></h6>
                            <div class="small text-muted">
                                <?= htmlspecialchars((string)($item['nomsub'] ?? '-')) ?> · <?= htmlspecialchars((string)($item['nomrub'] ?? '-')) ?>
                            </div>
                            <div class="d-flex justify-content-between mt-2 small">
                                <span><?= htmlspecialchars(Format::moneyRoundedFromCents((int)round($itemGross * 100))) ?></span>
                                <span><?= (int)($item['variants_count'] ?? 0) ?> var.</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($totalPages > 1): ?>
    <div class="d-flex justify-content-center mt-3">
        <nav>
            <ul class="pagination pagination-sm mb-0">
                <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page - 1])) ?>">&laquo;</a>
                </li>
                <?php
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $page + 2);
                if ($startPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => 1])) ?>">1</a>
                    </li>
                    <?php if ($startPage > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif;
                endif;
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                        <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $i])) ?>"><?= $i ?></a>
                    </li>
                <?php endfor;
                if ($endPage < $totalPages):
                    if ($endPage < $totalPages - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $totalPages])) ?>"><?= $totalPages ?></a>
                    </li>
                <?php endif; ?>
                <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($pageUrl(['page' => $page + 1])) ?>">&raquo;</a>
                </li>
            </ul>
        </nav>
    </div>
<?php endif; ?>
