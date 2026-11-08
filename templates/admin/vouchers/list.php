<?php
$formasPago = $formasPago ?? [];
$formaPagoLabels = array_column($formasPago, 'nombre', 'codigo');
$voucherDesign = $voucherDesign ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Vouchers de regalo</h4>
        <p class="text-muted small mb-0">Generá vouchers virtuales con código de un solo uso.</p>
    </div>
</div>

<?php if (!empty($generatedVoucher)): ?>
<div class="alert alert-success d-flex flex-wrap justify-content-between align-items-center gap-2">
    <span>Voucher <strong><?= htmlspecialchars($generatedVoucher['codigo']) ?></strong> generado.</span>
    <a class="btn btn-sm btn-success" target="_blank" href="/voucher/<?= rawurlencode((string)$generatedVoucher['codigo']) ?>">Abrir vista previa</a>
</div>
<?php endif; ?>

<div class="card shadow-sm mb-3" id="voucherDesign">
    <div class="card-header bg-white fw-semibold">Diseño del voucher</div>
    <div class="card-body">
        <form method="post" action="/admin/vouchers/diseno" enctype="multipart/form-data" class="row g-2 align-items-end">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="col-lg-4">
                <?php if (!empty($voucherDesign['modelo_imagen'])): ?>
                    <img src="<?= htmlspecialchars(\Perfushopping\Web\Support\Format::uploadUrl((string)$voucherDesign['modelo_imagen'])) ?>" alt="Foto de modelo actual" class="img-fluid rounded mb-2" style="max-height:190px" />
                <?php else: ?>
                    <div class="alert alert-warning small mb-2">Subí una foto real de la modelo (25–30 años, rubia recogida, vestido negro) sosteniendo una bolsa negra y dorada de frente. No uses una imagen con perfume en las manos; el logo original se superpone sobre la bolsa.</div>
                <?php endif; ?>
                <label class="form-label small">Fotografía principal (JPG, PNG o WEBP)</label>
                <input class="form-control form-control-sm" type="file" name="modelo_imagen" accept="image/jpeg,image/png,image/webp" />
            </div>
            <div class="col-lg-8 row g-2">
                <div class="col-md-6">
                    <label class="form-label small">Título</label>
                    <input class="form-control form-control-sm" name="titulo" maxlength="80" value="<?= htmlspecialchars($voucherDesign['titulo'] ?? '¡FELICITACIONES!') ?>" required />
                </div>
                <div class="col-md-6">
                    <label class="form-label small">Cinta</label>
                    <input class="form-control form-control-sm" name="cinta" maxlength="80" value="<?= htmlspecialchars($voucherDesign['cinta'] ?? 'GIFT VOUCHER') ?>" required />
                </div>
                <div class="col-12">
                    <label class="form-label small">Mensaje (separá los párrafos con una línea en blanco)</label>
                    <textarea class="form-control form-control-sm" name="mensaje" rows="4" required><?= htmlspecialchars($voucherDesign['mensaje'] ?? '') ?></textarea>
                </div>
                <div class="col-md-8">
                    <label class="form-label small">Frase inferior</label>
                    <input class="form-control form-control-sm" name="frase_inferior" maxlength="160" value="<?= htmlspecialchars($voucherDesign['frase_inferior'] ?? '¡Te esperamos con mucho cariño!') ?>" required />
                </div>
                <div class="col-md-4 d-grid">
                    <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-palette"></i> Guardar diseño</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold">Generar voucher</div>
    <div class="card-body">
        <form method="post" action="/admin/vouchers/generar" class="row g-2">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <div class="col-md-4">
                <label class="form-label small">Importe del voucher</label>
                <div class="input-group input-group-sm"><span class="input-group-text">$</span><input class="form-control" name="importe" type="number" min="0.01" step="0.01" required /></div>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Sucursal emisora</label>
                <select class="form-select form-select-sm" name="sucursal_id">
                    <option value="">— Seleccionar —</option>
                    <?php foreach ($branches ?? [] as $branch): ?>
                    <option value="<?= (int)$branch['id'] ?>" <?= (int)($branch['id'] ?? 0) === (int)($selectedBranchId ?? 0) ? 'selected' : '' ?>><?= htmlspecialchars($branch['nomsuc'] ?? '') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Cobro inicial</label>
                <select class="form-select form-select-sm" name="forma_pago" id="voucherFormaPago">
                    <?php foreach ($formasPago as $forma): ?>
                    <option value="<?= htmlspecialchars($forma['codigo']) ?>"><?= htmlspecialchars($forma['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4" id="voucherBancoBox" style="display:none">
                <label class="form-label small">Cuenta donde se acredita</label>
                <select class="form-select form-select-sm" name="banco_cuenta_id" id="voucherBanco">
                    <option value="">— Seleccionar cuenta —</option>
                    <?php foreach ($banks ?? [] as $bank): ?>
                    <option value="<?= (int)$bank['id'] ?>"><?= htmlspecialchars(($bank['banco'] ?? 'Banco').' - '.($bank['numero_cuenta'] ?? $bank['cbu'] ?? '')) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small">Nombre de quien recibe</label>
                <input class="form-control form-control-sm" name="destinatario_nombre" maxlength="160" required />
            </div>
            <div class="col-md-4">
                <label class="form-label small">DNI de quien recibe (opcional)</label>
                <input class="form-control form-control-sm" name="destinatario_dni" maxlength="30" />
            </div>
            <div class="col-md-4">
                <label class="form-label small">Teléfono de quien recibe (opcional)</label>
                <input class="form-control form-control-sm" name="destinatario_telefono" type="tel" maxlength="40" />
            </div>
            <div class="col-md-4">
                <label class="form-label small">Nombre de quien envía</label>
                <input class="form-control form-control-sm" name="remitente_nombre" maxlength="160" required />
            </div>
            <div class="col-md-4">
                <label class="form-label small">Teléfono de quien envía</label>
                <input class="form-control form-control-sm" name="remitente_telefono" type="tel" maxlength="40" required />
            </div>
            <div class="col-12 text-end"><button class="btn btn-accent btn-sm"><i class="bi bi-gift"></i> Generar voucher</button></div>
        </form>
    </div>
</div>

<script>
(function(){
    const forma=document.getElementById('voucherFormaPago');
    const box=document.getElementById('voucherBancoBox');
    const banco=document.getElementById('voucherBanco');
    if(!forma || !box || !banco) return;
    const update=function(){
        const requiereCuenta=['transferencia','tarjeta','mercadopago'].includes(forma.value);
        box.style.display=requiereCuenta?'':'none';
        banco.required=requiereCuenta;
        if(!requiereCuenta) banco.value='';
    };
    forma.addEventListener('change',update);
    update();
})();
</script>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold">Vouchers recientes</div>
    <div class="table-responsive">
        <table class="table table-sm table-admin table-hover mb-0">
            <thead><tr><th>Código</th><th>Fecha</th><th>Destinatario</th><th>Remitente</th><th class="text-end">Importe</th><th>Cobrado con</th><th>Estado</th><th>Sucursal</th><th></th></tr></thead>
            <tbody>
            <?php if (empty($vouchers)): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">Todavía no hay vouchers.</td></tr>
            <?php else: foreach ($vouchers as $voucher): ?>
                <tr>
                    <td><code><?= htmlspecialchars($voucher['codigo'] ?? '') ?></code></td>
                    <td><?= htmlspecialchars(substr((string)($voucher['created_at'] ?? ''), 0, 10)) ?></td>
                    <td><?= htmlspecialchars($voucher['destinatario_nombre'] ?? '') ?></td>
                    <td><?= htmlspecialchars($voucher['remitente_nombre'] ?? '') ?></td>
                    <td class="text-end fw-semibold"><?= \Perfushopping\Web\Support\Format::moneyFromCents((int)($voucher['importe_cents'] ?? 0)) ?></td>
                    <td><?= htmlspecialchars($formaPagoLabels[$voucher['forma_pago'] ?? ''] ?? ($voucher['forma_pago'] ?? '')) ?></td>
                    <td><span class="badge bg-<?= ($voucher['estado'] ?? '') === 'activo' ? 'success' : (($voucher['estado'] ?? '') === 'canjeado' ? 'secondary' : 'danger') ?>"><?= htmlspecialchars($voucher['estado'] ?? '') ?></span></td>
                    <td><?= htmlspecialchars($voucher['sucursal_nombre'] ?? '-') ?></td>
                    <td><a class="btn btn-sm btn-outline-primary py-0 px-1" target="_blank" title="Vista previa / descarga" href="/voucher/<?= rawurlencode((string)$voucher['codigo']) ?>"><i class="bi bi-eye"></i></a></td>
                </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>
