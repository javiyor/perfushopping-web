<?php
$depositos = $depositos ?? [];
$depoA = $depoA ?? null;
$depoB = $depoB ?? null;
$recomendacion = $recomendacion ?? null;
$error = (string)($error ?? '');
$mostrarReporte = $depoA !== null && $depoB !== null && $recomendacion !== null;
?>
<style>
@media print {
    .stock-replenishment-controls, .stock-replenishment-actions, .admin-sidebar, .sidebar, .sidebar-backdrop, .topbar, nav { display: none !important; }
    .stock-replenishment-list { break-after: page; page-break-after: always; }
    .stock-replenishment-list:last-child { break-after: auto; page-break-after: auto; }
}
</style>

<div class="d-flex justify-content-between align-items-start mb-3 stock-replenishment-controls">
    <div>
        <h4 class="fw-bold mb-1">Reposición entre depósitos</h4>
        <p class="text-muted small mb-0">Sugiere cuánto mover para repartir por mitades el stock de variedades sin existencia en uno de los depósitos.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/stock"><i class="bi bi-arrow-left"></i> Volver a stock</a>
</div>

<div class="card shadow-sm mb-3 stock-replenishment-controls">
    <div class="card-body">
        <form method="get" action="/admin/stock/reposicion" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small">Comparar depósito</label>
                <select class="form-select form-select-sm" name="depo_a" required>
                    <option value="">Seleccionar…</option>
                    <?php foreach ($depositos as $depo): $id = (int)($depo['iddepo'] ?? 0); ?>
                        <option value="<?= $id ?>" <?= $depoA === $id ? 'selected' : '' ?>><?= htmlspecialchars((string)($depo['nomdepo'] ?? '')) ?> (<?= $id ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-5">
                <label class="form-label small">con depósito</label>
                <select class="form-select form-select-sm" name="depo_b" required>
                    <option value="">Seleccionar…</option>
                    <?php foreach ($depositos as $depo): $id = (int)($depo['iddepo'] ?? 0); ?>
                        <option value="<?= $id ?>" <?= $depoB === $id ? 'selected' : '' ?>><?= htmlspecialchars((string)($depo['nomdepo'] ?? '')) ?> (<?= $id ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i> Revisar</button>
            </div>
        </form>
        <div class="form-text mt-2">Solo incluye depósitos con marca 2. Para variedades con stock 0 en un depósito y más de 2 en el otro, propone trasladar la mitad entera de las unidades disponibles.</div>
    </div>
</div>

<?php if ($error !== ''): ?>
    <div class="alert alert-warning"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if ($mostrarReporte): ?>
    <?php
    $depoAInfo = $recomendacion['deposito_a'];
    $depoBInfo = $recomendacion['deposito_b'];
    $enviarA = $recomendacion['enviar_a'] ?? [];
    $enviarB = $recomendacion['enviar_b'] ?? [];
    ?>
    <div class="d-flex justify-content-between align-items-center mb-3 stock-replenishment-actions">
        <div class="small text-muted">
            Sugerencias: <?= count($enviarA) ?> variedad(es) para <?= htmlspecialchars((string)$depoAInfo['nomdepo']) ?>;
            <?= count($enviarB) ?> para <?= htmlspecialchars((string)$depoBInfo['nomdepo']) ?>.
        </div>
        <button class="btn btn-outline-primary btn-sm" type="button" onclick="window.print()"><i class="bi bi-printer"></i> Imprimir listados</button>
    </div>

    <?php foreach ([
        ['depo' => $depoAInfo, 'desde' => $depoBInfo, 'items' => $enviarA],
        ['depo' => $depoBInfo, 'desde' => $depoAInfo, 'items' => $enviarB],
    ] as $listado): ?>
        <section class="card shadow-sm mb-3 stock-replenishment-list">
            <div class="card-header bg-white fw-semibold">
                Reposición para <?= htmlspecialchars((string)$listado['depo']['nomdepo']) ?>
                <span class="text-muted fw-normal">(desde <?= htmlspecialchars((string)$listado['desde']['nomdepo']) ?>)</span>
            </div>
            <div class="table-responsive">
                <table class="table table-admin table-striped mb-0">
                    <thead>
                        <tr><th>Producto</th><th>Variedad</th><th>Código de barras</th><th class="text-end">Cantidad a enviar</th></tr>
                    </thead>
                    <tbody>
                        <?php if (!$listado['items']): ?>
                            <tr><td colspan="4" class="text-muted text-center py-3">No hay variedades para reponer desde el otro depósito.</td></tr>
                        <?php else: foreach ($listado['items'] as $item): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)$item['produ']) ?></td>
                                <td><?= htmlspecialchars((string)$item['nomgusto']) ?></td>
                                <td><?= htmlspecialchars((string)($item['codscan'] ?? '')) ?></td>
                                <td class="text-end fw-semibold"><?= (int)$item['cantidad'] ?></td>
                            </tr>
                        <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    <?php endforeach; ?>
<?php endif; ?>
