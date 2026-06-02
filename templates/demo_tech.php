<?php
/** @var string $csrf */
/** @var array<int, array<string,mixed>> $events */
$events = $events ?? [];
?>

<div class="page">
  <h2 style="margin:0 0 8px">Demostracion tecnica (lunes)</h2>
  <p style="margin:0;color:rgba(246,244,239,0.72)">Registro para profesionales peluqueros y clientes. Elegi una fecha lunes y te contactamos para coordinar.</p>
</div>

<div class="page" style="margin-top:14px">
  <div class="two" style="grid-template-columns:1fr 1fr">
    <div>
      <h3 style="margin:0 0 10px">Soy profesional (peluquero/a)</h3>
      <form method="post" action="/eventos/demo-tecnica">
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
    </div>

    <div>
      <h3 style="margin:0 0 10px">Soy cliente (evento demostracion)</h3>
      <form method="post" action="/eventos/demo-tecnica">
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
    </div>
  </div>
</div>

<?php if (!$events): ?>
  <div class="page" style="margin-top:14px">
    <div class="notice danger">Todavia no hay horarios cargados para demos tecnicas. Volve a intentar mas tarde.</div>
  </div>
<?php endif; ?>
