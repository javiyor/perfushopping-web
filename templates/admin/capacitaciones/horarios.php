<?php
$events = $events ?? [];
?>
<div class="page">
  <h2 style="margin:0 0 10px">Capacitaciones: horarios / cupos / sede</h2>
  <p style="margin:0;color:rgba(246,244,239,0.72)">Cargar solo lunes. No se permiten fechas pasadas.</p>
  <div style="margin-top:10px">
    <a class="btn secondary" href="/admin/capacitaciones">Volver a registros</a>
  </div>
</div>

<div class="page" style="margin-top:14px">
  <h3 style="margin:0 0 10px">Nuevo horario</h3>
  <form method="post" action="/admin/capacitaciones/horarios/save">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />

    <div style="display:grid;grid-template-columns:1fr 1fr 1fr 1fr;gap:10px">
      <div>
        <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Fecha (lunes)</label>
        <input type="date" name="monday_date" required />
      </div>
      <div>
        <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Inicio</label>
        <input name="start_time" placeholder="09:30" required maxlength="5" />
      </div>
      <div>
        <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Fin</label>
        <input name="end_time" placeholder="11:00" required maxlength="5" />
      </div>
      <div>
        <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Cupo</label>
        <input type="number" name="capacity" min="1" max="200" value="20" required />
      </div>
    </div>

    <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Sede</label>
    <input name="venue_name" required maxlength="160" placeholder="Ej: Perfushopping - Salon tecnico" />

    <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Direccion (opcional)</label>
    <input name="venue_address" maxlength="190" placeholder="Calle y numero" />

    <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Notas (opcional)</label>
    <textarea name="notes" rows="2" maxlength="400" placeholder="Ej: traer modelo / herramientas / etc"></textarea>

    <label style="display:flex;align-items:center;gap:10px;margin-top:10px;color:rgba(246,244,239,0.72)">
      <input type="checkbox" name="active" checked />
      Activo (visible en el formulario)
    </label>

    <button class="btn" type="submit" style="margin-top:12px">Guardar</button>
  </form>
</div>

<div class="page" style="margin-top:14px;overflow:auto">
  <h3 style="margin:0 0 10px">Horarios cargados</h3>
  <table style="width:100%;border-collapse:collapse;min-width:980px">
    <thead>
      <tr style="text-align:left;color:rgba(246,244,239,0.72)">
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">ID</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Fecha</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Horario</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Sede</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Cupo</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Disponibles</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Activo</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Editar</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($events as $ev): ?>
        <tr>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)">#<?= (int)$ev['id'] ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars((string)($ev['monday_date'] ?? '')) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars(substr((string)($ev['start_time'] ?? ''), 0, 5) . '-' . substr((string)($ev['end_time'] ?? ''), 0, 5)) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)">
            <div style="font-size:13px">
              <?= htmlspecialchars((string)($ev['venue_name'] ?? '')) ?>
              <div style="color:rgba(246,244,239,0.72)"><?= htmlspecialchars((string)($ev['venue_address'] ?? '')) ?></div>
            </div>
          </td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= (int)($ev['capacity'] ?? 0) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= (int)($ev['remaining'] ?? 0) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= ((int)($ev['active'] ?? 0) === 1) ? 'si' : 'no' ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)">
            <form method="post" action="/admin/capacitaciones/horarios/save" style="display:grid;grid-template-columns:140px 88px 88px 80px 1fr 120px auto;gap:8px;align-items:center">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= (int)$ev['id'] ?>" />
              <input type="date" name="monday_date" value="<?= htmlspecialchars((string)($ev['monday_date'] ?? '')) ?>" required />
              <input name="start_time" value="<?= htmlspecialchars(substr((string)($ev['start_time'] ?? ''), 0, 5)) ?>" required maxlength="5" />
              <input name="end_time" value="<?= htmlspecialchars(substr((string)($ev['end_time'] ?? ''), 0, 5)) ?>" required maxlength="5" />
              <input type="number" name="capacity" min="1" max="200" value="<?= (int)($ev['capacity'] ?? 0) ?>" required />
              <input name="venue_name" value="<?= htmlspecialchars((string)($ev['venue_name'] ?? '')) ?>" required maxlength="160" />
              <input name="venue_address" value="<?= htmlspecialchars((string)($ev['venue_address'] ?? '')) ?>" maxlength="190" />
              <label style="display:flex;align-items:center;gap:6px;color:rgba(246,244,239,0.72)">
                <input type="checkbox" name="active" <?= ((int)($ev['active'] ?? 0) === 1) ? 'checked' : '' ?> />
                activo
              </label>
              <button class="btn secondary" type="submit">Guardar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$events): ?>
        <tr>
          <td colspan="8" style="padding:14px;color:rgba(246,244,239,0.72)">Sin horarios.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
