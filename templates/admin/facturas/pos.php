<?php
$remitoId = (int)($remitoId ?? 0);
$remitoItems = $remitoItems ?? [];
$presupuestoId = (int)($presupuestoId ?? 0);
$presupuestoItems = $presupuestoItems ?? [];
$csrfToken = $csrf ?? '';
$bancos = $bancos ?? [];
$bancosCuentas = $bancosCuentas ?? [];
$tarjetas = $tarjetas ?? [];
$equipos = $equipos ?? [];
$plazos = $plazos ?? [];
$editarId = (int)($editarId ?? 0);
$editarFactura = is_array($editarFactura ?? null) ? $editarFactura : null;
$editarItems = is_array($editarItems ?? null) ? $editarItems : [];
$editarPagos = is_array($editarPagos ?? null) ? $editarPagos : [];
$editarAsociada = is_array($editarAsociada ?? null) ? $editarAsociada : null;
$edicionPendiente = is_array($edicionPendiente ?? null) ? $edicionPendiente : null;
$ncDeId = (int)($ncDeId ?? 0);
$ncDe = is_array($ncDe ?? null) ? $ncDe : null;
$pedidoId = (int)($pedidoId ?? 0);
$pedidoCodigo = (string)($pedidoCodigo ?? '');
$pedidoItems = is_array($pedidoItems ?? null) ? $pedidoItems : [];
$pedidoCliente = $pedidoCliente ?? null;
$pedidoEnvio = $pedidoEnvio ?? null;
$pedidoPago = $pedidoPago ?? null;
$pedidoDescPct = $pedidoDescPct ?? 0;
?>
<style>
.pos-layout { display:flex; gap:20px; align-items:flex-start; }
.pos-left { flex:1; min-width:0; }
.pos-right { width:400px; flex-shrink:0; position:sticky; top:80px; }

.pos-search-box { position:relative; margin-bottom:16px; }
.pos-search-box input {
    width:100%; padding:8px 12px; font-size:15px; border:2px solid #dee2e6; border-radius:8px;
    outline:none; transition:border-color .15s;
}
.pos-search-box input:focus { border-color:var(--accent); box-shadow:0 0 0 3px rgba(216,178,90,.15); }
.pos-search-box input::placeholder { color:#adb5bd; }

.pos-results {
    position:absolute; z-index:1060; top:100%; left:0; right:0; background:#fff;
    border:1px solid #dee2e6; border-radius:0 0 10px 10px; max-height:320px; overflow-y:auto;
    box-shadow:0 8px 24px rgba(0,0,0,.12);
}
.pos-result-item {
    display:flex; justify-content:space-between; align-items:center;
    padding:10px 14px; cursor:pointer; border-bottom:1px solid #f0f0f0; transition:background .08s;
}
.pos-result-item:hover { background:var(--accent); color:#1a1d23; }
.pos-result-item:last-child { border-bottom:none; }
.pos-result-item .prod-name { font-weight:600; font-size:14px; }
.pos-result-item .prod-code { font-size:12px; color:#6c757d; }
.pos-result-item .prod-price { font-weight:700; font-size:15px; }

.pos-cart { background:#fff; border-radius:12px; box-shadow:0 1px 3px rgba(0,0,0,.06); overflow:hidden; }
.pos-cart-header {
    padding:14px 16px; background:#f8f9fa; font-weight:700; font-size:15px;
    border-bottom:1px solid #e9ecef; display:flex; justify-content:space-between;
}
.pos-cart-items { max-height:400px; overflow-y:auto; }
.pos-cart-item {
    display:flex; align-items:center; gap:10px; padding:10px 14px;
    border-bottom:1px solid #f0f0f0; font-size:14px; transition:background .1s;
}
.pos-cart-item:hover { background:#fcfcfc; }
.pos-cart-item .ci-name { flex:1; min-width:0; }
.pos-cart-item .ci-name .ci-prod { font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.pos-cart-item .ci-name .ci-var { font-size:12px; color:#6c757d; }
.pos-cart-item .ci-qty { width:60px; }
.pos-cart-item .ci-qty input { width:100%; text-align:center; padding:4px; border:1px solid #dee2e6; border-radius:6px; font-weight:600; }
.pos-cart-item .ci-dto { width:64px; }
.pos-cart-item .ci-dto input { width:100%; text-align:center; padding:4px 2px; border:1px solid #dee2e6; border-radius:6px; font-size:12px; }
.pos-cart-item .ci-price { width:100px; text-align:right; font-weight:600; }
.pos-cart-item .ci-total { width:100px; text-align:right; font-weight:700; }
.pos-cart-item .ci-del { width:30px; text-align:center; color:#dc3545; cursor:pointer; font-size:18px; opacity:.5; }
.pos-cart-item .ci-del:hover { opacity:1; }
.pos-cart-cols {
    display:flex; align-items:center; gap:10px; padding:8px 14px;
    font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#6c757d;
    border-bottom:1px solid #e9ecef; background:#fff; font-weight:600;
}
.pos-cart-cols .ci-name { flex:1; min-width:0; }
.pos-cart-cols .ci-qty { width:60px; text-align:center; }
.pos-cart-cols .ci-dto { width:64px; text-align:center; }
.pos-cart-cols .ci-price { width:100px; text-align:right; }
.pos-cart-cols .ci-total { width:100px; text-align:right; }
.pos-cart-cols .ci-del { width:30px; }

.pos-totals { padding:14px 16px; border-top:2px solid #e9ecef; }
.pos-totals .pt-row { display:flex; justify-content:space-between; padding:2px 0; font-size:14px; }
.pos-totals .pt-row.pt-total { font-size:22px; font-weight:800; border-top:2px solid #1a1d23; margin-top:6px; padding-top:8px; }

.pos-toolbar { display:flex; gap:10px; align-items:center; margin-bottom:16px; flex-wrap:wrap; }
.pos-toolbar select, .pos-toolbar .form-control-sm { font-size:14px; }
.pos-cliente { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
.pos-cliente input { min-width:180px; }

@media (max-width: 768px) {
    .pos-layout { flex-direction:column; }
    .pos-right { width:100%; position:static; }
    .pos-search-box input { font-size:16px; padding:12px; }
    .pos-cart-item { gap:6px; padding:10px 10px; font-size:13px; }
    .pos-cart-item .ci-price { width:70px; }
    .pos-cart-item .ci-total { width:70px; }
    .pos-cart-item .ci-qty { width:50px; }
    .pos-cart-item .ci-dto { width:56px; }
    .pos-totals .pt-row.pt-total { font-size:18px; }
    .pos-toolbar { flex-direction:column; align-items:stretch; }
    .pos-toolbar select { width:100% !important; }
    .pos-cliente { flex-direction:column; align-items:stretch; }
    .pos-cliente input { width:100% !important; min-width:0; }
    .pos-cliente .btn { width:100%; }
    #clienteSection { margin-bottom:8px !important; }
    #clienteSection input { width:100% !important; }
}
@media (max-width: 480px) {
    .pos-cart-cols { display:none !important; }
    .pos-cart-item { flex-wrap:wrap; gap:4px; }
    .pos-cart-item .ci-name { width:100%; }
    .pos-cart-item .ci-qty { width:40px; }
    .pos-cart-item .ci-dto { width:auto; }
    .pos-cart-item .ci-price { width:auto; }
    .pos-cart-item .ci-total { width:auto; }
}
</style>

<div class="d-flex justify-content-between align-items-start mb-2">
    <div>
        <h4 class="fw-bold mb-0"><?= $editarId > 0 ? 'Editar comprobante' : 'Nueva factura' ?></h4>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/facturas/comprobantes"><i class="bi bi-list-ul"></i> Comprobantes emitidos</a>
</div>

<div class="pos-toolbar" style="margin-bottom:8px">
    <select class="form-select form-select-sm" id="tipoComprobante" style="width:auto">
        <option value="FACT-B">Factura B</option>
        <option value="FACT-A">Factura A</option>
        <option value="FACT-C">Factura C</option>
        <option value="NC">Nota de Crédito</option>
        <option value="ND">Nota de Débito</option>
    </select>
    <div id="asociadaWrap" style="display:none;position:relative">
        <input type="hidden" id="comprobanteAsociadoId" value="0" />
        <input class="form-control form-control-sm" id="asociadaSearch" placeholder="Buscar factura asociada..." autocomplete="off" style="width:270px" />
        <div id="asociadaSuggestions" style="position:absolute;z-index:1050;width:100%"></div>
        <div id="asociadaSel" class="mt-1" style="display:none"></div>
    </div>

    <?php $vendedores = $vendedores ?? []; if ($vendedores): ?>
    <div class="pos-cliente">
        <span class="text-muted small">Vendedor:</span>
        <select class="form-select form-select-sm" id="vendedorId" style="width:auto">
            <option value="0">— Seleccionar —</option>
            <?php foreach ($vendedores as $v): ?>
            <option value="<?= (int)$v['id'] ?>"><?= htmlspecialchars($v['nombre'] ?? $v['username'] ?? '') ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php endif; ?>

    <?php if ($remitoId > 0): ?>
    <span class="badge bg-info fs-6">Remito cargado</span>
    <input type="hidden" id="remitoId" value="<?= $remitoId ?>" />
    <?php else: ?>
    <input type="hidden" id="remitoId" value="0" />
    <button class="btn btn-sm btn-outline-primary" type="button" onclick="document.getElementById('remitoSearchWrap').style.display='block'">
        <i class="bi bi-link"></i> Desde remito
    </button>
    <div id="remitoSearchWrap" style="display:none;position:relative">
        <input class="form-control form-control-sm" id="remitoSearch" placeholder="Buscar remito completado..." autocomplete="off" style="width:250px" />
        <div id="remitoSuggestions" style="position:absolute;z-index:1050;width:100%"></div>
    </div>
    <?php endif; ?>

    <?php if ($presupuestoId > 0): ?>
    <span class="badge bg-warning fs-6">Presupuesto cargado</span>
    <input type="hidden" id="presupuestoId" value="<?= $presupuestoId ?>" />
    <?php else: ?>
    <input type="hidden" id="presupuestoId" value="0" />
    <button class="btn btn-sm btn-outline-warning" type="button" onclick="document.getElementById('presupuestoSearchWrap').style.display='block'">
        <i class="bi bi-file-text"></i> Desde presupuesto
    </button>
    <div id="presupuestoSearchWrap" style="display:none;position:relative">
        <input class="form-control form-control-sm" id="presupuestoSearch" placeholder="Buscar presupuesto aprobado..." autocomplete="off" style="width:250px" />
        <div id="presupuestoSuggestions" style="position:absolute;z-index:1050;width:100%"></div>
    </div>
    <?php endif; ?>

    <?php $pedidoId = (int)($pedidoId ?? 0); ?>
    <?php if ($pedidoId > 0): ?>
    <span class="badge bg-success fs-6">Pedido web <?= htmlspecialchars($pedidoCodigo ?? '') ?> cargado</span>
    <input type="hidden" id="pedidoId" value="<?= $pedidoId ?>" />
    <?php else: ?>
        <input type="hidden" id="pedidoId" value="0" />
    <button class="btn btn-sm btn-outline-success" type="button" onclick="document.getElementById('pedidoSearchWrap').style.display='block'">
        <i class="bi bi-cart"></i> Desde pedido web
    </button>
    <div id="pedidoSearchWrap" style="display:none;position:relative">
        <?php $pends = $pedidosPendientes ?? []; ?>
        <select class="form-select form-select-sm mb-1" id="pedidoPendSelect" style="width:320px" onchange="if(this.value)window.location.href='/admin/facturas/nueva?pedido_id='+this.value">
            <option value="">— Pagados sin facturar (<?= count($pends) ?>) —</option>
            <?php foreach ($pends as $pp): ?>
            <option value="<?= (int)$pp['id'] ?>"><?= htmlspecialchars((string)($pp['order_code'] ?? '')) ?> — <?= htmlspecialchars((string)($pp['ship_name'] ?? '')) ?> — $<?= number_format((int)($pp['total_cents'] ?? 0) / 100, 2, ',', '.') ?></option>
            <?php endforeach; ?>
        </select>
        <input class="form-control form-control-sm" id="pedidoSearch" placeholder="...o buscar otro pedido" autocomplete="off" style="width:320px" />
        <div id="pedidoSuggestions" style="position:absolute;z-index:1050;width:100%"></div>
    </div>
    <?php endif; ?>

<?php if ($editarId > 0): ?>
<span class="badge bg-warning text-dark fs-6"><i class="bi bi-pencil"></i> Editando <?= htmlspecialchars((string)($editarFactura['codigo'] ?? '')) ?></span>
<?php endif; ?>
</div>

<?php if ($editarId > 0): ?>
<div class="alert alert-warning small py-2">
    <?php if ($edicionPendiente): ?>
        Esta factura ya tiene la solicitud #<?= (int)$edicionPendiente['id'] ?> en estado <strong><?= htmlspecialchars((string)$edicionPendiente['status']) ?></strong>. No se puede enviar otra edición hasta resolverla.
    <?php else: ?>
        Al guardar, la edición quedará pendiente hasta que otro administrador la apruebe. La factura no cambiará mientras tanto.
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="pos-cliente mb-2" id="clienteSection">
    <span class="text-muted small">Cliente:</span>
    <input class="form-control form-control-sm" id="clienteSearch" placeholder="Buscar o CF" autocomplete="off" style="width:200px" />
    <input type="hidden" id="clienteId" value="0" />
    <input type="hidden" id="clienteErpId" value="0" />
    <span id="clienteNombre" class="fw-semibold small">Consumidor Final</span>
    <span id="clienteCatBadge" class="badge bg-warning text-dark" style="display:none;font-size:10px">Precios mayoristas</span>
    <span id="clienteInfo" class="text-muted small"></span>
    <span id="clienteCuit" class="d-none"></span>
    <input type="hidden" id="clienteCondIva" value="consumidor_final" />
    <input type="hidden" id="emisorIva" value="<?= htmlspecialchars($emisorIva ?? '') ?>" />
    <button class="btn btn-sm btn-outline-secondary" type="button" onclick="clearCliente()" title="Consumidor Final"><i class="bi bi-person-x"></i></button>
    <button class="btn btn-sm btn-outline-success" type="button" data-bs-toggle="modal" data-bs-target="#nuevoClienteModal" title="Cargar nuevo cliente"><i class="bi bi-person-plus"></i> Nuevo</button>
</div>
<div id="clienteSuggestions" style="position:relative"></div>

<!-- Nuevo cliente modal -->
<div class="modal fade" id="nuevoClienteModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-plus"></i> Nuevo cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Nombre / Razón social *</label>
                    <input type="text" class="form-control form-control-sm" id="ncRazon" autocomplete="off" />
                </div>
                <div class="mb-2">
                    <label class="form-label small">CUIT</label>
                    <input type="text" class="form-control form-control-sm" id="ncCuit" maxlength="11" autocomplete="off" placeholder="11 dígitos" />
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small">Teléfono</label>
                        <input type="text" class="form-control form-control-sm" id="ncTele" autocomplete="off" />
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Email</label>
                        <input type="text" class="form-control form-control-sm" id="ncMail" autocomplete="off" />
                    </div>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small">Dirección</label>
                        <input type="text" class="form-control form-control-sm" id="ncDirec" autocomplete="off" />
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Localidad</label>
                        <input type="text" class="form-control form-control-sm" id="ncLocalidad" autocomplete="off" />
                    </div>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Condición frente al IVA</label>
                    <select class="form-select form-select-sm" id="ncCondIva">
                        <option value="consumidor_final">Consumidor Final</option>
                        <option value="monotributista">Monotributista</option>
                        <option value="responsable_inscripto">Responsable Inscripto</option>
                        <option value="exento">Exento</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Categoría</label>
                    <select class="form-select form-select-sm" id="ncCategoria" onchange="toggleNcCategoria()">
                        <option value="minorista">Minorista</option>
                        <option value="mayorista">Mayorista</option>
                        <option value="profesional">Profesional</option>
                    </select>
                </div>
                <div id="ncMayoristaBox" style="display:none">
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="ncPrecioMayorista" value="1" />
                        <label class="form-check-label small" for="ncPrecioMayorista">Aplica precios mayoristas</label>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Especialidad</label>
                        <input type="text" class="form-control form-control-sm" id="ncEspecialidad" list="ncEspecialidades" autocomplete="off" placeholder="Ej: peluquería, barbería..." />
                        <datalist id="ncEspecialidades">
                            <option value="Peluquería"></option>
                            <option value="Barbería"></option>
                            <option value="Masajes"></option>
                            <option value="Cosmetología"></option>
                            <option value="Manicuría"></option>
                            <option value="Estética"></option>
                            <option value="Maquillaje"></option>
                            <option value="Depilación"></option>
                        </datalist>
                    </div>
                </div>
                <div id="ncError" class="alert alert-danger small py-2" style="display:none"></div>
                <div id="ncDup" class="alert alert-warning small py-2 mb-0" style="display:none">
                    <div class="fw-semibold mb-1"><i class="bi bi-exclamation-triangle-fill"></i> Se parecen a clientes ya cargados:</div>
                    <div id="ncDupList" class="mb-2"></div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="guardarNuevoCliente(true)"><i class="bi bi-plus-lg"></i> Crear de todos modos</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-accent" id="ncSaveBtn" onclick="guardarNuevoCliente()"><i class="bi bi-check-lg"></i> Guardar y usar</button>
            </div>
        </div>
    </div>
</div>

<div class="pos-layout">
    <div class="pos-left">
        <div class="pos-search-box">
            <div class="d-flex gap-1">
                <input id="productSearch" placeholder="🔍 Buscar producto por nombre, código o ID..." autofocus class="flex-fill" style="flex:1" />
                <button class="btn btn-sm btn-outline-secondary" type="button" id="btnScanCam" title="Escanear código de barras con la cámara"><i class="bi bi-camera"></i></button>
            </div>
            <div id="scanReader" style="display:none;max-width:300px;margin-top:4px"></div>
            <div class="pos-results" id="productResults" style="display:none"></div>
        </div>

        <div class="pos-cart">
            <div class="pos-cart-header">
                <span>Carrito</span>
                <span id="cartCount">0 items</span>
            </div>
            <div class="pos-cart-cols" id="cartCols" style="display:none">
                <div class="ci-name">Producto</div>
                <div class="ci-qty">Cant.</div>
                <div class="ci-dto">Dto. %</div>
                <div class="ci-price">Precio</div>
                <div class="ci-total">Total</div>
                <div class="ci-del"></div>
            </div>
            <div class="pos-cart-items" id="cartItems">
                <div class="text-muted text-center py-4 small">Buscá productos para agregar al carrito</div>
            </div>
            <div class="pos-totals">
                <div class="pt-row" id="posSubtotalRow"><span>Subtotal</span><span id="posSubtotal">$0</span></div>
                <div class="pt-row" id="posIvaRow"><span>IVA</span><span id="posIva">$0</span></div>
                <div class="pt-row">
                    <span>Dto. %</span>
                    <span><input type="number" id="posDescuento" value="0" min="0" max="100" step="any" style="width:60px;text-align:right;font-size:14px;border:1px solid #ccc;border-radius:4px;padding:2px 4px" onchange="recalcTotals()" />%</span>
                </div>
                <div class="pt-row" id="posPuntosRow" style="display:none">
                    <span>Puntos a canjear (1 pto = $1)</span>
                    <span><input type="number" id="posPuntosUsar" value="0" min="0" step="1" style="width:80px;text-align:right;font-size:14px;border:1px solid #ccc;border-radius:4px;padding:2px 4px" onchange="recalcTotals()" /></span>
                </div>
                <div class="pt-row" id="posPuntosSaldo" style="display:none;font-size:12px;color:#6c757d"><span>Saldo disponible</span><span><span id="posPuntosSaldoVal">0</span> <button type="button" class="btn btn-outline-warning btn-sm" style="padding:1px 7px;font-size:11px;line-height:1.4;vertical-align:middle" onclick="canjearPuntos()">Canjear puntos</button></span></div>
                <div class="pt-row" id="posRedondeoRow" style="display:none"><span>Redondeo al peso</span><span id="posRedondeo"></span></div>
                <div class="pt-row pt-total"><span>TOTAL</span><span id="posTotal">$0</span></div>
            </div>
        </div>
    </div>

    <div class="pos-right">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Entrega</div>
            <div class="card-body">
                <div class="d-flex gap-2 mb-2">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="entrega_tipo" id="entregaLocal" value="local" checked onchange="onEntregaChange()">
                        <label class="form-check-label small fw-semibold" for="entregaLocal">Retiro en local</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="entrega_tipo" id="entregaEnvio" value="envio" onchange="onEntregaChange()">
                        <label class="form-check-label small fw-semibold" for="entregaEnvio">Envío</label>
                    </div>
                </div>
                <div id="envioFields" style="display:none">
                    <div class="mb-2">
                        <label class="form-label small">Transporte</label>
                        <select class="form-select form-select-sm" id="transporteSelect">
                            <option value="propio">Transporte propio</option>
                            <option value="delivery">Delivery</option>
                            <option value="correo_argentino">Correo Argentino</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small">Domicilio de entrega</label>
                        <input class="form-control form-control-sm" id="envioDireccion" placeholder="Calle, altura, localidad" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label small">Obs. envío</label>
                        <input class="form-control form-control-sm" id="envioObs" placeholder="Horario, referencia..." />
                    </div>
                    <div class="alert alert-info py-1 px-2 small mt-2" id="envioCajaHint"></div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
                <span><i class="bi bi-cash-stack"></i> Formas de pago</span>
                <button class="btn btn-sm btn-outline-primary" type="button" onclick="addPagoLine()"><i class="bi bi-plus-lg"></i> Agregar</button>
            </div>
            <div class="card-body">
                <div id="pagosContainer"></div>
                <div id="ctaCtePermisoHint" class="small text-muted mt-2">La cuenta corriente solo está disponible para clientes habilitados por un admin.</div>
                <div class="d-flex justify-content-between align-items-center small mt-2 pt-2 border-top">
                    <span class="text-muted">Pagado:</span>
                    <strong id="pagosTotal">$0,00</strong>
                </div>
                <div class="d-flex justify-content-between align-items-center small">
                    <span class="text-muted">Vuelto:</span>
                    <strong id="vueltoDisplay">$0,00</strong>
                </div>
            </div>
        </div>

        <?php if ($editarId > 0): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Motivo de la autorización</div>
            <div class="card-body">
                <textarea class="form-control form-control-sm" id="motivoAutorizacionEdicion" rows="2" maxlength="500" required placeholder="Explicá por qué se solicita modificar esta factura"></textarea>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Notas</div>
            <div class="card-body">
                <textarea class="form-control form-control-sm" id="facturaNotas" rows="2" placeholder="Observaciones..."></textarea>
            </div>
        </div>

        <input type="hidden" id="csrfToken" value="<?= htmlspecialchars($csrfToken) ?>" />

        <button class="btn btn-accent w-100 py-3 fw-bold fs-5" id="btnFacturar" onclick="submitFactura()" <?= $edicionPendiente ? 'disabled' : '' ?>>
            <i class="bi bi-<?= $editarId > 0 ? 'pencil' : 'receipt' ?>"></i> <?= $editarId > 0 ? 'GUARDAR CAMBIOS' : 'FACTURAR' ?>
        </button>
    </div>
</div>

<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// ── State ──
let cart = [];
let modoEdicionFactura = <?= (int)($editarId ?? 0) > 0 ? 'true' : 'false' ?>;
let ctaCteHabilitada = false;
let ctaCteCheckSequence = 0;
// Mientras la factura está abierta (POS), el layout NO recarga la página
// al actualizar el service worker: espera la acción del usuario.
window.__facturaEnProceso = function() { return true; };
let productSearchTimer;
let productSearchSequence = 0;
let productInputRevision = 0;
let productSearchAbort = null;
let explicitProductSearchQueue = Promise.resolve();
let html5Scanner = null;
let isScanning = false;
let cameraScanPending = false;

// ── Barcode scanner via camera ──
document.getElementById('btnScanCam').addEventListener('click', function() {
    const reader = document.getElementById('scanReader');
    if (isScanning) {
        if (html5Scanner) { html5Scanner.stop().catch(()=>{}); html5Scanner.clear(); }
        reader.style.display = 'none';
        isScanning = false;
        cameraScanPending = false;
        return;
    }
    reader.style.display = 'block';
    cameraScanPending = false;
    if (!html5Scanner) {
        html5Scanner = new Html5Qrcode('scanReader');
    }
    html5Scanner.start(
        { facingMode: 'environment' },
        { fps: 15, qrbox: { width: 250, height: 100 } },
        function(decodedText) {
            if (cameraScanPending) return;
            cameraScanPending = true;
            html5Scanner.stop().catch(()=>{});
            reader.style.display = 'none';
            isScanning = false;
            const input = document.getElementById('productSearch');
            input.value = String(decodedText);
            queueCompletedProductSearch(input.value.trim());
        },
        function() {}
    ).then(() => {
        isScanning = true;
    }).catch(err => {
        cameraScanPending = false;
        alert('Error al acceder a la cámara: ' + err);
        reader.style.display = 'none';
    });
});

function fmtPrice(cents) {
    return (cents / 100).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function redondearTotalFacturaCents(montoCents, tipoComprobante) {
    const monto = Math.max(0, Math.round(Number(montoCents) || 0));
    if (tipoComprobante === 'NC' || monto === 0) return {total: monto, redondeo: 0};
    const total = Math.floor((monto + 50) / 100) * 100;
    return {total: total, redondeo: total - monto};
}

function fmtPesosEnteros(cents) {
    return Math.round((Number(cents) || 0) / 100).toLocaleString('es-AR', {maximumFractionDigits: 0});
}

function getCondIva() {
    return document.getElementById('clienteCondIva').value || 'consumidor_final';
}

function displayPriceIsGross() {
    return getCondIva() !== 'responsable_inscripto';
}

function getDisplayPrice(netCents, ivaRate) {
    if (displayPriceIsGross()) {
        return netCents + Math.round(netCents * ivaRate / 100);
    }
    return netCents;
}

// Precios mayoristas: si el cliente es mayorista, o profesional con flag,
// se usa precio1 (cuando existe) en lugar del precio normal.
var clienteMayorista = false;

function precioVigenteCents(p) {
    const normal = p.precio ? Math.round(parseFloat(p.precio) * 100) : 0;
    const mayor = p.precio1 ? Math.round(parseFloat(p.precio1) * 100) : 0;
    if (clienteMayorista && mayor > 0) return mayor;
    return normal;
}

function precioActivo(item) {
    if (clienteMayorista && (item.precio1_cents || 0) > 0) return item.precio1_cents;
    return item.net_price_cents || 0;
}

function promoPagoCalificado() {
    if (modoEdicionFactura) return false;
    const lines = Array.from(document.querySelectorAll('#pagosContainer .pago-line'));
    let hayFormaElegible = false;
    for (const line of lines) {
        const forma = line.querySelector('.fp-forma')?.value || '';
        const cents = Math.round((parseFloat(line.querySelector('.fp-monto')?.value) || 0) * 100);
        const formaElegible = forma === 'efectivo' || forma === 'transferencia';
        if (formaElegible) hayFormaElegible = true;
        else if (cents > 0) return false;
    }
    return hayFormaElegible;
}

function promoNetCents(item) {
    const gross = Math.max(0, parseInt(item.promo_pos_final_cents) || 0);
    const iva = Math.max(0, Number(item.iva_rate) || 0);
    if (!gross || iva <= 0) return gross;
    const estimate = Math.max(0, Math.round(gross / (1 + iva / 100)));
    let best = estimate, bestDiff = Infinity;
    for (let net = Math.max(0, estimate - 3); net <= estimate + 3; net++) {
        const final = net + Math.round(net * iva / 100);
        const diff = Math.abs(final - gross);
        if (diff < bestDiff) { best = net; bestDiff = diff; }
        if (diff === 0) return net;
    }
    return best;
}

function promoUnitsFor(item, index) {
    if (!promoPagoCalificado() || !item.promo_pos_version) return 0;
    const regularNet = Math.max(0, precioActivo(item));
    const regularFinal = regularNet + Math.round(regularNet * Math.max(0, Number(item.iva_rate) || 0) / 100);
    if (Number(item.promo_pos_final_cents || 0) >= regularFinal) return 0;
    const remainingRaw = item.promo_pos_remaining;
    const totalRemaining = remainingRaw === null || remainingRaw === undefined ? Infinity : Math.max(0, parseInt(remainingRaw) || 0);
    let alreadyUsed = 0;
    for (let i = 0; i < index && alreadyUsed < totalRemaining; i++) {
        const previous = cart[i];
        if ((previous.idprodu || 0) !== (item.idprodu || 0) || previous.promo_pos_version !== item.promo_pos_version) continue;
        alreadyUsed += Math.min(Math.max(0, parseInt(previous.qty) || 0), totalRemaining - alreadyUsed);
    }
    return Math.min(Math.max(0, parseInt(item.qty) || 0), Math.max(0, totalRemaining - alreadyUsed));
}

function lineAmounts(item, index) {
    const qty = Math.max(1, parseInt(item.qty) || 1);
    const promoQty = promoUnitsFor(item, index);
    const regularQty = qty - promoQty;
    const regularNet = Math.max(0, precioActivo(item));
    const promoNet = promoQty > 0 ? promoNetCents(item) : 0;
    const discount = 1 - lineDto(item.dto) / 100;
    const promoLineNet = promoQty > 0 ? Math.round(promoQty * promoNet * discount) : 0;
    const regularLineNet = regularQty > 0 ? Math.round(regularQty * regularNet * discount) : 0;
    const promoLineIva = item.iva_rate > 0 && promoQty > 0 ? Math.round(promoLineNet * item.iva_rate / 100) : 0;
    const regularLineIva = item.iva_rate > 0 && regularQty > 0 ? Math.round(regularLineNet * item.iva_rate / 100) : 0;
    return {net: promoLineNet + regularLineNet, iva: promoLineIva + regularLineIva};
}

function precioCarritoLabel(item, index) {
    const regular = getDisplayPrice(precioActivo(item), item.iva_rate);
    const promoQty = promoUnitsFor(item, index);
    if (promoQty <= 0) return '$' + fmtPrice(regular);
    const promo = displayPriceIsGross() ? Number(item.promo_pos_final_cents) : getDisplayPrice(promoNetCents(item), item.iva_rate);
    if (promoQty >= item.qty) return '$' + fmtPrice(promo) + ' promo';
    return '$' + fmtPrice(promo) + ' promo (' + promoQty + ') · $' + fmtPrice(regular) + ' resto';
}

function actualizarPromoEnCarrito() {
    if (cart.length) renderCart(); else recalcTotals();
}

function actualizarBadgeMayorista() {
    const badge = document.getElementById('clienteCatBadge');
    if (badge) badge.style.display = clienteMayorista ? '' : 'none';
}

// ── Load remito items if present ──
<?php if ($remitoItems): ?>
<?php foreach ($remitoItems as $ri): ?>
addToCart({
    idprodu: <?= (int)($ri['idprodu'] ?? 0) ?>,
    idcodgusto: <?= (int)($ri['idcodgusto'] ?? 0) ?>,
    producto: '<?= htmlspecialchars($ri['producto'] ?? '', ENT_QUOTES) ?>',
    variedad: '<?= htmlspecialchars($ri['variedad'] ?? '', ENT_QUOTES) ?>',
    qty: <?= (int)($ri['qty'] ?? 1) ?>,
    unit_price_cents: <?= (int)round((float)($ri['precio'] ?? 0) * 100) ?>,
    iva_rate: <?= (float)($ri['tiva'] ?? 21) ?>,
});
<?php endforeach; ?>
<?php endif; ?>

<?php if ($presupuestoItems): ?>
<?php foreach ($presupuestoItems as $pi): ?>
addToCart({
    idprodu: <?= (int)($pi['idprodu'] ?? 0) ?>,
    idcodgusto: <?= (int)($pi['idcodgusto'] ?? 0) ?>,
    producto: '<?= htmlspecialchars($pi['producto'] ?? '', ENT_QUOTES) ?>',
    variedad: '<?= htmlspecialchars($pi['variedad'] ?? '', ENT_QUOTES) ?>',
    qty: <?= (int)($pi['qty'] ?? 1) ?>,
    unit_price_cents: <?= (int)round((float)($pi['precio'] ?? 0) * 100) ?>,
    iva_rate: <?= (float)($pi['tiva'] ?? 21) ?>,
});
<?php endforeach; ?>
<?php endif; ?>

// ── Product search ──
const prodInput = document.getElementById('productSearch');
const prodResults = document.getElementById('productResults');

prodInput.addEventListener('input', function() {
    const revision = ++productInputRevision;
    const sequence = ++productSearchSequence;
    clearTimeout(productSearchTimer);
    if (productSearchAbort) productSearchAbort.abort();
    prodResults.style.display = 'none';
    prodResults.innerHTML = '';
    const val = this.value.trim();
    if (val.length < 2) { prodResults.style.display = 'none'; return; }
    productSearchTimer = setTimeout(() => searchProd(val, false, sequence, revision), 250);
});

prodInput.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') { prodResults.style.display = 'none'; this.blur(); }
    if (e.key === 'Enter') {
        e.preventDefault();
        e.stopPropagation();
        const val = this.value.trim();
        if (val !== '') queueCompletedProductSearch(val);
    }
});

function cancelProductSearch() {
    clearTimeout(productSearchTimer);
    productSearchSequence++;
    if (productSearchAbort) {
        productSearchAbort.abort();
        productSearchAbort = null;
    }
}

function clearProductSearchInput() {
    prodInput.value = '';
    productInputRevision++;
    prodInput.focus();
}

function queueCompletedProductSearch(q) {
    if (!q) return;
    cancelProductSearch();
    clearProductSearchInput();
    const revision = productInputRevision;
    prodResults.style.display = 'none';
    explicitProductSearchQueue = explicitProductSearchQueue
        .then(() => searchProd(q, true, null, revision))
        .catch(error => console.error('POS barcode search failed:', error));
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.pos-search-box')) {
        prodResults.style.display = 'none';
        cancelProductSearch();
    }
});

// Semáforo de fecha de compra: >90 días rojo, >60 amarillo, dentro verde.
function fecompraBadge(fecha) {
    if (!fecha) return '<span class="text-muted">F.Comp: —</span>';
    const d = new Date(String(fecha).substring(0, 10) + 'T00:00:00');
    if (isNaN(d.getTime())) return '<span class="text-muted">F.Comp: —</span>';
    const days = Math.floor((Date.now() - d.getTime()) / 86400000);
    const cls = days > 90 ? 'text-danger fw-bold' : (days > 60 ? 'text-warning fw-bold' : 'text-success');
    const txt = String(fecha).substring(0, 10).split('-').reverse().join('/');
    return '<span class="' + cls + '">F.Comp: ' + esc(txt) + '</span>';
}

function searchProd(q, explicit = false, sequence = null, revision = productInputRevision) {
    const controller = explicit ? null : new AbortController();
    if (!explicit) productSearchAbort = controller;
    const url = '/admin/facturas/buscar-productos?q=' + encodeURIComponent(q);
    return fetch(url, controller ? {signal: controller.signal} : {})
        .then(r => {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(response => {
            if (!explicit && (sequence !== productSearchSequence || revision !== productInputRevision || prodInput.value.trim() !== q)) return;
            const data = Array.isArray(response) ? response : (response.items || []);
            const barcode = response && !Array.isArray(response) ? (response.barcode || {}) : {};

            if (barcode.can_auto_add && barcode.match && (explicit || !barcode.has_longer_prefix)) {
                const p = data.find(item => Number(item.idprodu) === Number(barcode.match.idprodu));
                if (!p) {
                    if (explicit && revision !== productInputRevision) return;
                    prodResults.innerHTML = '';
                    prodResults.innerHTML = '<div class="pos-result-item text-danger" style="justify-content:center">No se pudo resolver el producto del código.</div>';
                    prodResults.style.display = 'block';
                    return;
                }
                const priceCents = precioVigenteCents(p);
                const precio1Cents = p.precio1 ? Math.round(parseFloat(p.precio1) * 100) : 0;
                const ivaRate = p.tiva || 21;
                if (barcode.match.idcodgusto > 0) {
                    const matchedVariant = (p.variants || []).find(v => Number(v.idcodgusto) === Number(barcode.match.idcodgusto));
                    if (!matchedVariant) {
                        if (explicit && revision !== productInputRevision) return;
                        prodResults.innerHTML = '';
                        prodResults.innerHTML = '<div class="pos-result-item text-danger" style="justify-content:center">La variedad del código no está disponible para la venta.</div>';
                        prodResults.style.display = 'block';
                        return;
                    }
                    addToCart({
                        idprodu: Number(p.idprodu),
                        idcodgusto: Number(matchedVariant.idcodgusto),
                        producto: p.produ,
                        variedad: matchedVariant.nomgusto || barcode.match.variedad || '',
                        qty: 1,
                        unit_price_cents: priceCents,
                        precio1_cents: precio1Cents,
                        promo_pos_final_cents: p.promo_pos_final_cents || 0,
                        promo_pos_remaining: p.promo_pos_remaining,
                        promo_pos_version: p.promo_pos_version || '',
                        promo_pos_until: p.promo_pos_until || '',
                        iva_rate: ivaRate,
                        fecompra: p.fecompra || '',
                    });
                    if (revision === productInputRevision) {
                        prodResults.innerHTML = '';
                        prodResults.style.display = 'none';
                    }
                    if (!explicit && prodInput.value.trim() === q) clearProductSearchInput();
                    return;
                }
                addToCart({
                    idprodu: Number(p.idprodu),
                    idcodgusto: 0,
                    producto: p.produ,
                    variedad: '',
                    qty: 1,
                    unit_price_cents: priceCents,
                    precio1_cents: precio1Cents,
                    promo_pos_final_cents: p.promo_pos_final_cents || 0,
                    promo_pos_remaining: p.promo_pos_remaining,
                    promo_pos_version: p.promo_pos_version || '',
                    promo_pos_until: p.promo_pos_until || '',
                    iva_rate: ivaRate,
                    fecompra: p.fecompra || '',
                });
                if (revision === productInputRevision) {
                    prodResults.innerHTML = '';
                    prodResults.style.display = 'none';
                }
                if (!explicit && prodInput.value.trim() === q) clearProductSearchInput();
                return;
            }

            if (explicit && revision !== productInputRevision) return;
            prodResults.innerHTML = '';

            if (barcode.requires_selection && !barcode.ambiguous && barcode.match && (explicit || !barcode.has_longer_prefix)) {
                const p = data.find(item => Number(item.idprodu) === Number(barcode.match.idprodu));
                if (p) {
                    const priceCents = precioVigenteCents(p);
                    const precio1Cents = p.precio1 ? Math.round(parseFloat(p.precio1) * 100) : 0;
                    showVariantPicker(p, priceCents, precio1Cents, p.tiva || 21);
                    return;
                }
            }

            if (!data.length) {
                prodResults.innerHTML = '<div class="pos-result-item text-muted" style="justify-content:center">Sin resultados para “' + esc(q) + '”</div>';
                prodResults.style.display = 'block';
                return;
            }

            if (barcode.ambiguous) {
                prodResults.innerHTML = '<div class="alert alert-warning py-2 px-3 mb-0 small">Código duplicado o ambiguo. Elegí el producto y la variedad; no se agregó automáticamente.</div>';
            } else if (barcode.requires_selection) {
                prodResults.innerHTML = '<div class="alert alert-info py-2 px-3 mb-0 small">Este código identifica el producto, pero tiene varias variedades. Seleccioná una.</div>';
            } else if (barcode.exact && barcode.has_longer_prefix && !explicit) {
                prodResults.innerHTML = '<div class="alert alert-info py-2 px-3 mb-0 small">El código coincide con un prefijo de otro código más largo. Terminá el escaneo o presioná Enter para confirmar.</div>';
            } else if (barcode.exact && !barcode.can_auto_add) {
                prodResults.innerHTML = '<div class="alert alert-warning py-2 px-3 mb-0 small">El código coincide con una variedad no disponible o no seleccionable.</div>';
            }

            data.forEach(p => {
                const priceCents = precioVigenteCents(p);
                const precio1Cents = p.precio1 ? Math.round(parseFloat(p.precio1) * 100) : 0;
                const ivaRate = p.tiva || 21;
                const promoFinalCents = Number(p.promo_pos_final_cents || 0);
                const precioRegularFinal = priceCents + Math.round(priceCents * ivaRate / 100);
                const hasPromo = promoFinalCents > 0 && Boolean(p.promo_pos_version) && promoFinalCents < precioRegularFinal;
                const promoSelected = hasPromo && promoPagoCalificado();
                const promoNet = promoSelected ? promoNetCents({promo_pos_final_cents: promoFinalCents, iva_rate: ivaRate}) : 0;
                const displayPrice = promoSelected
                    ? (displayPriceIsGross() ? promoFinalCents : getDisplayPrice(promoNet, ivaRate))
                    : getDisplayPrice(priceCents, ivaRate);
                const stockDep = p.stock_deposito ?? 0;
                const stockTot = p.stock_total ?? 0;
                const promoHint = hasPromo
                    ? '<div class="small text-success fw-semibold">Oferta contado/transferencia: $' + fmtPrice(promoFinalCents) + (p.promo_pos_remaining !== null ? ' · quedan ' + Number(p.promo_pos_remaining) : '') + '</div>'
                    : '';

                const div = document.createElement('div');
                div.className = 'pos-result-item';
                div.style.flexWrap = 'wrap';
                div.innerHTML = `
                    <div style="flex:1;min-width:0">
                        <div class="prod-name">${esc(p.produ)}</div>
                        <div class="prod-code">${esc(p.codprodu)} ${p.codprodup ? '| ' + esc(p.codprodup) : ''}</div>
                        <div style="font-size:11px;color:#6c757d;margin-top:2px">
                            <span class="text-success fw-semibold">Dep: ${stockDep}</span>
                            <span class="ms-2 text-muted">Total: ${stockTot}</span>
                            <span class="ms-2">${fecompraBadge(p.fecompra)}</span>
                        </div>
                    </div>
                    <div class="prod-price">$${fmtPrice(displayPrice)}${promoHint}</div>
                `;
                div.addEventListener('mousedown', function(e) {
                    e.preventDefault();
                    cancelProductSearch();
                    if (p.variants && p.variants.length > 0) {
                        showVariantPicker(p, priceCents, precio1Cents, ivaRate);
                    } else {
                        addToCart({
                            idprodu: p.idprodu,
                            idcodgusto: 0,
                            producto: p.produ,
                            variedad: '',
                            qty: 1,
                            unit_price_cents: priceCents,
                            precio1_cents: precio1Cents,
                            promo_pos_final_cents: p.promo_pos_final_cents || 0,
                            promo_pos_remaining: p.promo_pos_remaining,
                            promo_pos_version: p.promo_pos_version || '',
                            promo_pos_until: p.promo_pos_until || '',
                            iva_rate: ivaRate,
                            fecompra: p.fecompra || '',
                        });
                        prodResults.style.display = 'none';
                        prodInput.value = '';
                        prodInput.focus();
                    }
                });
                prodResults.appendChild(div);
            });
            prodResults.style.display = 'block';
        })
        .catch(error => {
            if (error.name === 'AbortError') return;
            if (!explicit && (sequence !== productSearchSequence || revision !== productInputRevision || prodInput.value.trim() !== q)) return;
            if (explicit && revision !== productInputRevision) return;
            console.error('POS product search failed:', error);
            prodResults.innerHTML = '<div class="pos-result-item text-danger" style="justify-content:center">No se pudo buscar el producto.</div>';
            prodResults.style.display = 'block';
        });
}

function showVariantPicker(p, priceCents, precio1Cents, ivaRate) {
    let html = '<div class="pos-result-item" style="flex-direction:column;align-items:stretch;cursor:default">';
    html += '<div class="fw-bold mb-2">' + esc(p.produ) + ' — elegí variedad: ' + fecompraBadge(p.fecompra) + '</div>';
    html += '<input type="text" id="variantFilter" placeholder="Filtrar por nombre o id (ej: saro, 123)" class="form-control form-control-sm mb-2" oninput="filterVariantPicker(this.value)" />';
    html += '<div id="variantList" style="max-height:300px;overflow-y:auto;">';
    p.variants.forEach(v => {
        const cs = v.codscan ? ' (' + v.codscan + ')' : '';
        const vStockDep = v.stock_deposito ?? 0;
        const vStockTot = v.stock_total ?? 0;
        html += '<div class="suggestion-item variant-item" data-id="' + v.idcodgusto + '" data-nom="' + esc(v.nomgusto) + '" data-cod="' + esc(String(v.idcodgusto)) + '">'
            + esc(v.nomgusto) + cs + ' <span class="text-muted" style="font-size:10px">#'+v.idcodgusto+'</span>'
            + ' <span class="text-success" style="font-size:11px">Dep: ' + vStockDep + '</span>'
            + ' <span class="text-muted" style="font-size:11px">Total: ' + vStockTot + '</span>'
            + '</div>';
    });
    html += '</div></div>';
    prodResults.innerHTML = html;
    const filterInput = document.getElementById('variantFilter');
    if (filterInput) filterInput.focus();
    prodResults.querySelectorAll('.suggestion-item').forEach(el => {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            cancelProductSearch();
            const nom = this.dataset.nom || '';
            const gustoId = this.dataset.id || 0;
            addToCart({
                idprodu: p.idprodu,
                idcodgusto: parseInt(gustoId),
                producto: p.produ,
                variedad: nom,
                qty: 1,
                unit_price_cents: priceCents,
                precio1_cents: precio1Cents,
                promo_pos_final_cents: p.promo_pos_final_cents || 0,
                promo_pos_remaining: p.promo_pos_remaining,
                promo_pos_version: p.promo_pos_version || '',
                promo_pos_until: p.promo_pos_until || '',
                iva_rate: ivaRate,
                fecompra: p.fecompra || '',
            });
            prodResults.style.display = 'none';
            prodInput.value = '';
            prodInput.focus();
        });
    });
}
function filterVariantPicker(q) {
    const needle = (q||'').toLowerCase().trim();
    document.querySelectorAll('#variantList .variant-item').forEach(el => {
        const nom = (el.dataset.nom||'').toLowerCase();
        const id = (el.dataset.id||'').toLowerCase();
        const cod = (el.dataset.cod||'').toLowerCase();
        const visible = !needle || nom.includes(needle) || id.includes(needle) || cod.includes(needle);
        el.style.display = visible ? '' : 'none';
    });
}

// ── Cart ──
function lineDto(v) {
    const d = parseFloat(v) || 0;
    return Math.min(100, Math.max(0, d));
}

function addToCart(item) {
    const netPrice = item.unit_price_cents || 0;
    const dto = lineDto(item.dto);
    const key = item.idprodu + '-' + item.idcodgusto + '-' + dto;
    const existing = cart.find(c => (c.idprodu + '-' + c.idcodgusto + '-' + lineDto(c.dto)) === key);
    if (existing) {
        existing.qty += item.qty || 1;
        if (!existing.fecompra && item.fecompra) existing.fecompra = item.fecompra;
        if (!existing.promo_pos_version && item.promo_pos_version) {
            existing.promo_pos_final_cents = Number(item.promo_pos_final_cents || 0);
            existing.promo_pos_remaining = item.promo_pos_remaining === null || item.promo_pos_remaining === undefined ? null : Number(item.promo_pos_remaining);
            existing.promo_pos_version = item.promo_pos_version;
            existing.promo_pos_until = item.promo_pos_until || '';
        }
    } else {
        cart.push({
            idprodu: item.idprodu,
            idcodgusto: item.idcodgusto || 0,
            producto: item.producto,
            variedad: item.variedad || '',
            qty: item.qty || 1,
            net_price_cents: netPrice,
            precio1_cents: item.precio1_cents || 0,
            promo_pos_final_cents: Number(item.promo_pos_final_cents || 0),
            promo_pos_remaining: item.promo_pos_remaining === null || item.promo_pos_remaining === undefined ? null : Number(item.promo_pos_remaining),
            promo_pos_version: item.promo_pos_version || '',
            promo_pos_until: item.promo_pos_until || '',
            dto: dto,
            iva_rate: item.iva_rate || 21,
            fecompra: item.fecompra || '',
        });
    }
    renderCart();
}

function renderCart() {
    const container = document.getElementById('cartItems');
    const count = document.getElementById('cartCount');
    const cols = document.getElementById('cartCols');
    if (cols) cols.style.display = cart.length === 0 ? 'none' : 'flex';
    if (cart.length === 0) {
        container.innerHTML = '<div class="text-muted text-center py-4 small">Carrito vacío</div>';
        count.textContent = '0 items';
        recalcTotals();
        return;
    }

    count.textContent = cart.length + ' item(s)';
    container.innerHTML = cart.map((item, idx) => {
        const dto = lineDto(item.dto);
        const amounts = lineAmounts(item, idx);
        const total = displayPriceIsGross() ? amounts.net + amounts.iva : amounts.net;
        const promoQty = promoUnitsFor(item, idx);
        return `
            <div class="pos-cart-item" data-idx="${idx}">
                <div class="ci-name">
                    <div class="ci-prod">${esc(item.producto)}</div>
                    <div class="ci-var">${item.variedad ? esc(item.variedad) : '—'}</div>
                    ${promoQty > 0 ? '<div class="small text-success fw-semibold">Oferta efectivo/transferencia' + (item.promo_pos_until ? ' · hasta ' + esc(item.promo_pos_until) : '') + '</div>' : ''}
                    ${item.fecompra ? '<div style="font-size:10px">' + fecompraBadge(item.fecompra) + '</div>' : ''}
                </div>
                <div class="ci-qty"><input type="number" value="${item.qty}" min="1" onchange="updateQty(${idx}, this.value)" /></div>
                <div class="ci-dto"><input type="number" value="${dto}" min="0" max="100" step="1" title="Descuento %" onchange="updateDto(${idx}, this.value)" /></div>
                <div class="ci-price">${precioCarritoLabel(item, idx)}</div>
                <div class="ci-total">$${fmtPrice(total)}</div>
                <div class="ci-del" onclick="removeItem(${idx})">&times;</div>
            </div>
        `;
    }).join('');
    recalcTotals();
}

function updateQty(idx, val) {
    cart[idx].qty = Math.max(1, parseInt(val) || 1);
    renderCart();
}

function updateDto(idx, val) {
    cart[idx].dto = lineDto(val);
    renderCart();
}

function removeItem(idx) {
    cart.splice(idx, 1);
    renderCart();
}

function recalcTotals() {
    let subtotal = 0, iva = 0, total = 0;
    cart.forEach((item, index) => {
        const amounts = lineAmounts(item, index);
        subtotal += amounts.net;
        iva += amounts.iva;
        total += amounts.net + amounts.iva;
    });
    const descPct = parseFloat(document.getElementById('posDescuento').value) || 0;
    const descuento = descPct > 0 ? Math.round(total * descPct / 100) : 0;

    const isRI = getCondIva() === 'responsable_inscripto';
    document.getElementById('posIvaRow').style.display = isRI ? 'flex' : 'none';
    document.getElementById('posSubtotal').textContent = '$' + fmtPrice(isRI ? subtotal : total);
    document.getElementById('posIva').textContent = '$' + fmtPrice(iva);
    const totalConDto = total - descuento;

    let puntosUsados = 0;
    const puntosEl = document.getElementById('posPuntosUsar');
    if (puntosEl && puntosEl.value && parseInt(puntosEl.value) > 0) {
        const maxPuntos = Math.floor(totalConDto / 100);
        puntosUsados = Math.min(parseInt(puntosEl.value), maxPuntos);
        if (puntosUsados < parseInt(puntosEl.value)) puntosEl.value = puntosUsados;
    }
    const puntosCents = puntosUsados * 100;
    const totalSinRedondear = Math.max(0, totalConDto - puntosCents);
    const redondeado = redondearTotalFacturaCents(totalSinRedondear, document.getElementById('tipoComprobante').value);
    const totalFinal = redondeado.total;
    const redondeoRow = document.getElementById('posRedondeoRow');
    redondeoRow.style.display = redondeado.redondeo !== 0 ? 'flex' : 'none';
    document.getElementById('posRedondeo').textContent = (redondeado.redondeo > 0 ? '+' : '−') + '$' + fmtPrice(Math.abs(redondeado.redondeo));
    document.getElementById('posTotal').textContent = '$' + (document.getElementById('tipoComprobante').value === 'NC' ? fmtPrice(totalFinal) : fmtPesosEnteros(totalFinal));

    let pagado = 0;
    document.querySelectorAll('#pagosContainer .fp-monto').forEach(inp => {
        pagado += parseInt(parseFloat(inp.value) * 100) || 0;
    });
    const vuelto = pagado > totalFinal ? pagado - totalFinal : 0;
    document.getElementById('pagosTotal').textContent = '$' + fmtPrice(pagado);
    document.getElementById('vueltoDisplay').textContent = '$' + fmtPrice(vuelto);
}

// ── Client search ──
const cliInput = document.getElementById('clienteSearch');
const cliSuggestions = document.getElementById('clienteSuggestions');
let cliTimer;
let clienteNombreSel = '';

cliInput.addEventListener('input', function() {
    clearTimeout(cliTimer);
    const val = this.value.trim();
    if (val.length < 2) { cliSuggestions.innerHTML = ''; return; }
    cliTimer = setTimeout(() => {
        fetch('/admin/facturas/buscar-clientes?q=' + encodeURIComponent(val))
            .then(r => r.json())
            .then(data => {
                cliSuggestions.innerHTML = '';
                if (!data || data.length === 0) {
                    cliSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                    return;
                }
                data.forEach(c => {
                    const div = document.createElement('div');
                    div.className = 'suggestion-item';
                    const condIvaLabel = c.condicion_iva === 'responsable_inscripto' ? ' (RI)' : c.condicion_iva === 'monotributista' ? ' (Mono)' : c.condicion_iva === 'exento' ? ' (EX)' : '';
                    div.innerHTML = '<strong>' + esc(c.name) + '</strong> ' + esc(c.cuit || '') + condIvaLabel + esc(categoriaBadge(c)) + ' <span class="text-muted">' + esc(c.email || '') + '</span>';
                    div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
                    div.addEventListener('mousedown', function(e) {
                        e.preventDefault();
                        selectCliente(c);
                    });
                    cliSuggestions.appendChild(div);
                });
            });
    }, 250);
});

cliInput.addEventListener('blur', function() {
    setTimeout(() => cliSuggestions.innerHTML = '', 300);
});

function condIvaTexto(cond) {
    const mapa = {
        responsable_inscripto: 'Responsable Inscripto',
        monotributista: 'Monotributista',
        exento: 'Exento',
        consumidor_final: 'Consumidor Final',
    };
    return mapa[cond] || '';
}

function cuentaCorrientePermitida() {
    const pagosOriginales = Array.isArray(EDITAR_PAGOS) ? EDITAR_PAGOS : [];
    const facturaYaUsabaCtaCte = pagosOriginales.some(p => p.forma_pago === 'cuenta_corriente');
    return ctaCteHabilitada || (EDITAR_ID > 0 && facturaYaUsabaCtaCte);
}

function actualizarOpcionesCuentaCorriente() {
    const permitida = cuentaCorrientePermitida();
    document.querySelectorAll('#pagosContainer .fp-forma').forEach(function(sel) {
        Array.from(sel.options).forEach(function(opt) {
            if (opt.value === 'cuenta_corriente') {
                opt.disabled = !permitida;
                if (!permitida && sel.value === 'cuenta_corriente') {
                    sel.value = 'efectivo';
                    onPagoFormaChange(sel);
                }
            }
        });
    });
    const hint = document.getElementById('ctaCtePermisoHint');
    if (hint) {
        hint.textContent = permitida
            ? 'Cuenta corriente habilitada para este cliente.'
            : 'La cuenta corriente solo está disponible para clientes habilitados por un admin.';
        hint.className = 'small mt-2 ' + (permitida ? 'text-success' : 'text-muted');
    }
}

function consultarHabilitacionCuentaCorriente(idclien) {
    const seq = ++ctaCteCheckSequence;
    ctaCteHabilitada = false;
    actualizarOpcionesCuentaCorriente();
    idclien = parseInt(idclien) || 0;
    if (idclien <= 0) return;
    const hint = document.getElementById('ctaCtePermisoHint');
    if (hint) {
        hint.textContent = 'Verificando habilitación de cuenta corriente…';
        hint.className = 'small mt-2 text-muted';
    }
    fetch('/admin/facturas/cuenta-corriente-habilitada?idclien=' + encodeURIComponent(idclien))
        .then(r => r.json())
        .then(data => {
            if (seq !== ctaCteCheckSequence) return;
            ctaCteHabilitada = !!(data && data.habilitada);
            actualizarOpcionesCuentaCorriente();
        })
        .catch(() => {
            if (seq !== ctaCteCheckSequence) return;
            ctaCteHabilitada = false;
            actualizarOpcionesCuentaCorriente();
        });
}

function selectCliente(c) {
    document.getElementById('clienteId').value = c.id || 0;
    document.getElementById('clienteErpId').value = c.idclien || 0;
    clienteNombreSel = (c.name || '').trim();
    document.getElementById('clienteCuit').textContent = c.cuit || '';
    document.getElementById('clienteCondIva').value = c.condicion_iva || 'consumidor_final';
    const cat = c.categoria || 'minorista';
    clienteMayorista = cat === 'mayorista' || (cat === 'profesional' && parseInt(c.precio_mayorista) > 0);
    actualizarBadgeMayorista();
    const displayName = (c.name || 'Consumidor Final') + (c.cuit ? ' - ' + c.cuit : '') + categoriaBadge(c);
    document.getElementById('clienteNombre').textContent = displayName;
    const dir = [c.direc || '', c.city || ''].filter(Boolean).join(', ');
    document.getElementById('clienteInfo').textContent = [condIvaTexto(c.condicion_iva || ''), dir].filter(Boolean).join(' · ');
    cliInput.value = '';
    cliSuggestions.innerHTML = '';

    const iva = c.condicion_iva || 'consumidor_final';
    const tipoMap = {
        'responsable_inscripto': 'FACT-A',
        'consumidor_final': 'FACT-B',
        'monotributista': 'FACT-C',
        'exento': 'FACT-C',
    };
    let tipoAutomatico = tipoMap[iva] || 'FACT-B';
    const emisorIva = document.getElementById('emisorIva').value;
    if (emisorIva === 'responsable_inscripto') {
        tipoAutomatico = iva === 'responsable_inscripto' ? 'FACT-A' : 'FACT-B';
    } else if (emisorIva === 'monotributo' || emisorIva === 'exento') {
        tipoAutomatico = 'FACT-C';
    }
    const tipoSelNc = document.getElementById('tipoComprobante');
    if (tipoSelNc.value !== 'NC' && tipoSelNc.value !== 'ND') {
        tipoSelNc.value = tipoAutomatico;
    }
    renderCart();

    loadPuntosSaldo(c.idclien || 0);
    consultarHabilitacionCuentaCorriente(c.idclien || 0);
}

let puntosSaldoCliente = 0;

function canjearPuntos() {
    const usar = document.getElementById('posPuntosUsar');
    if (!usar || puntosSaldoCliente <= 0) return;
    usar.value = puntosSaldoCliente;
    recalcTotals();
}

function loadPuntosSaldo(idclien) {
    const row = document.getElementById('posPuntosRow');
    const saldoRow = document.getElementById('posPuntosSaldo');
    const usar = document.getElementById('posPuntosUsar');
    if (usar) usar.value = 0;
    puntosSaldoCliente = 0;
    if (!idclien) {
        row.style.display = 'none';
        saldoRow.style.display = 'none';
        renderCart();
        return;
    }
    fetch('/admin/puntos/saldo?idclien=' + encodeURIComponent(idclien))
        .then(r => r.json())
        .then(data => {
            const saldo = (data && data.saldo) ? parseInt(data.saldo) : 0;
            puntosSaldoCliente = saldo;
            document.getElementById('posPuntosSaldoVal').textContent = saldo.toLocaleString('es-AR');
            row.style.display = saldo > 0 ? 'flex' : 'none';
            saldoRow.style.display = saldo > 0 ? 'flex' : 'none';
            renderCart();
        })
        .catch(() => {
            row.style.display = 'none';
            saldoRow.style.display = 'none';
            renderCart();
        });
}

function clearCliente() {
    document.getElementById('clienteId').value = 0;
    document.getElementById('clienteErpId').value = 0;
    clienteNombreSel = '';
    document.getElementById('clienteNombre').textContent = 'Consumidor Final';
    document.getElementById('clienteCuit').textContent = '';
    document.getElementById('clienteInfo').textContent = '';
    document.getElementById('clienteCondIva').value = 'consumidor_final';
    clienteMayorista = false;
    actualizarBadgeMayorista();
    cliInput.value = '';
    cliSuggestions.innerHTML = '';
    loadPuntosSaldo(0);
    consultarHabilitacionCuentaCorriente(0);
    renderCart();
}

// ── Nuevo cliente (directo desde el POS) ──
function toggleNcCategoria() {
    const cat = document.getElementById('ncCategoria').value;
    document.getElementById('ncMayoristaBox').style.display = (cat === 'mayorista' || cat === 'profesional') ? '' : 'none';
    if (cat === 'mayorista') {
        document.getElementById('ncPrecioMayorista').checked = true;
    }
}
function categoriaBadge(c) {
    const cat = c.categoria || 'minorista';
    if (cat === 'mayorista') return ' [Mayorista]';
    if (cat === 'profesional') {
        let label = ' [Profesional';
        if (c.especialidad) label += ' · ' + c.especialidad;
        if (parseInt(c.precio_mayorista) > 0) label += ' · PM';
        return label + ']';
    }
    return '';
}
let ncDups = [];

function ncLimpiarForm() {
    document.getElementById('ncRazon').value = '';
    document.getElementById('ncCuit').value = '';
    document.getElementById('ncTele').value = '';
    document.getElementById('ncMail').value = '';
    document.getElementById('ncDirec').value = '';
    document.getElementById('ncLocalidad').value = '';
    document.getElementById('ncCondIva').value = 'consumidor_final';
    document.getElementById('ncCategoria').value = 'minorista';
    document.getElementById('ncPrecioMayorista').checked = false;
    document.getElementById('ncEspecialidad').value = '';
    document.getElementById('ncDup').style.display = 'none';
    document.getElementById('ncError').style.display = 'none';
    toggleNcCategoria();
}

function ncMostrarDuplicados(dups) {
    ncDups = dups || [];
    const box = document.getElementById('ncDupList');
    box.innerHTML = ncDups.map((d, i) => {
        const doc = (d.cuit || '').trim();
        return '<div class="d-flex justify-content-between align-items-center border rounded px-2 py-1 mb-1 bg-white">'
            + '<div class="me-2"><strong>' + esc(d.name || '') + '</strong>'
            + (doc ? ' <span class="text-muted">&middot; ' + esc(doc) + '</span>' : '')
            + (d.motivo ? '<div class="text-muted" style="font-size:11px">' + esc(d.motivo) + '</div>' : '')
            + '</div>'
            + '<button type="button" class="btn btn-sm btn-outline-success text-nowrap" onclick="ncUsarExistente(' + i + ')"><i class="bi bi-check2"></i> Usar</button>'
            + '</div>';
    }).join('');
    document.getElementById('ncDup').style.display = '';
    document.getElementById('ncError').style.display = 'none';
}

function ncUsarExistente(i) {
    const c = ncDups[i];
    if (!c) return;
    const modal = bootstrap.Modal.getInstance(document.getElementById('nuevoClienteModal'));
    if (modal) modal.hide();
    selectCliente(c);
    ncLimpiarForm();
}

function guardarNuevoCliente(force) {
    const razon = document.getElementById('ncRazon').value.trim();
    const cuit = document.getElementById('ncCuit').value.trim();
    if (!razon) { alert('Ingresá el nombre del cliente.'); return; }
    const btn = document.getElementById('ncSaveBtn');
    btn.disabled = true;
    document.getElementById('ncError').style.display = 'none';
    document.getElementById('ncDup').style.display = 'none';

    const body = new URLSearchParams();
    body.append('_csrf', document.getElementById('csrfToken').value);
    body.append('razon', razon);
    body.append('cuit', cuit);
    if (force === true) body.append('force', '1');
    body.append('tele', document.getElementById('ncTele').value.trim());
    body.append('mail', document.getElementById('ncMail').value.trim());
    body.append('direc', document.getElementById('ncDirec').value.trim());
    body.append('localidad', document.getElementById('ncLocalidad').value.trim());
    body.append('condicion_iva', document.getElementById('ncCondIva').value);
    body.append('categoria', document.getElementById('ncCategoria').value);
    body.append('precio_mayorista', document.getElementById('ncPrecioMayorista').checked ? '1' : '0');
    body.append('especialidad', document.getElementById('ncEspecialidad').value.trim());

    fetch('/admin/facturas/clientes/crear', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: body.toString(),
    })
    .then(r => r.text().then(text => {
        try {
            const res = JSON.parse(text);
            if (res.ok && res.cliente) {
                const modal = bootstrap.Modal.getInstance(document.getElementById('nuevoClienteModal'));
                if (modal) modal.hide();
                selectCliente(res.cliente);
                ncLimpiarForm();
            } else if (res.confirm && res.duplicados) {
                ncMostrarDuplicados(res.duplicados);
            } else {
                document.getElementById('ncError').textContent = res.error || 'Error al crear el cliente.';
                document.getElementById('ncError').style.display = '';
            }
        } catch (e) {
            document.getElementById('ncError').textContent = 'Error del servidor: ' + text.substring(0, 200);
            document.getElementById('ncError').style.display = '';
        }
        btn.disabled = false;
    }))
    .catch(err => {
        document.getElementById('ncError').textContent = 'Error de conexión: ' + err.message;
        document.getElementById('ncError').style.display = '';
        btn.disabled = false;
    });
}

document.getElementById('nuevoClienteModal').addEventListener('shown.bs.modal', function() {
    document.getElementById('ncDup').style.display = 'none';
    document.getElementById('ncError').style.display = 'none';
    document.getElementById('ncRazon').focus();
});

// ── Remito search ──
const remInput = document.getElementById('remitoSearch');
const remSuggestions = document.getElementById('remitoSuggestions');
if (remInput) {
    let remTimer;
    remInput.addEventListener('input', function() {
        clearTimeout(remTimer);
        const val = this.value.trim();
        if (val.length < 2) { remSuggestions.innerHTML = ''; return; }
        remTimer = setTimeout(() => {
            fetch('/admin/facturas/buscar-remitos?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    remSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        remSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(r => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.innerHTML = '<strong>' + esc(r.codigo) + '</strong> <span class="text-muted">' + esc(r.cliente_nombre || '') + '</span>';
                        div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            window.location.href = '/admin/facturas/nueva?remito_id=' + r.id;
                        });
                        remSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    remInput.addEventListener('blur', function() {
        setTimeout(() => remSuggestions.innerHTML = '', 300);
    });
}

// ── Presupuesto search ──
const presInput = document.getElementById('presupuestoSearch');
const presSuggestions = document.getElementById('presupuestoSuggestions');
if (presInput) {
    let presTimer;
    presInput.addEventListener('input', function() {
        clearTimeout(presTimer);
        const val = this.value.trim();
        if (val.length < 2) { presSuggestions.innerHTML = ''; return; }
        presTimer = setTimeout(() => {
            fetch('/admin/facturas/buscar-presupuestos?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    presSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        presSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        div.innerHTML = '<strong>' + esc(p.codigo) + '</strong> <span class="text-muted">' + esc(p.cliente_nombre || '') + '</span>';
                        div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            window.location.href = '/admin/facturas/nueva?presupuesto_id=' + p.id;
                        });
                        presSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    presInput.addEventListener('blur', function() {
        setTimeout(() => presSuggestions.innerHTML = '', 300);
    });
}

// ── Comprobante asociado (NC/ND, obligatorio) ──
function toggleAsociada() {
    const tipo = document.getElementById('tipoComprobante').value;
    const wrap = document.getElementById('asociadaWrap');
    const show = (tipo === 'NC' || tipo === 'ND');
    wrap.style.display = show ? '' : 'none';
    if (!show) {
        document.getElementById('comprobanteAsociadoId').value = '0';
        document.getElementById('asociadaSearch').value = '';
        document.getElementById('asociadaSel').style.display = 'none';
    }
}
document.getElementById('tipoComprobante').addEventListener('change', function() {
    toggleAsociada();
    recalcTotals();
});

function mostrarAsociada(id, codigo, tipo, cliente, totalCents, fecha) {
    const hid = document.getElementById('comprobanteAsociadoId');
    const box = document.getElementById('asociadaSel');
    hid.value = String(id || 0);
    if (id > 0) {
        const total = (parseInt(totalCents || 0) / 100).toLocaleString('es-AR', { minimumFractionDigits: 2 });
        box.innerHTML = '<span class="badge bg-warning text-dark">Asociada: ' + esc(tipo || '') + ' ' + esc(codigo || '') + ' — ' + esc(cliente || '') + ' ($' + total + ')' + (fecha ? ' ' + esc(fecha) : '') + '</span> '
            + '<button type="button" class="btn btn-sm btn-outline-danger py-0 px-1" onclick="limpiarAsociada()" title="Quitar">×</button>';
        box.style.display = '';
    } else {
        box.style.display = 'none';
    }
}
function limpiarAsociada() {
    document.getElementById('comprobanteAsociadoId').value = '0';
    document.getElementById('asociadaSearch').value = '';
    document.getElementById('asociadaSel').style.display = 'none';
}
const asocInput = document.getElementById('asociadaSearch');
const asocSuggestions = document.getElementById('asociadaSuggestions');
if (asocInput) {
    let asocTimer;
    asocInput.addEventListener('input', function() {
        clearTimeout(asocTimer);
        const val = this.value.trim();
        if (val.length < 2) { asocSuggestions.innerHTML = ''; return; }
        asocTimer = setTimeout(() => {
            fetch('/admin/facturas/buscar-comprobantes?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    asocSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        asocSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(c => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        const total = (parseInt(c.total_cents || 0) / 100).toLocaleString('es-AR', { minimumFractionDigits: 2 });
                        div.innerHTML = '<strong>' + esc(c.tipo_comprobante || '') + ' ' + esc(c.codigo || '') + '</strong> <span class="text-muted">' + esc(c.cliente_nombre || '') + ' ($' + total + ')</span>';
                        div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            mostrarAsociada(c.id, c.codigo, c.tipo_comprobante, c.cliente_nombre, c.total_cents, c.fecha);
                            asocInput.value = '';
                            asocSuggestions.innerHTML = '';
                        });
                        asocSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    asocInput.addEventListener('blur', function() {
        setTimeout(() => asocSuggestions.innerHTML = '', 300);
    });
}

// ── Precarga para "Generar NC" (?nc_de=) ──
function ncDePrefill() {
    const o = NC_DE || {};
    const tc = document.getElementById('tipoComprobante');
    if (tc) { tc.value = 'NC'; toggleAsociada(); }
    mostrarAsociada(o.id || 0, o.codigo || '', o.tipo || '', (o.cliente && o.cliente.nombre) || '', 0, '');
    const cli = o.cliente || {};
    if (cli.id || cli.idclien || cli.nombre) {
        selectCliente({
            id: cli.id || 0,
            idclien: cli.idclien || 0,
            name: cli.nombre || '',
            cuit: cli.cuit || '',
            condicion_iva: cli.condicion_iva || 'consumidor_final',
            direc: cli.direc || '',
            city: '',
        });
    }
    (o.items || []).forEach(it => addToCart({
        idprodu: it.idprodu || 0,
        idcodgusto: it.idcodgusto || 0,
        producto: it.producto || '',
        variedad: it.variedad || '',
        qty: it.qty || 1,
        unit_price_cents: it.unit_price_cents || 0,
        iva_rate: (it.iva_rate === undefined || it.iva_rate === null) ? 21 : it.iva_rate,
        dto: it.dto || 0,
    }));
    renderCart();
}

// ── Pedido web search ──
const pedInput = document.getElementById('pedidoSearch');
const pedSuggestions = document.getElementById('pedidoSuggestions');
if (pedInput) {
    let pedTimer;
    pedInput.addEventListener('input', function() {
        clearTimeout(pedTimer);
        const val = this.value.trim();
        if (val.length < 2) { pedSuggestions.innerHTML = ''; return; }
        pedTimer = setTimeout(() => {
            fetch('/admin/facturas/buscar-pedidos?q=' + encodeURIComponent(val))
                .then(r => r.json())
                .then(data => {
                    pedSuggestions.innerHTML = '';
                    if (!data || data.length === 0) {
                        pedSuggestions.innerHTML = '<div class="suggestion-item text-muted">Sin resultados</div>';
                        return;
                    }
                    data.forEach(p => {
                        const div = document.createElement('div');
                        div.className = 'suggestion-item';
                        const extra = (p.facturado ? ' <span class="badge bg-secondary">facturado</span>' : '') + ' <span class="text-muted">' + esc(p.estado || '') + '</span>';
                        div.innerHTML = '<strong>' + esc(p.codigo) + '</strong> <span class="text-muted">' + esc(p.cliente || '') + '</span>' + extra;
                        div.style.cssText = 'padding:6px 10px;cursor:pointer;font-size:13px;border-bottom:1px solid #eee;background:#fff;';
                        div.addEventListener('mousedown', function(e) {
                            e.preventDefault();
                            window.location.href = '/admin/facturas/nueva?pedido_id=' + p.id;
                        });
                        pedSuggestions.appendChild(div);
                    });
                });
        }, 300);
    });
    pedInput.addEventListener('blur', function() {
        setTimeout(() => pedSuggestions.innerHTML = '', 300);
    });
}

// ── Prefill desde pedido web ──
const PEDIDO_ITEMS = <?= json_encode($pedidoItems, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]' ?>;
const PEDIDO_CLIENTE = <?= json_encode($pedidoCliente, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: 'null' ?>;
const PEDIDO_ENVIO = <?= json_encode($pedidoEnvio, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: 'null' ?>;
const PEDIDO_PAGO = <?= json_encode($pedidoPago, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: 'null' ?>;
const PEDIDO_DESC_PCT = <?= json_encode($pedidoDescPct) ?: '0' ?>;

// ── Edición de comprobante (facturas sin CAE) ──
const EDITAR_ID = <?= (int)$editarId ?>;
const EDITAR_FACTURA = <?= json_encode($editarFactura, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: 'null' ?>;
const EDITAR_ITEMS = <?= json_encode($editarItems, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]' ?>;
const EDITAR_PAGOS = <?= json_encode($editarPagos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: '[]' ?>;
const EDITAR_ASOCIADA = <?= json_encode($editarAsociada ?? null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: 'null' ?>;
const NC_DE = <?= json_encode($ncDe ?? null, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE) ?: 'null' ?>;
const BTN_LABEL = EDITAR_ID > 0 ? '<i class="bi bi-pencil"></i> GUARDAR CAMBIOS' : '<i class="bi bi-receipt"></i> FACTURAR';

function pedidoDisplayedTotalCents() {
    const t = (document.getElementById('posTotal').textContent || '').replace(/[^0-9,.\-]/g, '').replace(/\./g, '').replace(',', '.');
    const v = Math.round(parseFloat(t) * 100);
    return isNaN(v) ? 0 : v;
}

document.addEventListener('DOMContentLoaded', function() {
    toggleAsociada();
    if (EDITAR_ID > 0) { editarPrefill(); return; }
    if (NC_DE && NC_DE.id > 0) { ncDePrefill(); return; }
    const pidEl = document.getElementById('pedidoId');
    if (!pidEl || parseInt(pidEl.value) <= 0) return;
    (PEDIDO_ITEMS || []).forEach(it => addToCart({
        idprodu: it.idprodu || 0,
        idcodgusto: it.idcodgusto || 0,
        producto: it.producto || '',
        variedad: it.variedad || '',
        qty: it.qty || 1,
        unit_price_cents: it.unit_price_cents || 0,
        iva_rate: it.iva_rate || 21,
    }));
    if (PEDIDO_CLIENTE && (PEDIDO_CLIENTE.name || PEDIDO_CLIENTE.idclien)) {
        selectCliente(PEDIDO_CLIENTE);
    }
    if (PEDIDO_ENVIO) {
        const radio = document.querySelector('input[name="entrega_tipo"][value="' + (PEDIDO_ENVIO.tipo === 'envio' ? 'envio' : 'local') + '"]');
        if (radio) { radio.checked = true; onEntregaChange(); }
        if (PEDIDO_ENVIO.transporte) {
            const ts = document.getElementById('transporteSelect');
            if (ts) ts.value = PEDIDO_ENVIO.transporte;
        }
        if (PEDIDO_ENVIO.direccion) {
            const ed = document.getElementById('envioDireccion');
            if (ed) ed.value = PEDIDO_ENVIO.direccion;
        }
        if (PEDIDO_ENVIO.obs) {
            const eo = document.getElementById('envioObs');
            if (eo) eo.value = PEDIDO_ENVIO.obs;
        }
    }
    if (PEDIDO_DESC_PCT > 0) {
        const dp = document.getElementById('posDescuento');
        if (dp) dp.value = PEDIDO_DESC_PCT;
    }
    renderCart();
    if (PEDIDO_PAGO && PEDIDO_PAGO.monto_cents > 0) {
        addPagoLine(PEDIDO_PAGO.forma || 'mercadopago');
        const lines = document.querySelectorAll('#pagosContainer .pago-line');
        const last = lines[lines.length - 1];
        if (last) {
            const monto = last.querySelector('.fp-monto');
            if (monto) monto.value = (pedidoDisplayedTotalCents() / 100).toFixed(2);
        }
        recalcTotals();
    }
});

// ── Prefill para editar un comprobante existente ──
function editarPrefill() {
    const f = EDITAR_FACTURA || {};
    (EDITAR_ITEMS || []).forEach(it => addToCart({
        idprodu: it.idprodu || 0,
        idcodgusto: it.idcodgusto || 0,
        producto: it.producto || '',
        variedad: it.variedad || '',
        qty: it.qty || 1,
        unit_price_cents: it.unit_price_cents || 0,
        iva_rate: (it.iva_rate === undefined || it.iva_rate === null) ? 21 : it.iva_rate,
        dto: it.descuento_pct || 0,
    }));
    if (f.cliente_id || f.idclien) {
        selectCliente({
            id: f.cliente_id || 0,
            idclien: f.idclien || 0,
            name: f.cliente_nombre || '',
            cuit: f.cliente_cuit || '',
            condicion_iva: f.cliente_condicion_iva || 'consumidor_final',
            direc: f.cliente_direc || '',
            city: '',
        });
    } else {
        if (f.cliente_nombre) {
            clienteNombreSel = (f.cliente_nombre || '').trim();
            document.getElementById('clienteNombre').textContent = f.cliente_nombre;
            cliInput.value = '';
        }
        if (f.cliente_cuit) document.getElementById('clienteCuit').textContent = f.cliente_cuit;
        if (f.cliente_condicion_iva) document.getElementById('clienteCondIva').value = f.cliente_condicion_iva;
        document.getElementById('clienteInfo').textContent =
            [condIvaTexto(f.cliente_condicion_iva || ''), f.cliente_direc || ''].filter(Boolean).join(' · ');
    }
    // selectCliente puede cambiar el tipo según la condición: prevalece el original
    const tc = document.getElementById('tipoComprobante');
    if (tc && f.tipo_comprobante) tc.value = f.tipo_comprobante;
    toggleAsociada();
    // Comprobante asociado (NC/ND)
    if (typeof EDITAR_ASOCIADA !== 'undefined' && EDITAR_ASOCIADA && EDITAR_ASOCIADA.id > 0) {
        mostrarAsociada(EDITAR_ASOCIADA.id, EDITAR_ASOCIADA.codigo, EDITAR_ASOCIADA.tipo, EDITAR_ASOCIADA.cliente_nombre, EDITAR_ASOCIADA.total_cents, EDITAR_ASOCIADA.fecha);
    }
    const vEl = document.getElementById('vendedorId');
    if (vEl) {
        vEl.value = String(parseInt(f.vendedor_id || 0)) || '0';
    }
    // Dto. global (% con decimales para reconstruir los centavos exactos)
    const bruto = cart.reduce((s, item) => {
        const netLine = Math.round(item.qty * precioActivo(item) * (1 - lineDto(item.dto) / 100));
        return s + netLine + (item.iva_rate > 0 ? Math.round(netLine * item.iva_rate / 100) : 0);
    }, 0);
    const descCents = parseInt(f.descuento_cents || 0);
    const dp = document.getElementById('posDescuento');
    if (dp) dp.value = (descCents > 0 && bruto > 0) ? parseFloat((descCents / bruto * 100).toFixed(6)) : 0;
    // Entrega
    if (f.entrega_tipo === 'envio') {
        const radio = document.querySelector('input[name="entrega_tipo"][value="envio"]');
        if (radio) { radio.checked = true; onEntregaChange(); }
        const ts = document.getElementById('transporteSelect');
        if (ts && f.transporte) ts.value = f.transporte;
        const ed = document.getElementById('envioDireccion');
        if (ed) ed.value = f.envio_direccion || '';
        const eo = document.getElementById('envioObs');
        if (eo) eo.value = f.envio_observacion || '';
    }
    const nEl = document.getElementById('facturaNotas');
    if (nEl && f.notas) nEl.value = f.notas;

    // Pagos originales (los cheques conservan su cheque_id para reutilizarse)
    (EDITAR_PAGOS || []).forEach(pg => {
        if ((pg.monto_cents || 0) <= 0) return;
        const forma = pg.forma_pago === 'tarjetas' ? 'tarjeta' : (pg.forma_pago || 'efectivo');
        addPagoLine(forma);
        const lines = document.querySelectorAll('#pagosContainer .pago-line');
        const line = lines[lines.length - 1];
        if (!line) return;
        const sel = line.querySelector('.fp-forma');
        if (sel && sel.value !== forma) {
            const opt = document.createElement('option');
            opt.value = forma;
            opt.textContent = forma;
            sel.appendChild(opt);
            sel.value = forma;
            onPagoFormaChange(sel);
        }
        const monto = line.querySelector('.fp-monto');
        if (monto) monto.value = (pg.monto_cents / 100).toFixed(2);
        const tipo = formaTipo(forma);
        if (tipo === 'tarjeta') {
            const t = line.querySelector('.fp-tarjeta'); if (t && pg.tarjeta_id) t.value = String(pg.tarjeta_id);
            const e = line.querySelector('.fp-equipo'); if (e && pg.equipo_id) e.value = String(pg.equipo_id);
            const c = line.querySelector('.fp-cupon'); if (c && pg.cupon_numero) c.value = pg.cupon_numero;
        } else if (tipo === 'banco') {
            const b = line.querySelector('.fp-banco-cuenta'); if (b && pg.banco_cuenta_id) b.value = String(pg.banco_cuenta_id);
        } else if (tipo === 'ctacte') {
            const p = line.querySelector('.fp-plazo'); if (p && pg.idplazo) p.value = String(pg.idplazo);
        } else if (tipo === 'cheque') {
            if (pg.cheque_id) line.dataset.chequeId = String(pg.cheque_id);
            const b = line.querySelector('.fp-banco'); if (b && pg.banco_id) b.value = String(pg.banco_id);
            const n = line.querySelector('.fp-chequenum'); if (n) n.value = pg.numero_cheque || '';
            const t = line.querySelector('.fp-chequetitular'); if (t) t.value = pg.cheque_titular || '';
            const cu = line.querySelector('.fp-chequecuit'); if (cu) cu.value = pg.cheque_cuit || '';
            const v = line.querySelector('.fp-chequevenc'); if (v && pg.cheque_vto) v.value = String(pg.cheque_vto).substring(0, 10);
        } else if (tipo === 'moneda') {
            const mm = line.querySelector('.fp-monto-moneda');
            const cz = line.querySelector('.fp-cotizacion');
            if (mm && pg.monto_moneda_cents) mm.value = (pg.monto_moneda_cents / 100).toFixed(2);
            if (cz && pg.cotizacion) cz.value = pg.cotizacion;
            if (mm) mm.dispatchEvent(new Event('input'));
        }
    });

    // Puntos canjeados: setear después de selectCliente (loadPuntosSaldo lo pone en 0)
    const pu = document.getElementById('posPuntosUsar');
    const pts = Math.floor(parseInt(f.puntos_cents || 0) / 100);
    if (pu && pts > 0) pu.value = pts;
    renderCart();

    if (document.querySelectorAll('#pagosContainer .pago-line').length === 0) {
        addPagoLine('efectivo');
        const lines = document.querySelectorAll('#pagosContainer .pago-line');
        const monto = lines[lines.length - 1]?.querySelector('.fp-monto');
        if (monto) monto.value = (pedidoDisplayedTotalCents() / 100).toFixed(2);
        recalcTotals();
    }
}

// ── Payment lines (multi-pago) ──
const BANCOS = <?= json_encode($bancos, JSON_UNESCAPED_UNICODE) ?: '[]' ?>;
const BANCOS_CUENTAS = <?= json_encode($bancosCuentas, JSON_UNESCAPED_UNICODE) ?: '[]' ?>;
const TARJETAS = <?= json_encode($tarjetas, JSON_UNESCAPED_UNICODE) ?: '[]' ?>;
const EQUIPOS = <?= json_encode($equipos, JSON_UNESCAPED_UNICODE) ?: '[]' ?>;
const TRANSFER_CUENTA_ID = <?= json_encode($transferCuentaId) ?: 'null' ?>;
const TARJETA_BANCO_MAP = <?= json_encode($tarjetaBancoMap) ?: '[]' ?>;
const PLAZOS = <?= json_encode($plazos, JSON_UNESCAPED_UNICODE) ?: '[]' ?>;
const FORMAS_PAGO_RAW = <?= json_encode($formasPago, JSON_UNESCAPED_UNICODE) ?: '[]' ?>;
const VOUCHERS_DISPONIBLES = <?= json_encode($vouchersDisponibles ?? [], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT) ?: '[]' ?>;
const FORMAS_PAGO = (FORMAS_PAGO_RAW.length ? FORMAS_PAGO_RAW : [
    {codigo: 'efectivo', nombre: 'Efectivo', tipo: 'efectivo'},
    {codigo: 'transferencia', nombre: 'Transferencia bancaria', tipo: 'banco'},
    {codigo: 'tarjeta', nombre: 'Tarjetas', tipo: 'tarjeta'},
    {codigo: 'mercadopago', nombre: 'Mercado Pago', tipo: 'banco'},
    {codigo: 'cuenta_corriente', nombre: 'Cuenta corriente', tipo: 'ctacte'},
    {codigo: 'cheque', nombre: 'Cheque de terceros', tipo: 'cheque'},
]).map(f => [f.codigo, f.nombre]);
const FORMAS_TIPO = {};
const FORMAS_MONEDA = {};
(FORMAS_PAGO_RAW.length ? FORMAS_PAGO_RAW : []).forEach(f => {
    FORMAS_TIPO[f.codigo] = f.tipo;
    if (f.moneda) FORMAS_MONEDA[f.codigo] = f.moneda;
});
function formaTipo(forma) {
    if (FORMAS_TIPO[forma]) return FORMAS_TIPO[forma];
    if (forma === 'efectivo') return 'efectivo';
    if (['transferencia', 'mercadopago', 'debito', 'credito'].includes(forma)) return 'banco';
    if (['tarjeta', 'tarjeta_credito', 'tarjeta_debito'].includes(forma)) return 'tarjeta';
    if (forma === 'cheque') return 'cheque';
    if (forma === 'cuenta_corriente') return 'ctacte';
    return 'otro';
}

function addPagoLine(forma) {
    const container = document.getElementById('pagosContainer');
    const div = document.createElement('div');
    div.className = 'pago-line border rounded p-2 mb-2 bg-light';
    div.innerHTML = `
        <div class="row g-1 align-items-center">
            <div class="col-7">
                <select class="form-select form-select-sm fp-forma" onchange="onPagoFormaChange(this)">
                    ${FORMAS_PAGO.map(f => {
                        const deshabilitada = f[0] === 'cuenta_corriente' && !cuentaCorrientePermitida();
                        return `<option value="${f[0]}"${forma == f[0] ? ' selected' : ''}${deshabilitada ? ' disabled' : ''}>${f[1]}${deshabilitada ? ' (no habilitada)' : ''}</option>`;
                    }).join('')}
                </select>
            </div>
            <div class="col-4">
                <div class="input-group input-group-sm"><span class="input-group-text">$</span><input class="form-control fp-monto" type="number" min="0" step="0.01" value="0" oninput="actualizarPromoEnCarrito()" /></div>
            </div>
            <div class="col-1 text-end">
                <button class="btn btn-sm btn-outline-danger fp-del" type="button" onclick="removePagoLine(this)"><i class="bi bi-x-lg"></i></button>
            </div>
        </div>
        <div class="fp-extra mt-2"></div>`;
    container.appendChild(div);
    onPagoFormaChange(div.querySelector('.fp-forma'));
    recalcTotals();
}

function removePagoLine(btn) {
    btn.closest('.pago-line').remove();
    actualizarPromoEnCarrito();
}

function onPagoFormaChange(sel) {
    setTimeout(updateEnvioCajaHint, 50);
    const line = sel.closest('.pago-line');
    const extra = line.querySelector('.fp-extra');
    const forma = sel.value;
    const tipo = formaTipo(forma);
    const montoInput = line.querySelector('.fp-monto');
    if (montoInput && tipo !== 'moneda') montoInput.readOnly = false;
    if (tipo === 'tarjeta') {
        let tarOpts = '<option value="">— Seleccionar tarjeta —</option>';
        TARJETAS.forEach(t => { tarOpts += `<option value="${t.idtarje}">${esc(t.nomtar)}</option>`; });
        let eqOpts = '<option value="">— Seleccionar equipo —</option>';
        EQUIPOS.forEach(e => { eqOpts += `<option value="${e.idequipo}">${esc(e.empresa||'Equipo')} - ${esc(String(e.idequipo))} (Suc ${esc(String(e.idsucemp))})</option>`; });
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-6"><label class="small text-muted">Tarjeta</label><select class="form-select form-select-sm fp-tarjeta">${tarOpts}</select></div>
                <div class="col-6"><label class="small text-muted">Equipo / POS</label><select class="form-select form-select-sm fp-equipo">${eqOpts}</select></div>
                <div class="col-12"><label class="small text-muted">N° de cupón</label><input class="form-control form-control-sm fp-cupon" placeholder="N° de cupón" /></div>
            </div>`;
    } else if (tipo === 'banco') {
        let bcOpts = '<option value="">— Seleccionar banco destino —</option>';
        BANCOS_CUENTAS.forEach(b => { bcOpts += `<option value="${b.id}">${esc(b.banco)} - ${esc(b.numero_cuenta||b.cbu||'')}</option>`; });
        if (BANCOS_CUENTAS.length===0) {
            BANCOS.forEach(b => { bcOpts += `<option value="${b.idban}">${esc(b.nombanc)}</option>`; });
        }
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-12"><label class="small text-muted">Banco donde se acredita</label><select class="form-select form-select-sm fp-banco-cuenta">${bcOpts}</select></div>
            </div>`;
        // Preseleccionar cuenta predeterminada para transferencias
        const sel = extra.querySelector('.fp-banco-cuenta');
        if (sel && TRANSFER_CUENTA_ID) {
            sel.value = String(TRANSFER_CUENTA_ID);
            if (!sel.value && BANCOS_CUENTAS.length>0) sel.value = String(TRANSFER_CUENTA_ID);
        }
    } else if (tipo === 'ctacte') {
        let opts = '<option value="">— Sin plazo —</option>';
        PLAZOS.forEach(p => { opts += `<option value="${p.idplazo}">${esc(p.descripcion)}</option>`; });
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-12"><label class="small text-muted">Plazo / cuotas</label>
                    <select class="form-select form-select-sm fp-plazo">${opts}</select>
                </div>
            </div>`;
    } else if (tipo === 'voucher') {
        if (montoInput) { montoInput.value = '0'; montoInput.readOnly = true; }
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-12"><label class="small text-muted">Voucher disponible — destinatario / código</label><select class="form-select form-select-sm fp-voucher" disabled><option value="">Cargando vouchers disponibles…</option></select></div>
                <div class="col-12 small fp-voucher-status text-muted">Elegí por nombre o código. El listado está ordenado alfabéticamente.</div>
            </div>`;
        const voucherSelect = extra.querySelector('.fp-voucher');
        const status = extra.querySelector('.fp-voucher-status');
        voucherSelect.addEventListener('change', function(){
            const option = voucherSelect.options[voucherSelect.selectedIndex];
            const amountCents = Number(option?.dataset.cents || 0);
            if (montoInput) montoInput.value = amountCents > 0 ? (amountCents / 100).toFixed(2) : '0';
            status.textContent = amountCents > 0 ? 'Valor del voucher: $' + fmtPrice(amountCents) + '. Se canjeará una sola vez.' : 'Elegí un voucher disponible.';
            status.className = 'col-12 small fp-voucher-status ' + (amountCents > 0 ? 'text-success' : 'text-muted');
            actualizarPromoEnCarrito();
        });
        voucherSelect.innerHTML = '<option value="">— Seleccionar voucher —</option>' + VOUCHERS_DISPONIBLES.map(v =>
            `<option value="${esc(v.codigo)}" data-cents="${Number(v.importe_cents || 0)}">${esc(v.destinatario_nombre)} — ${esc(v.codigo)} — $${fmtPrice(Number(v.importe_cents || 0))}</option>`
        ).join('');
        voucherSelect.disabled = VOUCHERS_DISPONIBLES.length === 0;
        status.textContent = VOUCHERS_DISPONIBLES.length ? 'Elegí por nombre o código. El listado está ordenado alfabéticamente.' : 'No hay vouchers disponibles para canjear.';
        status.className = 'col-12 small fp-voucher-status ' + (VOUCHERS_DISPONIBLES.length ? 'text-muted' : 'text-warning');
    } else if (tipo === 'cheque') {
        let bopts = '<option value="">— Seleccionar banco —</option>';
        BANCOS.forEach(b => { bopts += `<option value="${b.idban}">${esc(b.nombanc)}</option>`; });
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-6"><label class="small text-muted">Banco</label><select class="form-select form-select-sm fp-banco">${bopts}</select></div>
                <div class="col-6"><label class="small text-muted">N° de cheque</label><input class="form-control form-control-sm fp-chequenum" /></div>
                <div class="col-6"><label class="small text-muted">Titular</label><input class="form-control form-control-sm fp-chequetitular" /></div>
                <div class="col-6"><label class="small text-muted">CUIT</label><input class="form-control form-control-sm fp-chequecuit" /></div>
                <div class="col-6"><label class="small text-muted">Vencimiento</label><input class="form-control form-control-sm fp-chequevenc" type="date" /></div>
            </div>`;
    } else if (tipo === 'moneda') {
        const mon = FORMAS_MONEDA[forma] || '';
        extra.innerHTML = `
            <div class="row g-1">
                <div class="col-4"><label class="small text-muted">Monto (${esc(mon)})</label><input class="form-control form-control-sm fp-monto-moneda" type="number" min="0" step="0.01" /></div>
                <div class="col-4"><label class="small text-muted">Cotización $</label><input class="form-control form-control-sm fp-cotizacion" type="number" min="0" step="0.01" /></div>
                <div class="col-4"><label class="small text-muted">= Pesos</label><input class="form-control form-control-sm" id="fpConvShow" readonly /></div>
            </div>`;
        const updConv = () => {
            const mm = parseFloat(extra.querySelector('.fp-monto-moneda').value) || 0;
            const cz = parseFloat(extra.querySelector('.fp-cotizacion').value) || 0;
            const pesos = mm * cz;
            extra.querySelector('#fpConvShow').value = pesos ? pesos.toFixed(2) : '';
            if (montoInput) { montoInput.value = pesos ? pesos.toFixed(2) : 0; montoInput.readOnly = true; }
            actualizarPromoEnCarrito();
        };
        extra.querySelectorAll('.fp-monto-moneda,.fp-cotizacion').forEach(i => i.addEventListener('input', updConv));
    } else {
        extra.innerHTML = '';
    }
    actualizarPromoEnCarrito();
}

    if (EDITAR_ID <= 0) addPagoLine('efectivo');

function onEntregaChange() {
    const tipo = document.querySelector('input[name="entrega_tipo"]:checked').value;
    const box = document.getElementById('envioFields');
    box.style.display = tipo === 'envio' ? '' : 'none';
    updateEnvioCajaHint();
}
function updateEnvioCajaHint() {
    const tipo = document.querySelector('input[name="entrega_tipo"]:checked')?.value || 'local';
    const hint = document.getElementById('envioCajaHint');
    if (!hint) return;
    if (tipo === 'local') { hint.style.display='none'; return; }
    hint.style.display='';
    // check pagos to decide message
    let hasEfectivo = false, hasTransfer = false;
    document.querySelectorAll('#pagosContainer .fp-forma').forEach(sel => {
        const t = formaTipo(sel.value);
        if (t === 'efectivo') hasEfectivo = true;
        if (t !== 'efectivo') hasTransfer = true;
    });
    if (hasEfectivo && !hasTransfer) {
        hint.textContent = 'Envío con efectivo: quedará pendiente en Envíos hasta cobrar al entregar. No impacta caja aún.';
        hint.className = 'alert alert-warning py-1 px-2 small mt-2';
    } else if (hasTransfer) {
        hint.textContent = 'Envío con transferencia/link: impacta en caja inmediatamente.';
        hint.className = 'alert alert-success py-1 px-2 small mt-2';
    } else {
        hint.textContent = 'Seleccioná forma de pago. Efectivo contra entrega queda pendiente.';
        hint.className = 'alert alert-info py-1 px-2 small mt-2';
    }
}
document.addEventListener('DOMContentLoaded', onEntregaChange);

// ── Submit ──
function submitFactura() {
    if (cart.length === 0) { alert('Agregá productos al carrito.'); return; }
    const btn = document.getElementById('btnFacturar');
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Facturando...';

    const vendedorSel = document.getElementById('vendedorId');
    if (vendedorSel && (parseInt(vendedorSel.value) || 0) <= 0) {
        alert('Seleccioná el vendedor para facturar.');
        if (vendedorSel) vendedorSel.focus();
        btn.disabled = false;
        btn.innerHTML = BTN_LABEL;
        return;
    }

    const tipo = document.getElementById('tipoComprobante').value;
    const asociadaId = parseInt((document.getElementById('comprobanteAsociadoId') || {}).value) || 0;
    if ((tipo === 'NC' || tipo === 'ND') && asociadaId <= 0) {
        alert('Seleccioná el comprobante asociado (factura A, B o C).');
        btn.disabled = false;
        btn.innerHTML = BTN_LABEL;
        return;
    }
    const clienteId = parseInt(document.getElementById('clienteId').value) || 0;
    const remitoId = parseInt(document.getElementById('remitoId').value) || 0;
    const clienteErpId = parseInt(document.getElementById('clienteErpId').value) || 0;
    let clienteNombre = (clienteNombreSel || '').trim();
    if (!clienteNombre && (clienteId || clienteErpId)) {
        // Fallback: el texto visible puede traer CUIT y/o badges de categoría.
        clienteNombre = (document.getElementById('clienteNombre').textContent || '')
            .replace(/\s*\[.*$/, '')
            .replace(/\s+-\s+.*$/, '')
            .trim();
    }
    if (!clienteNombre) clienteNombre = 'Consumidor Final';
    const clienteCuit = document.getElementById('clienteCuit').textContent || '';
    const clienteCondIva = document.getElementById('clienteCondIva').value || 'consumidor_final';
    const notas = document.getElementById('facturaNotas').value;

    const descPct = parseFloat(document.getElementById('posDescuento').value) || 0;
    const totalBruto = cart.reduce((sum, item, index) => {
        const amounts = lineAmounts(item, index);
        return sum + amounts.net + amounts.iva;
    }, 0);
    const descuentoCents = descPct > 0 ? Math.round(totalBruto * descPct / 100) : 0;

    const pagos = [];
    document.querySelectorAll('#pagosContainer .pago-line').forEach(line => {
        const forma = line.querySelector('.fp-forma').value;
        const tipo = formaTipo(forma);
        const monto = parseInt(parseFloat(line.querySelector('.fp-monto').value) * 100) || 0;
        if (monto <= 0) return;
        const entry = { forma_pago: forma, monto_cents: monto };
        if (tipo === 'moneda') {
            entry.moneda = FORMAS_MONEDA[forma] || '';
            entry.monto_moneda_cents = Math.round((parseFloat(line.querySelector('.fp-monto-moneda')?.value) || 0) * 100);
            entry.cotizacion = parseFloat(line.querySelector('.fp-cotizacion')?.value) || 0;
        }
        if (tipo === 'voucher') {
            entry.voucher_code = (line.querySelector('.fp-voucher')?.value || '').trim();
        }
        if (tipo === 'tarjeta') {
            entry.cupon_numero = (line.querySelector('.fp-cupon')?.value || '').trim();
            entry.cupon_monto_cents = monto || null;
            entry.tarjeta_id = parseInt(line.querySelector('.fp-tarjeta')?.value) || null;
            entry.equipo_id = parseInt(line.querySelector('.fp-equipo')?.value) || null;
        } else if (tipo === 'banco') {
            entry.banco_cuenta_id = parseInt(line.querySelector('.fp-banco-cuenta')?.value) || null;
            // fallback si usa bancos viejos
            if (!entry.banco_cuenta_id) entry.banco_id = parseInt(line.querySelector('.fp-banco-cuenta')?.value) || null;
        } else if (tipo === 'ctacte') {
            entry.idplazo = parseInt(line.querySelector('.fp-plazo').value) || null;
        } else if (tipo === 'cheque') {
            const bancoSel = line.querySelector('.fp-banco');
            entry.cheque = {
                banco_id: parseInt(bancoSel.value) || null,
                banco: bancoSel.selectedIndex >= 0 ? bancoSel.options[bancoSel.selectedIndex].text : '',
                numero: line.querySelector('.fp-chequenum').value,
                titular: line.querySelector('.fp-chequetitular').value,
                cuit: line.querySelector('.fp-chequecuit').value,
                vencimiento: line.querySelector('.fp-chequevenc').value,
            };
            if (line.dataset.chequeId) entry.cheque_id = parseInt(line.dataset.chequeId) || null;
        }
        pagos.push(entry);
    });
    if (pagos.length === 0) {
        alert('Agregá al menos una forma de pago (con monto mayor a cero).');
        btn.disabled = false;
        btn.innerHTML = BTN_LABEL;
        return;
    }
    const totalFactura = totalBruto - descuentoCents;
    const puntosCanje = Math.max(0, parseInt(document.getElementById('posPuntosUsar').value) || 0);
    const totalSinRedondear = Math.max(0, totalFactura - puntosCanje * 100);
    const redondeado = redondearTotalFacturaCents(totalSinRedondear, tipo);
    const totalACobrar = redondeado.total;
    const totalPagado = pagos.reduce((s, p) => s + p.monto_cents, 0);
    const totalVoucher = pagos.filter(p => formaTipo(p.forma_pago) === 'voucher').reduce((s, p) => s + p.monto_cents, 0);
    if (totalVoucher > totalACobrar || (totalVoucher > 0 && totalPagado < totalACobrar)) {
        alert('El voucher se canjea por su valor completo. Agregá otro medio de pago para cubrir el saldo pendiente.');
        btn.disabled = false;
        btn.innerHTML = BTN_LABEL;
        return;
    }
    let excesoSinEfectivo = Math.max(0, totalPagado - totalACobrar);
    for (let i = pagos.length - 1; i >= 0 && excesoSinEfectivo > 0; i--) {
        if (formaTipo(pagos[i].forma_pago) !== 'efectivo') continue;
        excesoSinEfectivo -= Math.min(excesoSinEfectivo, pagos[i].monto_cents);
    }
    const promoUnitsInCart = cart.reduce((sum, item, index) => sum + promoUnitsFor(item, index), 0);
    if (promoUnitsInCart > 0 && excesoSinEfectivo > 0) {
        alert('Los medios de pago no efectivo superan el total. Ajustá el importe de transferencia, tarjeta u otros pagos al total actualizado.');
        btn.disabled = false;
        btn.innerHTML = BTN_LABEL;
        return;
    }
    if (totalPagado < totalACobrar) {
        const ok = confirm('El total pagado ($' + fmtPrice(totalPagado) + ') es menor que el total a cobrar ($' + fmtPesosEnteros(totalACobrar) + '). ¿Continuar igual?');
        if (!ok) {
            btn.disabled = false;
            btn.innerHTML = BTN_LABEL;
            return;
        }
    }

    const presupuestoId = parseInt(document.getElementById('presupuestoId').value) || 0;
    const vendedorEl = document.getElementById('vendedorId');
    const entregaTipo = document.querySelector('input[name="entrega_tipo"]:checked')?.value || 'local';
    const motivoAutorizacionEdicion = EDITAR_ID > 0
        ? (document.getElementById('motivoAutorizacionEdicion')?.value || '').trim()
        : '';
    if (EDITAR_ID > 0 && !motivoAutorizacionEdicion) {
        alert('Indicá el motivo de la solicitud de autorización.');
        btn.disabled = false;
        btn.innerHTML = BTN_LABEL;
        return;
    }
    const payload = {
        _csrf: document.getElementById('csrfToken').value,
        editar_id: EDITAR_ID,
        motivo_autorizacion: motivoAutorizacionEdicion,
        tipo_comprobante: tipo,
        comprobante_asociado_id: asociadaId > 0 ? asociadaId : null,
        forma_pago: pagos[0].forma_pago,
        entrega: {
            tipo: entregaTipo,
            transporte: document.getElementById('transporteSelect')?.value || null,
            direccion: document.getElementById('envioDireccion')?.value || '',
            observacion: document.getElementById('envioObs')?.value || '',
        },
        remito_id: remitoId,
        presupuesto_id: presupuestoId,
        pedido_id: parseInt((document.getElementById('pedidoId') || {}).value) || 0,
        vendedor_id: vendedorEl ? parseInt(vendedorEl.value) || null : null,
        notas: notas,
        fecha: EDITAR_ID > 0 && EDITAR_FACTURA && EDITAR_FACTURA.fecha ? EDITAR_FACTURA.fecha : (function() { var d = new Date(); return d.getFullYear() + '-' + String(d.getMonth() + 1).padStart(2, '0') + '-' + String(d.getDate()).padStart(2, '0'); })(),
        descuento_cents: descuentoCents,
        redondeo_cents: redondeado.redondeo,
        puntos_usados: parseInt(document.getElementById('posPuntosUsar').value) || 0,
        cliente: {
            id: clienteId || null,
            idclien: clienteErpId || null,
            nombre: clienteNombre,
            cuit: clienteCuit,
            condicion_iva: clienteCondIva,
        },
        items: cart.map((item, index) => ({
            idprodu: item.idprodu,
            idcodgusto: item.idcodgusto,
            producto: item.producto,
            variedad: item.variedad,
            qty: item.qty,
            unit_price_cents: precioActivo(item),
            promo_pos_version: item.promo_pos_version || '',
            promo_pos_units: promoUnitsFor(item, index),
            iva_rate: item.iva_rate,
            descuento_pct: lineDto(item.dto),
        })),
        pagos: pagos,
    };

    fetch(EDITAR_ID > 0 ? '/admin/facturas/actualizar' : '/admin/facturas/guardar', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload),
    })
    .then(r => r.text().then(text => {
        try {
            const res = JSON.parse(text);
            if (res.ok) {
                if (res.pending) {
                    alert('Solicitud de edición #' + res.request_id + ' enviada. La factura queda sin cambios hasta que otro administrador la apruebe.');
                    window.location.href = '/admin/facturas/' + res.id;
                    return;
                }
                if (res.arca && res.arca.cae) {
                    var fmt = localStorage.getItem('perfushopping_print_format') || '80mm';
                    window.location.href = '/admin/facturas/imprimir/' + res.id + '?auto=1&formato=' + fmt;
                } else {
                    window.location.href = '/admin/facturas/' + res.id;
                }
            } else {
                alert(res.error || 'Error al facturar');
                btn.disabled = false;
                btn.innerHTML = BTN_LABEL;
            }
        } catch (e) {
            alert('Error del servidor: ' + text.substring(0, 300));
            btn.disabled = false;
            btn.innerHTML = BTN_LABEL;
        }
    }))
    .catch(err => {
        alert('Error de conexión: ' + err.message);
        btn.disabled = false;
        btn.innerHTML = BTN_LABEL;
    });
}

function esc(s) { return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }
</script>
