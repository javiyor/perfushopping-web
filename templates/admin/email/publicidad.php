<?php
use Perfushopping\Web\Repo\FacturaRepo;
use Perfushopping\Web\Repo\UserRepo;

$contacts = $contacts ?? [];
$cities = $cities ?? [];
$categories = $categories ?? [];
$q = (string)($q ?? '');
$city = (string)($city ?? '');
$category = (string)($category ?? '');
$page = max(1, (int)($page ?? 1));
$pages = max(1, (int)($pages ?? 1));
$perPage = (int)($perPage ?? 200);
$total = (int)($total ?? 0);
$categoryLabels = UserRepo::customerCategoryOptions() + FacturaRepo::categoriaLabels();
$selectionKey = 'perf-email-ad-selection:' . hash('sha256', json_encode([$q, $city, $category], JSON_UNESCAPED_UNICODE));
$pageUrl = static function (int $target) use ($q, $city, $category, $perPage): string {
    return '/admin/email/publicidad?' . http_build_query(array_filter([
        'q' => $q, 'city' => $city, 'category' => $category, 'per_page' => $perPage, 'page' => $target,
    ], static fn($value): bool => $value !== '' && $value !== null));
};
?>
<div class="d-flex justify-content-between align-items-start mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-1">Envío publicitario</h4>
        <p class="text-muted small mb-0">Seleccioná contactos de clientes por ciudad y categoría para enviarles un mensaje.</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/email"><i class="bi bi-inbox"></i> Volver al correo</a>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-body">
        <form method="get" action="/admin/email/publicidad" class="row g-2 align-items-end">
            <div class="col-lg-3">
                <label class="form-label small mb-1">Buscar</label>
                <input class="form-control form-control-sm" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Nombre, email o ciudad" />
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small mb-1">Ciudad</label>
                <select class="form-select form-select-sm" name="city">
                    <option value="">Todas las ciudades</option>
                    <?php foreach ($cities as $cityOption): ?>
                    <option value="<?= htmlspecialchars((string)$cityOption) ?>" <?= $city === (string)$cityOption ? 'selected' : '' ?>><?= htmlspecialchars((string)$cityOption) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-3">
                <label class="form-label small mb-1">Categoría comercial</label>
                <select class="form-select form-select-sm" name="category">
                    <option value="">Todas las categorías</option>
                    <?php foreach ($categories as $value => $label): ?>
                    <option value="<?= htmlspecialchars((string)$value) ?>" <?= $category === (string)$value ? 'selected' : '' ?>><?= htmlspecialchars($categoryLabels[$value] ?? (string)$label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-2">
                <label class="form-label small mb-1">Por página</label>
                <select class="form-select form-select-sm" name="per_page">
                    <?php foreach ([50, 100, 200] as $size): ?><option value="<?= $size ?>" <?= $perPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?>
                </select>
            </div>
            <div class="col-sm-6 col-lg-1"><button class="btn btn-accent btn-sm w-100" type="submit"><i class="bi bi-search"></i></button></div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white d-flex flex-wrap gap-2 justify-content-between align-items-center">
        <div><strong><?= number_format($total, 0, ',', '.') ?></strong> contactos encontrados <span class="text-muted small">· Página <?= $page ?> de <?= $pages ?></span></div>
        <div class="d-flex gap-2 align-items-center">
            <label class="small mb-0"><input type="checkbox" id="selectPage" /> Seleccionar esta página</label>
            <button class="btn btn-outline-secondary btn-sm" type="button" id="clearSelection">Limpiar selección</button>
            <span class="badge bg-primary" id="selectedCount">0 seleccionados</span>
        </div>
    </div>
    <div class="table-responsive" style="max-height:480px;overflow:auto">
        <table class="table table-sm table-hover table-admin mb-0">
            <thead class="table-light sticky-top"><tr><th></th><th>Nombre</th><th>Email</th><th>Ciudad</th><th>Categoría</th><th>Origen</th><th>Estado</th></tr></thead>
            <tbody>
                <?php if (!$contacts): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No hay contactos con email para estos filtros.</td></tr>
                <?php else: foreach ($contacts as $contact): ?>
                <?php $key = (string)($contact['contact_key'] ?? ''); $unsubscribed = !empty($contact['unsubscribed']); $categoryValue = (string)($contact['categoria'] ?? ''); ?>
                <tr>
                    <td><input type="checkbox" class="marketing-contact-check" data-key="<?= htmlspecialchars($key, ENT_QUOTES) ?>" <?= $unsubscribed ? 'disabled' : '' ?> /></td>
                    <td><?= htmlspecialchars((string)($contact['nombre'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($contact['email'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string)($contact['ciudad'] ?? '')) ?></td>
                    <td><?= htmlspecialchars($categoryLabels[$categoryValue] ?? $categoryValue) ?></td>
                    <td><?= ($contact['source'] ?? '') === 'web' ? 'Cuenta web' : 'Presencial' ?></td>
                    <td><?php if ($unsubscribed): ?><span class="badge bg-secondary">Desuscripto</span><?php else: ?><span class="badge bg-success">Disponible</span><?php endif; ?></td>
                </tr>
                <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pages > 1): ?>
    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <a class="btn btn-outline-secondary btn-sm <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(max(1, $page - 1))) ?>">‹ Anterior</a>
        <span class="small text-muted">La selección se conserva al cambiar de página mientras mantengas los filtros.</span>
        <a class="btn btn-outline-secondary btn-sm <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(min($pages, $page + 1))) ?>">Siguiente ›</a>
    </div>
    <?php endif; ?>
</div>

<form id="advertisingForm" class="card shadow-sm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
    <div class="card-header bg-white fw-semibold">Mensaje publicitario</div>
    <div class="card-body">
        <div class="mb-3"><label class="form-label">Asunto</label><input class="form-control" name="subject" maxlength="200" required /></div>
        <div class="mb-3"><label class="form-label">Mensaje</label><textarea class="form-control" name="body" rows="7" maxlength="30000" required></textarea></div>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="1" name="include_unsubscribe" id="includeUnsubscribe" checked />
            <label class="form-check-label" for="includeUnsubscribe">Agregar mensaje y enlace para dejar de recibir publicidad</label>
        </div>
    </div>
    <div class="card-footer bg-white d-flex flex-wrap gap-2 align-items-center">
        <button class="btn btn-accent" type="submit" id="sendAdvertising"><i class="bi bi-send"></i> Enviar a la selección</button>
        <span class="small text-muted" id="sendProgress">Se envía en tandas pequeñas para informar el progreso.</span>
    </div>
</form>

<script>
(function(){
    const storageKey = <?= json_encode($selectionKey) ?>;
    const selected = new Set();
    try { (JSON.parse(localStorage.getItem(storageKey) || '[]')).forEach(k => selected.add(String(k))); } catch (e) {}
    const checks = Array.from(document.querySelectorAll('.marketing-contact-check'));
    const selectPage = document.getElementById('selectPage');
    const countEl = document.getElementById('selectedCount');
    const update = function(){
        checks.forEach(c => { if (!c.disabled) c.checked = selected.has(c.dataset.key); });
        if (countEl) countEl.textContent = selected.size + ' seleccionados';
        if (selectPage) {
            const available = checks.filter(c => !c.disabled);
            selectPage.checked = available.length > 0 && available.every(c => c.checked);
            selectPage.indeterminate = available.some(c => c.checked) && !selectPage.checked;
        }
        try { localStorage.setItem(storageKey, JSON.stringify(Array.from(selected))); } catch (e) {}
    };
    checks.forEach(c => c.addEventListener('change', function(){
        if (c.checked) selected.add(c.dataset.key); else selected.delete(c.dataset.key);
        update();
    }));
    if (selectPage) selectPage.addEventListener('change', function(){
        checks.forEach(c => { if (!c.disabled) { if (selectPage.checked) selected.add(c.dataset.key); else selected.delete(c.dataset.key); } });
        update();
    });
    document.getElementById('clearSelection').addEventListener('click', function(){ selected.clear(); update(); });
    update();

    const form = document.getElementById('advertisingForm');
    const button = document.getElementById('sendAdvertising');
    const progress = document.getElementById('sendProgress');
    form.addEventListener('submit', async function(event){
        event.preventDefault();
        const keys = Array.from(selected);
        if (!keys.length) { alert('Seleccioná al menos un contacto disponible.'); return; }
        if (!confirm('¿Enviar este mensaje a ' + keys.length + ' contacto(s) seleccionados?')) return;
        button.disabled = true;
        let sent = 0, skipped = 0, failed = 0;
        const failures = [];
        try {
            for (let i = 0; i < keys.length; i += 25) {
                const data = new FormData(form);
                data.delete('contact_keys[]');
                keys.slice(i, i + 25).forEach(key => data.append('contact_keys[]', key));
                progress.textContent = 'Enviando ' + Math.min(i + 25, keys.length) + ' de ' + keys.length + '…';
                const response = await fetch('/admin/email/publicidad/enviar', {method:'POST', body:data, headers:{'X-Requested-With':'XMLHttpRequest'}});
                const result = await response.json();
                if (!response.ok || !result.ok) throw new Error(result.error || 'Falló una tanda de envío.');
                sent += parseInt(result.sent || 0);
                skipped += parseInt(result.skipped || 0);
                failed += (result.errors || []).length;
                failures.push(...(result.errors || []));
                keys.slice(i, i + 25).forEach(key => selected.delete(key));
                update();
            }
            progress.textContent = 'Finalizado · Enviados: ' + sent + ' · Omitidos: ' + skipped + ' · Fallidos: ' + failed;
            if (failures.length) alert('No se pudieron enviar algunos mensajes:\n' + failures.map(f => f.email + ': ' + f.error).join('\n'));
        } catch (error) {
            progress.textContent = 'Envío detenido: ' + error.message + ' · Enviados antes del error: ' + sent;
        } finally { button.disabled = false; }
    });
})();
</script>
