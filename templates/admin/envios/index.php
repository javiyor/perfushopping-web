<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Envíos pendientes</h4>
        <p class="text-muted small">Facturas con entrega a domicilio. Efectivo contra entrega queda pendiente hasta cobrar.</p>
    </div>
    <div>
        <a class="btn btn-sm btn-outline-secondary" href="/admin/envios?estado=pendiente">Pendientes</a>
        <a class="btn btn-sm btn-outline-secondary" href="/admin/envios?estado=entregado">Entregados</a>
        <a class="btn btn-sm btn-outline-secondary" href="/admin/envios?estado=cancelado">Cancelados</a>
        <a class="btn btn-sm btn-outline-secondary" href="/admin/envios">Todos pendientes</a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Transporte</th>
                    <th>Dirección</th>
                    <th>Total</th>
                    <th>Pago</th>
                    <th>Estado</th>
                    <th style="width:180px">Acción</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!$list): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">No hay envíos.</td></tr>
            <?php else: foreach ($list as $f): ?>
                <tr>
                    <td><a href="/admin/facturas/<?= (int)$f['id'] ?>"><?= htmlspecialchars($f['codigo'] ?? '') ?></a><div class="small text-muted"><?= htmlspecialchars($f['fecha'] ?? '') ?></div></td>
                    <td><?= htmlspecialchars($f['cliente_nombre'] ?? '') ?><div class="small text-muted"><?= htmlspecialchars($f['cliente_tele'] ?? '') ?></div></td>
                    <td><span class="badge bg-info"><?= htmlspecialchars($f['transporte'] ?? '') ?></span></td>
                    <td class="small" style="max-width:220px"><?= htmlspecialchars($f['envio_direccion'] ?? $f['cliente_direc'] ?? '') ?></td>
                    <td class="fw-semibold"><?= \Perfushopping\Web\Support\Format::moneyRoundedFromCents((int)($f['total_cents'] ?? 0)) ?></td>
                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($f['forma_pago'] ?? '') ?></span></td>
                    <td><span class="badge bg-<?= ($f['envio_estado'] ?? '')==='entregado'?'success':(($f['envio_estado'] ?? '')==='cancelado'?'secondary':'warning') ?>"><?= htmlspecialchars($f['envio_estado'] ?? '') ?></span></td>
                    <td>
                        <?php if (in_array($f['envio_estado'] ?? '', ['pendiente','en_transito'], true)): ?>
                        <form method="post" action="/admin/envios/entregar" style="display:inline" onsubmit="return confirm('¿Confirmar entrega y cobro?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>" />
                            <button class="btn btn-sm btn-accent" type="submit"><i class="bi bi-check-lg"></i> Entregado / Cobrar</button>
                        </form>
                        <form method="post" action="/admin/envios/cancelar" style="display:inline" onsubmit="return confirm('¿Cancelar envío?')">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
                            <input type="hidden" name="id" value="<?= (int)$f['id'] ?>" />
                            <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-x-lg"></i></button>
                        </form>
                        <?php else: ?>
                        <span class="text-muted small">—</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
