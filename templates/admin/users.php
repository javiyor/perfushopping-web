<?php
use Perfushopping\Web\Support\Csrf;

$list = $list ?? [];
$q = (string)($q ?? '');
$customerCategories = $customerCategories ?? [];
?>

<div class="page">
  <div style="display:flex;gap:12px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
    <div>
      <h2 style="margin:0 0 8px">Usuarios / roles</h2>
      <p style="margin:0;color:rgba(246,244,239,0.72)">Busca usuarios y cambia si acceden como cliente o admin.</p>
    </div>
    <a class="btn secondary" href="/admin">Volver al admin</a>
  </div>
</div>

<div class="page" style="margin-top:14px">
  <form method="get" action="/admin/users" style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por email, nombre o telefono" style="min-width:280px" />
    <button class="btn" type="submit">Buscar</button>
    <?php if ($q !== ''): ?>
      <a class="btn secondary" href="/admin/users">Limpiar</a>
    <?php endif; ?>
  </form>
</div>

<div class="page" style="margin-top:14px">
  <div style="display:grid;gap:10px">
    <?php if (!$list): ?>
      <div class="notice">No se encontraron usuarios.</div>
    <?php else: ?>
      <?php foreach ($list as $row): ?>
        <?php $isBlocked = !empty($row['disabled_at']); ?>
        <div style="display:grid;gap:12px;border:1px solid rgba(255,255,255,0.08);border-radius:14px;padding:14px<?= $isBlocked ? ';opacity:0.8' : '' ?>">
          <div style="display:flex;gap:10px;justify-content:space-between;align-items:flex-start;flex-wrap:wrap">
            <div>
              <div style="font-weight:800"><?= htmlspecialchars((string)($row['name'] ?? 'Sin nombre')) ?><?= $isBlocked ? ' · Bloqueado' : '' ?></div>
              <div class="meta">#<?= (int)($row['id'] ?? 0) ?> · Mayorista: <?= htmlspecialchars((string)($row['wholesale_status'] ?? '-')) ?> · Categoria: <?= htmlspecialchars((string)($customerCategories[($row['customer_category'] ?? 'none')] ?? 'Sin categoria')) ?> · Alta: <?= htmlspecialchars((string)($row['created_at'] ?? '-')) ?> · Ultimo login: <?= htmlspecialchars((string)($row['last_login_at'] ?? '-')) ?></div>
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
              <form method="post" action="/admin/users/block">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
                <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />
                <button class="btn secondary" type="submit"><?= $isBlocked ? 'Desbloquear' : 'Bloquear' ?></button>
              </form>
              <form method="post" action="/admin/users/delete" onsubmit="return confirm('Eliminar este usuario? Esta accion puede fallar si tiene pedidos u otros registros relacionados.');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
                <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />
                <button class="btn secondary" type="submit">Eliminar</button>
              </form>
            </div>
          </div>
          <form method="post" action="/admin/users/save" style="display:grid;gap:12px">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />
            <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px">
              <div>
                <label>Nombre</label>
                <input name="name" value="<?= htmlspecialchars((string)($row['name'] ?? '')) ?>" required />
              </div>
              <div>
                <label>Email</label>
                <input name="email" value="<?= htmlspecialchars((string)($row['email'] ?? '')) ?>" required />
              </div>
              <div>
                <label>Telefono</label>
                <input name="phone" value="<?= htmlspecialchars((string)($row['phone'] ?? '')) ?>" />
              </div>
              <div>
                <label>Rol</label>
                <select name="role">
                  <option value="customer" <?= (($row['role'] ?? '') === 'customer') ? 'selected' : '' ?>>Cliente</option>
                  <option value="admin" <?= (($row['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
              </div>
              <div>
                <label>Estado mayorista</label>
                <select name="wholesale_status">
                  <option value="none" <?= (($row['wholesale_status'] ?? '') === 'none') ? 'selected' : '' ?>>Sin solicitud</option>
                  <option value="pending" <?= (($row['wholesale_status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pendiente</option>
                  <option value="approved" <?= (($row['wholesale_status'] ?? '') === 'approved') ? 'selected' : '' ?>>Aprobado</option>
                  <option value="rejected" <?= (($row['wholesale_status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rechazado</option>
                </select>
              </div>
              <div>
                <label>Categoria cliente</label>
                <select name="customer_category">
                  <?php foreach ($customerCategories as $value => $label): ?>
                    <option value="<?= htmlspecialchars((string)$value) ?>" <?= (($row['customer_category'] ?? 'none') === $value) ? 'selected' : '' ?>><?= htmlspecialchars((string)$label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div>
              <button class="btn secondary" type="submit">Guardar cambios</button>
            </div>
          </form>
          <form method="post" action="/admin/users/password" style="display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:end">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />
            <div>
              <label>Blanquear / nueva clave</label>
              <input name="new_password" type="password" placeholder="Minimo 8 caracteres" minlength="8" required />
            </div>
            <button class="btn secondary" type="submit">Guardar clave</button>
          </form>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>
