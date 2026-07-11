<?php
/** @var string $csrf */
/** @var array<int, array<string,mixed>> $events */
$events = $events ?? [];
$mode = $mode ?? null;
$isProfessional = $mode === 'pro';
$isClient = $mode === 'client';
?>

<div class="page">
  <h2 style="margin:0 0 8px">Capacitaciones (lunes)</h2>
  <p style="margin:0;color:rgba(246,244,239,0.72)">Elegi el tipo de registro para derivar correctamente a profesionales o clientes y completar el formulario indicado.</p>
  <?php if (($user['role'] ?? '') === 'admin'): ?>
    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:12px">
      <a class="btn secondary" href="/admin/capacitaciones/horarios">Cargar horarios</a>
      <a class="btn secondary" href="/admin/productos">Cargar productos</a>
    </div>
  <?php endif; ?>
</div>

<?php if (!$mode): ?>
  <div class="page" style="margin-top:14px">
    <div class="two" style="grid-template-columns:1fr 1fr">
      <a href="/eventos/capacitaciones/profesionales" style="display:block;text-decoration:none;color:inherit;border:1px solid rgba(255,255,255,0.12);border-radius:18px;padding:22px;background:rgba(255,255,255,0.03)">
        <h3 style="margin:0 0 10px">Soy profesional (peluquero, esteticista, cosmetologa, manicura/o, etc)</h3>
        <p style="margin:0;color:rgba(246,244,239,0.72)">Acceso al formulario pensado para profesionales, salones y equipos tecnicos.</p>
        <span class="btn" style="margin-top:14px">Ir al registro profesional</span>
      </a>
      <a href="/eventos/capacitaciones/clientes" style="display:block;text-decoration:none;color:inherit;border:1px solid rgba(255,255,255,0.12);border-radius:18px;padding:22px;background:rgba(255,255,255,0.03)">
        <h3 style="margin:0 0 10px">Soy cliente (para trabajo tecnico)</h3>
        <p style="margin:0;color:rgba(246,244,239,0.72)">Acceso al formulario para clientes que quieren participar en una capacitacion.</p>
        <span class="btn secondary" style="margin-top:14px">Ir al registro de clientes</span>
      </a>
    </div>
  </div>
<?php else: ?>
  <div class="page" style="margin-top:14px">
    <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between;margin-bottom:14px">
      <div>
        <h3 style="margin:0 0 6px"><?= $isProfessional ? 'Soy profesional (peluquero, esteticista, cosmetologa, manicura/o, etc)' : 'Soy cliente (para trabajo tecnico)' ?></h3>
        <p style="margin:0;color:rgba(246,244,239,0.72)"><?= $isProfessional ? 'Completa tus datos profesionales y elegi el horario de lunes que mejor te quede.' : 'Completa tus datos y elegi el horario de lunes para coordinar la capacitacion.' ?></p>
      </div>
      <a class="btn secondary" href="/eventos/capacitaciones">Cambiar tipo de registro</a>
    </div>

    <?php if ($isProfessional): ?>
      <form method="post" action="/eventos/capacitaciones/profesionales">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
        <input type="hidden" name="kind" value="pro" />
        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;opacity:0" />

        <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Nombre y apellido</label>
        <input name="name" required maxlength="120" placeholder="Tu nombre" />

        <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Salon / peluqueria</label>
        <input name="salon_name" required maxlength="160" placeholder="Nombre del salon" />

        <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Direccion (opcional)</label>
        <input name="salon_address" maxlength="190" placeholder="Calle y numero" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Ciudad</label>
            <input name="city" maxlength="120" placeholder="Ciudad" />
          </div>
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Provincia</label>
            <input name="province" maxlength="80" placeholder="Provincia" />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Telefono (opcional)</label>
            <input name="phone" maxlength="40" placeholder="WhatsApp" />
          </div>
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Email (opcional)</label>
            <input type="email" name="email" maxlength="190" placeholder="correo@..." />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Horario (lunes)</label>
            <select name="event_id" required>
              <option value="">Elegir...</option>
              <?php foreach ($events as $ev): ?>
                <?php
                  $rem = (int)($ev['remaining'] ?? 0);
                  $disabled = $rem <= 0;
                  $label = (string)($ev['monday_date'] ?? '')
                    . ' ' . substr((string)($ev['start_time'] ?? ''), 0, 5)
                    . '-' . substr((string)($ev['end_time'] ?? ''), 0, 5)
                    . ' | ' . (string)($ev['venue_name'] ?? '')
                    . ' | cupos: ' . $rem;
                ?>
                <option value="<?= (int)$ev['id'] ?>" <?= $disabled ? 'disabled' : '' ?>><?= htmlspecialchars($label) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Asistentes (opcional)</label>
            <input type="number" min="1" max="60" name="attendees" placeholder="Ej: 8" />
          </div>
        </div>

        <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Notas (opcional)</label>
        <textarea name="notes" rows="3" maxlength="600" placeholder="Que te gustaria ver / marcas / horarios..."></textarea>

        <button class="btn" type="submit" style="margin-top:12px">Enviar registro</button>
      </form>
    <?php else: ?>
      <form method="post" action="/eventos/capacitaciones/clientes">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
        <input type="hidden" name="kind" value="client" />
        <input type="text" name="website" value="" autocomplete="off" tabindex="-1" style="position:absolute;left:-9999px;opacity:0" />

        <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Nombre y apellido</label>
        <input name="name" required maxlength="120" placeholder="Tu nombre" />

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Telefono (opcional)</label>
            <input name="phone" maxlength="40" placeholder="WhatsApp" />
          </div>
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Email (opcional)</label>
            <input type="email" name="email" maxlength="190" placeholder="correo@..." />
          </div>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-top:10px">
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Ciudad</label>
            <input name="city" maxlength="120" placeholder="Ciudad" />
          </div>
          <div>
            <label style="display:block;margin:0 0 6px;color:rgba(246,244,239,0.72)">Provincia</label>
            <input name="province" maxlength="80" placeholder="Provincia" />
          </div>
        </div>

        <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Fecha (lunes)</label>
        <select name="event_id" required>
          <option value="">Elegir...</option>
          <?php foreach ($events as $ev): ?>
            <?php
              $rem = (int)($ev['remaining'] ?? 0);
              $disabled = $rem <= 0;
              $label = (string)($ev['monday_date'] ?? '')
                . ' ' . substr((string)($ev['start_time'] ?? ''), 0, 5)
                . '-' . substr((string)($ev['end_time'] ?? ''), 0, 5)
                . ' | ' . (string)($ev['venue_name'] ?? '')
                . ' | cupos: ' . $rem;
            ?>
            <option value="<?= (int)$ev['id'] ?>" <?= $disabled ? 'disabled' : '' ?>><?= htmlspecialchars($label) ?></option>
          <?php endforeach; ?>
        </select>

        <label style="display:block;margin:10px 0 6px;color:rgba(246,244,239,0.72)">Notas (opcional)</label>
        <textarea name="notes" rows="3" maxlength="600" placeholder="Que te interesa aprender / tipo de cabello / productos..."></textarea>

        <button class="btn" type="submit" style="margin-top:12px">Enviar registro</button>
      </form>
    <?php endif; ?>
  </div>
<?php endif; ?>

<?php if (!$events): ?>
  <div class="page" style="margin-top:14px">
    <div class="notice danger">Todavia no hay horarios cargados para capacitaciones. Volve a intentar mas tarde.</div>
  </div>
<?php endif; ?>
