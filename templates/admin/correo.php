<?php
$agencyFilters = $agencyFilters ?? ['stateId' => '', 'pickup_availability' => '', 'package_reception' => ''];
$savedFilters = $savedFilters ?? ['stateId' => '', 'cityName' => ''];
$agencies = $agencies ?? [];
$agenciesSaved = $agenciesSaved ?? null;
$savedAgencies = $savedAgencies ?? [];
?>

<div class="page">
  <div style="display:flex;gap:12px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
    <div>
      <h2 style="margin:0 0 8px">Correo Argentino</h2>
      <p style="margin:0;color:rgba(246,244,239,0.72)">Prueba autenticacion con la API y consulta sucursales habilitadas por provincia.</p>
    </div>
    <a class="btn secondary" href="/admin">Volver al admin</a>
  </div>
</div>

<div class="page" style="margin-top:14px">
  <h3 style="margin:0 0 12px">1. Validar credenciales</h3>
  <form method="post" action="/admin/correo/auth">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
    <button class="btn" type="submit">Probar auth</button>
    <?php if ($authOk === true): ?>
      <span class="chip gold" style="margin-left:10px">OK</span>
    <?php elseif ($authOk === false): ?>
      <span class="chip" style="margin-left:10px">Error</span>
    <?php endif; ?>
  </form>
</div>

<div class="page" style="margin-top:14px">
  <h3 style="margin:0 0 12px">2. Consultar sucursales via API</h3>
  <form method="post" action="/admin/correo/agencies" class="filters" style="grid-template-columns:1fr 1fr 1fr auto">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars((string)$csrf) ?>" />
    <input name="stateId" maxlength="1" placeholder="Codigo provincia ISO (ej. S)" value="<?= htmlspecialchars((string)($agencyFilters['stateId'] ?? '')) ?>" />
    <select name="pickup_availability">
      <option value="" <?= (($agencyFilters['pickup_availability'] ?? '') === '') ? 'selected' : '' ?>>Entrega: todas</option>
      <option value="1" <?= (($agencyFilters['pickup_availability'] ?? '') === '1') ? 'selected' : '' ?>>Entrega habilitada</option>
      <option value="0" <?= (($agencyFilters['pickup_availability'] ?? '') === '0') ? 'selected' : '' ?>>Entrega no habilitada</option>
    </select>
    <select name="package_reception">
      <option value="" <?= (($agencyFilters['package_reception'] ?? '') === '') ? 'selected' : '' ?>>Imposicion: todas</option>
      <option value="1" <?= (($agencyFilters['package_reception'] ?? '') === '1') ? 'selected' : '' ?>>Recibe paquetes</option>
      <option value="0" <?= (($agencyFilters['package_reception'] ?? '') === '0') ? 'selected' : '' ?>>No recibe paquetes</option>
    </select>
    <button class="btn" type="submit">Buscar via API</button>
  </form>

  <?php if ($agenciesSaved !== null): ?>
    <div class="meta" style="margin-top:10px">Guardadas/actualizadas en base: <?= (int)$agenciesSaved ?></div>
  <?php endif; ?>

  <?php if ($agencies): ?>
    <div style="display:grid;gap:10px;margin-top:12px">
      <?php foreach ($agencies as $agency): ?>
        <?php $loc = is_array($agency['location'] ?? null) ? $agency['location'] : $agency; ?>
        <div class="variant" style="display:block">
          <strong><?= htmlspecialchars((string)($agency['agency_name'] ?? $agency['agency_id'] ?? 'Sucursal')) ?></strong>
          <div class="meta">ID: <?= htmlspecialchars((string)($agency['agency_id'] ?? '-')) ?> · <?= htmlspecialchars((string)($loc['city_name'] ?? $agency['city_name'] ?? '-')) ?>, <?= htmlspecialchars((string)($loc['state_name'] ?? $agency['state_name'] ?? '-')) ?></div>
          <div class="meta">Direccion: <?= htmlspecialchars((string)($loc['street_name'] ?? $agency['street_name'] ?? '-')) ?> <?= htmlspecialchars((string)($loc['street_number'] ?? $agency['street_number'] ?? '')) ?> · CP <?= htmlspecialchars((string)($loc['zip_code'] ?? $agency['zip_code'] ?? '-')) ?></div>
          <div class="meta">Entrega: <?= !empty($agency['pickup_availability']) ? 'si' : 'no' ?> · Imposicion: <?= !empty($agency['package_reception']) ? 'si' : 'no' ?></div>
          <div class="meta">Horario: <?= htmlspecialchars((string)($agency['schedule'] ?? '-')) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<div class="page" style="margin-top:14px">
  <div class="admin-products-list-head">
    <h3 style="margin:0">3. Sucursales en base</h3>
    <span><?= count($savedAgencies) ?> sucursal(es)</span>
  </div>

  <form method="get" action="/admin/correo/saved" class="filters" style="grid-template-columns:1fr 1fr auto;margin-top:8px">
    <input name="stateId" maxlength="1" placeholder="Codigo provincia ISO (ej. S)" value="<?= htmlspecialchars((string)($savedFilters['stateId'] ?? '')) ?>" />
    <input name="cityName" placeholder="Buscar por ciudad" value="<?= htmlspecialchars((string)($savedFilters['cityName'] ?? '')) ?>" />
    <button class="btn" type="submit">Filtrar</button>
  </form>

  <?php if (!$savedAgencies): ?>
    <div class="notice" style="margin-top:12px">No hay sucursales guardadas en base. Usa la consulta via API para traerlas.</div>
  <?php else: ?>
    <div style="display:grid;gap:10px;margin-top:12px">
      <?php foreach ($savedAgencies as $agency): ?>
        <div class="variant" style="display:block">
          <strong><?= htmlspecialchars((string)($agency['agency_name'] ?? $agency['agency_id'] ?? 'Sucursal')) ?></strong>
          <div class="meta">ID: <?= htmlspecialchars((string)($agency['agency_id'] ?? '-')) ?> · <?= htmlspecialchars((string)($agency['city_name'] ?? '-')) ?>, <?= htmlspecialchars((string)($agency['state_name'] ?? '-')) ?></div>
          <div class="meta">Direccion: <?= htmlspecialchars((string)($agency['street_name'] ?? '-')) ?> <?= htmlspecialchars((string)($agency['street_number'] ?? '')) ?> · CP <?= htmlspecialchars((string)($agency['zip_code'] ?? '-')) ?></div>
          <div class="meta">Entrega: <?= !empty($agency['pickup_availability']) ? 'si' : 'no' ?> · Imposicion: <?= !empty($agency['package_reception']) ? 'si' : 'no' ?></div>
          <div class="meta">Horario: <?= htmlspecialchars((string)($agency['schedule'] ?? '-')) ?> · Actualizado: <?= htmlspecialchars((string)($agency['updated_at'] ?? '-')) ?></div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
