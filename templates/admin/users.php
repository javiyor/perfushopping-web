<?php
use Perfushopping\Web\Support\Csrf;

$list = $list ?? [];
$q = (string)($q ?? '');
$customerCategories = $customerCategories ?? [];
?>

<div class="d-flex gap-3 justify-content-between align-items-start flex-wrap mb-3">
  <div>
    <h2 class="fw-bold mb-1">Usuarios / Roles</h2>
    <p class="text-muted mb-0">Buscá usuarios, editá datos y gestioná accesos.</p>
  </div>
  <a class="btn btn-outline-secondary btn-sm" href="/admin"><i class="bi bi-arrow-left"></i> Volver al admin</a>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body">
    <form method="get" action="/admin/users" class="row g-2 align-items-center">
      <div class="col-12 col-md-8 col-lg-6">
        <input class="form-control" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Buscar por email, nombre o teléfono" />
      </div>
      <div class="col-auto d-flex gap-2">
        <button class="btn btn-accent" type="submit"><i class="bi bi-search"></i> Buscar</button>
        <?php if ($q !== ''): ?>
          <a class="btn btn-outline-secondary" href="/admin/users">Limpiar</a>
        <?php endif; ?>
      </div>
    </form>
  </div>
</div>

<?php if (!$list): ?>
  <div class="card shadow-sm">
    <div class="card-body text-muted">No se encontraron usuarios.</div>
  </div>
<?php else: ?>
  <div class="d-grid gap-3">
    <?php foreach ($list as $row): ?>
      <?php $isBlocked = !empty($row['disabled_at']); ?>
      <div class="card shadow-sm <?= $isBlocked ? 'border-warning' : '' ?>">
        <div class="card-body">
          <div class="d-flex gap-2 justify-content-between align-items-start flex-wrap mb-3">
            <div>
              <div class="fw-bold fs-5">
                <?= htmlspecialchars((string)($row['name'] ?? 'Sin nombre')) ?>
                <?php if ($isBlocked): ?>
                  <span class="badge bg-warning text-dark ms-1">Bloqueado</span>
                <?php endif; ?>
              </div>
              <div class="small text-muted">
                #<?= (int)($row['id'] ?? 0) ?> ·
                Mayorista: <?= htmlspecialchars((string)($row['wholesale_status'] ?? '-')) ?> ·
                Categoría: <?= htmlspecialchars((string)($customerCategories[($row['customer_category'] ?? 'none')] ?? 'Sin categoría')) ?> ·
                Alta: <?= htmlspecialchars((string)($row['created_at'] ?? '-')) ?> ·
                Último login: <?= htmlspecialchars((string)($row['last_login_at'] ?? '-')) ?>
              </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
              <form method="post" action="/admin/users/block">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
                <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />
                <button class="btn btn-sm <?= $isBlocked ? 'btn-success' : 'btn-warning' ?>" type="submit">
                  <i class="bi <?= $isBlocked ? 'bi-unlock' : 'bi-lock' ?>"></i> <?= $isBlocked ? 'Desbloquear' : 'Bloquear' ?>
                </button>
              </form>
              <form method="post" action="/admin/users/delete" onsubmit="return confirm('Eliminar este usuario? Esta acción puede fallar si tiene pedidos u otros registros relacionados.');">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
                <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
                <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />
                <button class="btn btn-sm btn-danger" type="submit"><i class="bi bi-trash"></i> Eliminar</button>
              </form>
            </div>
          </div>

          <form method="post" action="/admin/users/save" class="mb-3">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
            <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
            <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />

            <div class="row g-2">
              <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label small fw-semibold mb-1">Nombre</label>
                <input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)($row['name'] ?? '')) ?>" required />
              </div>
              <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label small fw-semibold mb-1">Email</label>
                <input class="form-control form-control-sm" name="email" value="<?= htmlspecialchars((string)($row['email'] ?? '')) ?>" required />
              </div>
              <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label small fw-semibold mb-1">Teléfono</label>
                <input class="form-control form-control-sm" name="phone" value="<?= htmlspecialchars((string)($row['phone'] ?? '')) ?>" />
              </div>

              <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label small fw-semibold mb-1">Rol</label>
                <select class="form-select form-select-sm" name="role">
                  <option value="customer" <?= (($row['role'] ?? '') === 'customer') ? 'selected' : '' ?>>Cliente</option>
                  <option value="admin" <?= (($row['role'] ?? '') === 'admin') ? 'selected' : '' ?>>Admin</option>
                </select>
              </div>
              <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label small fw-semibold mb-1">Estado mayorista</label>
                <select class="form-select form-select-sm" name="wholesale_status">
                  <option value="none" <?= (($row['wholesale_status'] ?? '') === 'none') ? 'selected' : '' ?>>Sin solicitud</option>
                  <option value="pending" <?= (($row['wholesale_status'] ?? '') === 'pending') ? 'selected' : '' ?>>Pendiente</option>
                  <option value="approved" <?= (($row['wholesale_status'] ?? '') === 'approved') ? 'selected' : '' ?>>Aprobado</option>
                  <option value="rejected" <?= (($row['wholesale_status'] ?? '') === 'rejected') ? 'selected' : '' ?>>Rechazado</option>
                </select>
              </div>
              <div class="col-12 col-md-6 col-lg-4">
                <label class="form-label small fw-semibold mb-1">Categoría cliente</label>
                <select class="form-select form-select-sm" name="customer_category">
                  <?php foreach ($customerCategories as $value => $label): ?>
                    <option value="<?= htmlspecialchars((string)$value) ?>" <?= (($row['customer_category'] ?? 'none') === $value) ? 'selected' : '' ?>><?= htmlspecialchars((string)$label) ?></option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="col-12 col-md-8 col-lg-6">
                <label class="form-label small fw-semibold mb-1">Nueva clave (opcional)</label>
                <input class="form-control form-control-sm" name="new_password" type="password" placeholder="Dejar vacío para no cambiar (mín. 8)" minlength="8" />
              </div>
            </div>

            <div class="mt-3 d-flex gap-2">
              <button class="btn btn-accent" type="submit"><i class="bi bi-check2-circle"></i> Guardar cambios</button>
            </div>
          </form>

          <div class="p-2 rounded border bg-light-subtle">
            <form method="post" action="/admin/users/password" class="row g-2 align-items-end">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars(Csrf::token()) ?>" />
              <input type="hidden" name="user_id" value="<?= (int)($row['id'] ?? 0) ?>" />
              <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>" />
              <div class="col-12 col-md-8 col-lg-6">
                <label class="form-label small fw-semibold mb-1">Blanquear / nueva clave (solo clave)</label>
                <input class="form-control form-control-sm" name="new_password" type="password" placeholder="Mínimo 8 caracteres" minlength="8" required />
              </div>
              <div class="col-auto">
                <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-key"></i> Guardar solo clave</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
