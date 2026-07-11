<?php
$flash = $flash ?? null;
?>
<div class="page">
  <h2 style="margin:0 0 10px">Capacitaciones (registros)</h2>
  <p style="margin:0;color:rgba(246,244,239,0.72)">Registros de inscripcion a capacitaciones.</p>
  <div style="margin-top:10px">
    <a class="btn secondary" href="/admin/capacitaciones/horarios">Horarios / cupos / sede</a>
  </div>
</div>

<div class="page" style="margin-top:14px;overflow:auto">
  <table style="width:100%;border-collapse:collapse;min-width:980px">
    <thead>
      <tr style="text-align:left;color:rgba(246,244,239,0.72)">
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">ID</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Tipo</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Fecha (lunes)</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Horario / sede</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Nombre</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Salon</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Ciudad</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Contacto</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Estado</th>
        <th style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.10)">Accion</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($list as $r): ?>
        <tr>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)">#<?= (int)$r['id'] ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars(($r['kind'] ?? '') === 'pro' ? 'Profesional' : 'Cliente') ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars((string)($r['monday_date'] ?? '')) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)">
            <div style="font-size:13px">
              <?= htmlspecialchars(substr((string)($r['start_time'] ?? ''), 0, 5) . '-' . substr((string)($r['end_time'] ?? ''), 0, 5)) ?>
              <div style="color:rgba(246,244,239,0.72)"><?= htmlspecialchars((string)($r['venue_name'] ?? '')) ?></div>
            </div>
          </td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars((string)($r['name'] ?? '')) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars((string)($r['salon_name'] ?? '')) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars(trim((string)($r['city'] ?? '')) . (isset($r['province']) && $r['province'] !== '' ? ' - ' . (string)$r['province'] : '')) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)">
            <div style="font-size:13px">
              <?= htmlspecialchars((string)($r['phone'] ?? '')) ?>
              <?php if (!empty($r['email'])): ?>
                <div style="color:rgba(246,244,239,0.72)"><?= htmlspecialchars((string)$r['email']) ?></div>
              <?php endif; ?>
            </div>
          </td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)"><?= htmlspecialchars((string)($r['status'] ?? '')) ?></td>
          <td style="padding:10px;border-bottom:1px solid rgba(255,255,255,0.07)">
            <form method="post" action="/admin/capacitaciones/status" style="display:flex;gap:8px;align-items:center">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>" />
              <select name="status" style="min-width:150px">
                <?php foreach (['new'=>'new','contacted'=>'contacted','confirmed'=>'confirmed','cancelled'=>'cancelled'] as $k => $lbl): ?>
                  <option value="<?= htmlspecialchars($k) ?>" <?= ((string)($r['status'] ?? '') === $k) ? 'selected' : '' ?>><?= htmlspecialchars($lbl) ?></option>
                <?php endforeach; ?>
              </select>
              <button class="btn secondary" type="submit">Guardar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$list): ?>
        <tr>
          <td colspan="10" style="padding:14px;color:rgba(246,244,239,0.72)">Sin registros.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>
