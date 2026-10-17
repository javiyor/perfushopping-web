<?php
use Perfushopping\Web\Support\Format;

$factura = $factura ?? [];
$pagos = $pagos ?? [];
$formasPago = $formasPago ?? [];
$formasPagoLabels = $formasPagoLabels ?? [];
$bancos = $bancos ?? [];
$bancosCuentas = $bancosCuentas ?? [];
$tarjetas = $tarjetas ?? [];
$equipos = $equipos ?? [];
$plazos = $plazos ?? [];
$csrfToken = $csrf ?? '';
$solicitudId = (int)($solicitudId ?? 0);
$motivoSolicitud = (string)($motivoSolicitud ?? '');
$puedeAplicarDirectamente = (bool)($puedeAplicarPagosDirectamente ?? false);
$codigosActivos = array_map(static fn(array $f): string => (string)($f['codigo'] ?? ''), $formasPago);
$tarjetaIds = array_map(static fn(array $x): int => (int)($x['idtarje'] ?? 0), $tarjetas);
$equipoIds = array_map(static fn(array $x): int => (int)($x['idequipo'] ?? 0), $equipos);
$bancoCuentaIds = array_map(static fn(array $x): int => (int)($x['id'] ?? 0), $bancosCuentas);
$plazoIds = array_map(static fn(array $x): int => (int)($x['idplazo'] ?? 0), $plazos);
$bancoIds = array_map(static fn(array $x): int => (int)($x['idban'] ?? 0), $bancos);
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= $solicitudId > 0 ? 'Corregir solicitud pendiente' : 'Corregir pagos de factura' ?></h4>
        <p class="text-muted small mb-0">Factura <?= htmlspecialchars((string)($factura['codigo'] ?? '')) ?> · <?= htmlspecialchars((string)($factura['cliente_nombre'] ?? '')) ?></p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/facturas/pagos"><i class="bi bi-arrow-left"></i> Volver a cobranzas</a>
</div>

<div class="alert alert-info small">
    Solo se modifican las formas de pago y sus datos. No se cambian los productos, importes, datos del cliente, fecha ni estado/CAE de la factura.
    <?php if ($puedeAplicarDirectamente): ?>Tu usuario tiene permiso de administración: la corrección se aplicará al guardar, sin autorización adicional.<?php else: ?>Al enviar, la corrección quedará pendiente y otro superadmin deberá aprobarla; hasta entonces la factura y sus impactos contables permanecen sin cambios.<?php endif; ?>
</div>
<?php if ($solicitudId > 0): ?><div class="alert alert-warning small">Estás editando la solicitud #<?= $solicitudId ?>. <?= $puedeAplicarDirectamente ? 'Al guardar se aplicará la corrección y se cerrará la solicitud.' : 'Al guardar se actualizará la misma solicitud, que seguirá pendiente de aprobación.' ?></div><?php endif; ?>

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body py-2"><span class="small text-muted">Comprobante</span><div class="fw-bold"><?= htmlspecialchars((string)($factura['codigo'] ?? '')) ?> · <?= htmlspecialchars((string)($factura['estado'] ?? '')) ?><?= !empty($factura['cae']) ? ' · CAE ' . htmlspecialchars((string)$factura['cae']) : '' ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body py-2"><span class="small text-muted">Total factura (sin modificar)</span><div class="fw-bold"><?= htmlspecialchars(Format::moneyFromCents((int)($factura['total_cents'] ?? 0))) ?></div></div></div></div>
    <div class="col-md-4"><div class="card shadow-sm"><div class="card-body py-2"><span class="small text-muted">Fecha factura</span><div class="fw-bold"><?= htmlspecialchars((string)($factura['fecha'] ?? '')) ?></div></div></div></div>
</div>

<form method="post" action="/admin/facturas/pagos/actualizar">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrfToken) ?>" />
    <input type="hidden" name="factura_id" value="<?= (int)($factura['id'] ?? 0) ?>" />
    <input type="hidden" name="solicitud_id" value="<?= $solicitudId ?>" />
    <?php foreach ($pagos as $i => $p): ?>
        <?php $pagoId = (int)($p['id'] ?? 0); $chequeId = (int)($p['cheque_id'] ?? 0); ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between">
                <span>Pago <?= (int)$i + 1 ?></span>
                <?php if ($chequeId > 0): ?><span class="badge bg-info">Cheque #<?= $chequeId ?></span><?php endif; ?>
            </div>
            <div class="card-body">
                <input type="hidden" name="pagos[<?= (int)$i ?>][id]" value="<?= $pagoId ?>" />
                <div class="row g-2">
                    <div class="col-md-4">
                        <label class="form-label small">Forma de pago</label>
                        <select class="form-select form-select-sm" name="pagos[<?= (int)$i ?>][forma_pago]" required>
                            <?php if (!in_array((string)($p['forma_pago'] ?? ''), $codigosActivos, true)): ?>
                                <option value="<?= htmlspecialchars((string)($p['forma_pago'] ?? '')) ?>" selected><?= htmlspecialchars($formaPagoLabels[$p['forma_pago'] ?? ''] ?? (string)($p['forma_pago'] ?? '')) ?></option>
                            <?php endif; ?>
                            <?php foreach ($formasPago as $fp): ?>
                                <option value="<?= htmlspecialchars((string)$fp['codigo']) ?>" <?= (string)$fp['codigo'] === (string)($p['forma_pago'] ?? '') ? 'selected' : '' ?>><?= htmlspecialchars((string)$fp['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">Importe en pesos</label>
                        <div class="input-group input-group-sm"><span class="input-group-text">$</span><input class="form-control" type="number" min="0.01" step="0.01" name="pagos[<?= (int)$i ?>][monto]" value="<?= number_format((int)($p['monto_cents'] ?? 0) / 100, 2, '.', '') ?>" required /></div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small">N.º cupón (tarjetas)</label>
                        <input class="form-control form-control-sm" name="pagos[<?= (int)$i ?>][cupon_numero]" value="<?= htmlspecialchars((string)($p['cupon_numero'] ?? '')) ?>" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Tarjeta</label>
                        <select class="form-select form-select-sm" name="pagos[<?= (int)$i ?>][tarjeta_id]"><option value="">—</option><?php if (!empty($p['tarjeta_id']) && !in_array((int)$p['tarjeta_id'], $tarjetaIds, true)): ?><option value="<?= (int)$p['tarjeta_id'] ?>" selected><?= htmlspecialchars((string)($p['tarjeta_nombre'] ?? ('Tarjeta #' . $p['tarjeta_id']))) ?></option><?php endif; ?><?php foreach ($tarjetas as $t): ?><option value="<?= (int)$t['idtarje'] ?>" <?= (int)($p['tarjeta_id'] ?? 0) === (int)$t['idtarje'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$t['nomtar']) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Equipo / POS</label>
                        <select class="form-select form-select-sm" name="pagos[<?= (int)$i ?>][equipo_id]"><option value="">—</option><?php if (!empty($p['equipo_id']) && !in_array((int)$p['equipo_id'], $equipoIds, true)): ?><option value="<?= (int)$p['equipo_id'] ?>" selected><?= htmlspecialchars((string)($p['equipo_empresa'] ?? ('Equipo #' . $p['equipo_id']))) ?></option><?php endif; ?><?php foreach ($equipos as $e): ?><option value="<?= (int)$e['idequipo'] ?>" <?= (int)($p['equipo_id'] ?? 0) === (int)$e['idequipo'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($e['empresa'] ?? 'Equipo')) ?> #<?= (int)$e['idequipo'] ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Banco / cuenta</label>
                        <select class="form-select form-select-sm" name="pagos[<?= (int)$i ?>][banco_cuenta_id]"><option value="">—</option><?php if (!empty($p['banco_cuenta_id']) && !in_array((int)$p['banco_cuenta_id'], $bancoCuentaIds, true)): ?><option value="<?= (int)$p['banco_cuenta_id'] ?>" selected><?= htmlspecialchars((string)($p['banco_cuenta_nombre'] ?? ('Cuenta #' . $p['banco_cuenta_id']))) ?></option><?php endif; ?><?php foreach ($bancosCuentas as $bc): ?><option value="<?= (int)$bc['id'] ?>" <?= (int)($p['banco_cuenta_id'] ?? 0) === (int)$bc['id'] ? 'selected' : '' ?>><?= htmlspecialchars((string)($bc['banco'] ?? 'Banco')) ?> · <?= htmlspecialchars((string)($bc['numero_cuenta'] ?? $bc['cbu'] ?? '')) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Plazo / cuotas</label>
                        <select class="form-select form-select-sm" name="pagos[<?= (int)$i ?>][idplazo]"><option value="">—</option><?php if (!empty($p['idplazo']) && !in_array((int)$p['idplazo'], $plazoIds, true)): ?><option value="<?= (int)$p['idplazo'] ?>" selected><?= htmlspecialchars((string)($p['plazo_descripcion'] ?? ('Plazo #' . $p['idplazo']))) ?></option><?php endif; ?><?php foreach ($plazos as $pl): ?><option value="<?= (int)$pl['idplazo'] ?>" <?= (int)($p['idplazo'] ?? 0) === (int)$pl['idplazo'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$pl['descripcion']) ?></option><?php endforeach; ?></select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Banco del cheque</label>
                        <select class="form-select form-select-sm" name="pagos[<?= (int)$i ?>][banco_id]"><option value="">—</option><?php if (!empty($p['banco_id']) && !in_array((int)$p['banco_id'], $bancoIds, true)): ?><option value="<?= (int)$p['banco_id'] ?>" selected><?= htmlspecialchars((string)($p['banco_nombre'] ?? ('Banco #' . $p['banco_id']))) ?></option><?php endif; ?><?php foreach ($bancos as $b): ?><option value="<?= (int)$b['idban'] ?>" <?= (int)($p['banco_id'] ?? 0) === (int)$b['idban'] ? 'selected' : '' ?>><?= htmlspecialchars((string)$b['nombanc']) ?></option><?php endforeach; ?></select>
                    </div>
                    <?php if ($chequeId > 0): ?>
                        <div class="col-md-3"><label class="form-label small">N.º de cheque</label><input class="form-control form-control-sm" name="pagos[<?= (int)$i ?>][cheque_numero]" value="<?= htmlspecialchars((string)($p['numero_cheque'] ?? '')) ?>" /></div>
                        <div class="col-md-3"><label class="form-label small">Titular del cheque</label><input class="form-control form-control-sm" name="pagos[<?= (int)$i ?>][cheque_titular]" value="<?= htmlspecialchars((string)($p['cheque_titular'] ?? '')) ?>" /></div>
                        <div class="col-md-3"><label class="form-label small">CUIT titular</label><input class="form-control form-control-sm" name="pagos[<?= (int)$i ?>][cheque_cuit]" value="<?= htmlspecialchars((string)($p['cheque_cuit'] ?? '')) ?>" /></div>
                        <div class="col-md-3"><label class="form-label small">Vencimiento cheque</label><input class="form-control form-control-sm" type="date" name="pagos[<?= (int)$i ?>][cheque_vencimiento]" value="<?= htmlspecialchars((string)($p['cheque_vto'] ?? '')) ?>" /></div>
                    <?php endif; ?>
                    <div class="col-md-3">
                        <label class="form-label small">Cupón pagado</label>
                        <input class="form-control form-control-sm" type="number" min="0" step="0.01" name="pagos[<?= (int)$i ?>][cupon_monto]" value="<?= number_format((int)($p['cupon_monto_cents'] ?? 0) / 100, 2, '.', '') ?>" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Importe en moneda extranjera</label>
                        <input class="form-control form-control-sm" type="number" min="0" step="0.01" name="pagos[<?= (int)$i ?>][monto_moneda]" value="<?= number_format((int)($p['monto_moneda_cents'] ?? 0) / 100, 2, '.', '') ?>" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small">Cotización</label>
                        <input class="form-control form-control-sm" type="number" min="0" step="0.000001" name="pagos[<?= (int)$i ?>][cotizacion]" value="<?= htmlspecialchars((string)($p['cotizacion'] ?? '')) ?>" />
                    </div>
                </div>
                <?php if ($chequeId > 0): ?>
                    <div class="small text-muted mt-2">Instrumento asociado: <?= htmlspecialchars((string)($p['cheque_banco'] ?? '')) ?> · N.º <?= htmlspecialchars((string)($p['numero_cheque'] ?? '')) ?> · Titular <?= htmlspecialchars((string)($p['cheque_titular'] ?? '')) ?>. El importe corregido también actualizará el registro del cheque.</div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="card shadow-sm mb-3">
        <div class="card-header bg-white fw-semibold"><?= $puedeAplicarDirectamente ? 'Aplicar corrección' : 'Enviar a autorización' ?></div>
        <div class="card-body row g-2">
            <div class="col-12"><label class="form-label small">Motivo de la corrección</label><textarea class="form-control form-control-sm" name="motivo" rows="2" maxlength="500" required placeholder="Explicá qué dato de pago debe corregirse y por qué"><?= htmlspecialchars($motivoSolicitud) ?></textarea></div>
            <div class="col-12"><span class="small text-muted">La suma de los pagos debe coincidir con el total de la factura. <?= $puedeAplicarDirectamente ? 'Los cambios se aplicarán inmediatamente.' : 'Los cambios quedarán pendientes y no tendrán efecto hasta que otro superadmin los apruebe.' ?></span></div>
        </div>
    </div>
    <button class="btn btn-accent" type="submit"><i class="bi bi-<?= $puedeAplicarDirectamente ? 'check-lg' : 'send' ?>"></i> <?= $puedeAplicarDirectamente ? 'Guardar corrección' : ($solicitudId > 0 ? 'Actualizar y reenviar solicitud' : 'Enviar solicitud') ?></button>
</form>
