<?php
$sucursales = $sucursales ?? [];
$vendedores = $vendedores ?? [];
$csrfToken = $csrf ?? '';
?>
<div class="row justify-content-center" style="margin-top:max(20px, 5vh)">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <h5 class="fw-bold">Iniciar turno</h5>
                    <p class="text-muted small">Seleccioná sucursal, turno y vendedores</p>
                </div>

                <form method="post" action="/admin/sesion/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Sucursal</label>
                        <select class="form-select" name="sucursal_id" required>
                            <option value="">— Seleccionar —</option>
                            <?php foreach ($sucursales as $s): ?>
                                <option value="<?= (int)$s['id'] ?>"><?= htmlspecialchars($s['nomsuc'] ?? 'Sucursal #' . $s['numsuc']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Turno</label>
                        <div class="d-flex gap-3">
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="turno" id="turnoManana" value="manana" required />
                                <label class="form-check-label fw-semibold" for="turnoManana">☀️ Mañana</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="turno" id="turnoTarde" value="tarde" />
                                <label class="form-check-label fw-semibold" for="turnoTarde">🌤️ Tarde</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Vendedores que trabajan este turno</label>
                        <?php if (!$vendedores): ?>
                            <p class="text-muted small">No hay vendedores disponibles.</p>
                        <?php else: ?>
                            <div class="row g-2">
                                <?php foreach ($vendedores as $v): ?>
                                    <div class="col-12 col-sm-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="vendedores[]" value="<?= (int)$v['id'] ?>" id="v_<?= (int)$v['id'] ?>" />
                                            <label class="form-check-label small" for="v_<?= (int)$v['id'] ?>">
                                                <?= htmlspecialchars($v['nombre'] ?? $v['username'] ?? '') ?>
                                                <span class="text-muted">(<?= htmlspecialchars($v['rol'] ?? '') ?>)</span>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <button class="btn btn-accent w-100 py-2 fw-bold" type="submit">
                        <i class="bi bi-play-fill"></i> Iniciar turno
                    </button>
                </form>

                <div class="text-center mt-3">
                    <form method="post" action="/admin/logout" style="display:inline">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                        <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="bi bi-box-arrow-left"></i> Cerrar sesión</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
