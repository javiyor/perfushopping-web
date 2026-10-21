<?php
$notas = $notas ?? [];
$q = (string)($q ?? '');
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div>
        <h4 class="fw-bold mb-1">Notas de pedido</h4>
        <p class="text-muted small mb-0">Notas generadas desde la pantalla de Stock (máximo 300 recientes).</p>
    </div>
    <div class="d-flex gap-2">
        <a class="btn btn-outline-secondary btn-sm" href="/admin/stock"><i class="bi bi-boxes"></i> Volver a Stock</a>
        <a class="btn btn-accent btn-sm" href="/admin/nota-pedido/nueva"><i class="bi bi-plus-lg"></i> Nueva nota</a>
    </div>
</div>

<form method="get" action="/admin/nota-pedido" class="card card-body mb-3">
    <div class="input-group input-group-sm">
        <input class="form-control" type="search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por número de nota o proveedor" />
        <button class="btn btn-outline-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
        <?php if ($q !== ''): ?><a class="btn btn-outline-secondary" href="/admin/nota-pedido">Limpiar</a><?php endif; ?>
    </div>
</form>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover align-middle mb-0">
            <thead><tr>
                <th>Número</th><th>Fecha</th><th>Proveedor</th><th class="text-center">Productos</th><th class="text-center">Unidades</th><th>Generada por</th><th class="text-end">Acciones</th>
            </tr></thead>
            <tbody>
            <?php foreach ($notas as $nota): ?>
                <tr>
                    <td class="fw-semibold"><a href="/admin/nota-pedido/<?= (int)$nota['id'] ?>"><?= htmlspecialchars((string)$nota['codigo']) ?></a></td>
                    <td><?= htmlspecialchars((string)$nota['created_at']) ?></td>
                    <td><?= htmlspecialchars((string)($nota['proveedor_nombre'] ?: '—')) ?></td>
                    <td class="text-center"><?= (int)$nota['items_count'] ?></td>
                    <td class="text-center"><?= (int)$nota['total_qty'] ?></td>
                    <td><?= htmlspecialchars((string)($nota['created_by_nombre'] ?: '—')) ?></td>
                    <td class="text-end"><a class="btn btn-outline-primary btn-sm" href="/admin/nota-pedido/<?= (int)$nota['id'] ?>"><i class="bi bi-eye"></i> Ver / imprimir</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$notas): ?><tr><td colspan="7" class="text-center text-muted py-4"><?= $q !== '' ? 'No hay notas que coincidan con la búsqueda.' : 'Todavía no se generaron notas de pedido desde Stock.' ?></td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
