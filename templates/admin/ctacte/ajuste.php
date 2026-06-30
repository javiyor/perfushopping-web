<?php
$clienteId = (int)($clienteId ?? 0);
$csrfToken = $csrf ?? '';

// Get client name
$st = \Perfushopping\Web\Infra\Db::pdo()->prepare('SELECT id, name, email FROM web_users WHERE id = :id LIMIT 1');
$st->execute([':id' => $clienteId]);
$cliente = $st->fetch();
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Ajuste manual</h4>
        <p class="text-muted small"><?= htmlspecialchars($cliente['name'] ?? '') ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/ctacte/<?= $clienteId ?>">Volver</a>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Registrar movimiento</div>
            <div class="card-body">
                <form method="post" action="/admin/ctacte/ajuste/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
                    <input type="hidden" name="cliente_id" value="<?= $clienteId ?>" />

                    <div class="mb-3">
                        <label class="form-label small">Tipo</label>
                        <select class="form-select" name="tipo" required>
                            <option value="debito">Débito (cliente debe)</option>
                            <option value="credito">Crédito (cliente pagó)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Monto</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="monto_cents" type="number" value="0" min="1" required />
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small">Concepto <span class="text-danger">*</span></label>
                        <input class="form-control" name="concepto" required placeholder="Ej: Ajuste por diferencia" />
                    </div>

                    <button class="btn btn-accent w-100" type="submit"><i class="bi bi-check-lg"></i> Registrar</button>
                </form>
            </div>
        </div>
    </div>
</div>
