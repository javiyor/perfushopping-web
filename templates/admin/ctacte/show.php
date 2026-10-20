<?php
use Perfushopping\Web\Support\Format;

$cliente = $cliente ?? null;
$saldo = (int)($saldo ?? 0);
$movimientos = $movimientos ?? [];
$facturasPendientes = $facturasPendientes ?? [];
$formasPago = $formasPago ?? [];
if (!$cliente):
?>
    <div class="alert alert-warning">Cliente no encontrado.</div>
<?php return; endif;

$origenLabels = ['factura'=>'Factura','recibo'=>'Recibo','ajuste'=>'Ajuste manual','nota_credito'=>'Nota de Crédito'];
?>
<nav aria-label="breadcrumb" class="mb-3">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="/admin/ctacte">Cuentas corrientes</a></li>
        <li class="breadcrumb-item active"><?= htmlspecialchars($cliente['name'] ?? '') ?></li>
    </ol>
</nav>

<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1"><?= htmlspecialchars($cliente['name'] ?? '') ?></h4>
        <p class="text-muted small"><?= htmlspecialchars((string)($cliente['email'] ?? '')) ?> — <?= htmlspecialchars((string)($cliente['phone'] ?? '')) ?></p>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <span class="fs-4 fw-bold <?= $saldo > 0 ? 'text-danger' : ($saldo < 0 ? 'text-success' : '') ?>">
            Saldo: <?= htmlspecialchars(Format::moneyFromCents($saldo)) ?>
        </span>
        <?php if ((int)($cliente['id'] ?? 0) > 0): ?>
            <a class="btn btn-outline-primary btn-sm" href="/admin/ctacte/ajuste/<?= (int)$cliente['id'] ?>"><i class="bi bi-pencil"></i> Ajuste manual</a>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($cliente['idclien'])): ?>
<div class="card shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><i class="bi bi-cash-coin"></i> Cobrar facturas de cuenta corriente</span>
        <span class="badge bg-secondary"><?= count($facturasPendientes) ?> pendiente(s)</span>
    </div>
    <?php if (!$facturasPendientes): ?>
        <div class="card-body small text-muted">Este cliente no tiene facturas pendientes en cuenta corriente.</div>
    <?php else: ?>
        <form method="post" action="/admin/ctacte/erp/pagar" id="cobroErpForm">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="idclien" value="<?= (int)$cliente['idclien'] ?>" />
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0 align-middle">
                    <thead><tr><th style="width:36px"></th><th>Factura</th><th>Fecha</th><th class="text-end">Total en cta. cte.</th><th class="text-end">Pendiente</th><th class="text-end" style="width:150px">Aplicar pago</th></tr></thead>
                    <tbody>
                        <?php foreach ($facturasPendientes as $f): $fid = (int)$f['id']; $pendiente = (int)($f['pendiente_cents'] ?? 0); ?>
                            <tr>
                                <td><input class="form-check-input cta-factura-check" type="checkbox" name="factura_id[]" value="<?= $fid ?>" /></td>
                                <td><a href="/admin/facturas/<?= $fid ?>" target="_blank"><?= htmlspecialchars((string)($f['codigo'] ?? ('Factura #' . $fid))) ?></a></td>
                                <td class="small"><?= htmlspecialchars((string)($f['fecha'] ?? '')) ?></td>
                                <td class="text-end"><?= htmlspecialchars(Format::moneyFromCents((int)($f['total_ctacte_cents'] ?? $f['total_cents'] ?? 0))) ?></td>
                                <td class="text-end fw-semibold"><?= htmlspecialchars(Format::moneyFromCents($pendiente)) ?></td>
                                <td><input class="form-control form-control-sm cta-factura-monto text-end" type="number" name="monto[<?= $fid ?>]" min="0.01" max="<?= number_format($pendiente / 100, 2, '.', '') ?>" step="0.01" value="<?= number_format($pendiente / 100, 2, '.', '') ?>" disabled /></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot><tr><th colspan="5" class="text-end">Total seleccionado</th><th class="text-end" id="ctaCobroTotal">$0,00</th></tr></tfoot>
                </table>
            </div>
            <div class="card-body border-top">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small">Forma de cobro</label>
                        <select class="form-select form-select-sm" name="forma_pago" id="ctaCobroForma" required>
                            <?php foreach ($formasPago as $fp): if (($fp['tipo'] ?? '') === 'moneda' || ($fp['tipo'] ?? '') === 'ctacte' || ($fp['codigo'] ?? '') === 'cuenta_corriente') continue; ?>
                                <option value="<?= htmlspecialchars((string)$fp['codigo']) ?>"><?= htmlspecialchars((string)$fp['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small">Fecha</label>
                        <input class="form-control form-control-sm" type="date" name="fecha" value="<?= date('Y-m-d') ?>" required />
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small">Notas</label>
                        <input class="form-control form-control-sm" name="notas" maxlength="1000" placeholder="Observación del cobro (opcional)" />
                    </div>
                    <div class="col-12" id="ctaCobroCheque" style="display:none">
                        <div class="row g-2">
                            <div class="col-md-3"><label class="form-label small">Banco</label><input class="form-control form-control-sm" name="cheque_banco" /></div>
                            <div class="col-md-2"><label class="form-label small">Número</label><input class="form-control form-control-sm" name="cheque_numero" /></div>
                            <div class="col-md-3"><label class="form-label small">Titular</label><input class="form-control form-control-sm" name="cheque_titular" /></div>
                            <div class="col-md-2"><label class="form-label small">CUIT</label><input class="form-control form-control-sm" name="cheque_cuit" /></div>
                            <div class="col-md-2"><label class="form-label small">Vencimiento</label><input class="form-control form-control-sm" type="date" name="cheque_vencimiento" /></div>
                            <input type="hidden" name="cheque_monto_cents" id="ctaChequeMontoCents" value="0" />
                        </div>
                    </div>
                    <div class="col-12 d-flex justify-content-end">
                        <button class="btn btn-accent" type="submit"><i class="bi bi-receipt"></i> Registrar cobro y aplicar a seleccionadas</button>
                    </div>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span>Movimientos</span>
        <span class="badge bg-secondary"><?= count($movimientos) ?> mov.</span>
    </div>
    <div class="table-responsive">
        <table class="table table-sm table-admin mb-0">
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>Tipo</th>
                    <th>Origen</th>
                    <th>Concepto</th>
                    <th class="text-end">Débito</th>
                    <th class="text-end">Crédito</th>
                    <th class="text-end">Saldo</th>
                    <th class="small text-muted">Creado por</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$movimientos): ?>
                    <tr><td colspan="8" class="text-muted text-center">Sin movimientos.</td></tr>
                <?php else: ?>
                    <?php foreach (array_reverse($movimientos) as $m): ?>
                        <tr>
                            <td class="small"><?= htmlspecialchars((string)($m['created_at'] ?? '-')) ?></td>
                            <td>
                                <?php if (($m['tipo'] ?? '') === 'debito'): ?>
                                    <span class="badge bg-danger">Debe</span>
                                <?php else: ?>
                                    <span class="badge bg-success">Haber</span>
                                <?php endif; ?>
                            </td>
                            <td class="small">
                                <?php
                                $origenLabel = $origenLabels[$m['origen'] ?? ''] ?? $m['origen'] ?? '';
                                $origenId = (int)($m['origen_id'] ?? 0);
                                if ($origenId && $m['origen'] === 'factura') {
                                    echo '<a href="/admin/facturas/' . $origenId . '">' . htmlspecialchars($origenLabel) . ' #' . $origenId . '</a> ';
                                    echo '<a class="text-nowrap" target="_blank" rel="noopener" title="Imprimir factura" href="/admin/facturas/imprimir/' . $origenId . '"><i class="bi bi-printer"></i></a>';
                                } elseif ($origenId && $m['origen'] === 'recibo') {
                                    echo '<a href="/admin/recibos/' . $origenId . '">' . htmlspecialchars($origenLabel) . ' #' . $origenId . '</a> ';
                                    echo '<a class="text-nowrap" target="_blank" rel="noopener" title="Imprimir recibo" href="/admin/recibos/imprimir/' . $origenId . '"><i class="bi bi-printer"></i></a>';
                                } else {
                                    echo htmlspecialchars($origenLabel) . ($origenId ? ' #' . $origenId : '');
                                }
                                ?>
                            </td>
                            <td class="small"><?= htmlspecialchars((string)($m['concepto'] ?? '-')) ?></td>
                            <td class="text-end"><?= ($m['tipo'] ?? '') === 'debito' ? htmlspecialchars(Format::moneyFromCents((int)($m['monto_cents'] ?? 0))) : '-' ?></td>
                            <td class="text-end"><?= ($m['tipo'] ?? '') === 'credito' ? htmlspecialchars(Format::moneyFromCents((int)($m['monto_cents'] ?? 0))) : '-' ?></td>
                            <td class="text-end fw-bold <?= ((int)($m['saldo_after_cents'] ?? 0)) > 0 ? 'text-danger' : (((int)($m['saldo_after_cents'] ?? 0)) < 0 ? 'text-success' : '') ?>">
                                <?= htmlspecialchars(Format::moneyFromCents((int)($m['saldo_after_cents'] ?? 0))) ?>
                            </td>
                            <td class="small text-muted"><?= htmlspecialchars((string)($m['created_by_nombre'] ?? '-')) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
(function() {
    var form = document.getElementById('cobroErpForm');
    if (!form) return;
    var checks = Array.from(form.querySelectorAll('.cta-factura-check'));
    var totalEl = document.getElementById('ctaCobroTotal');
    var chequeBox = document.getElementById('ctaCobroCheque');
    var chequeAmount = document.getElementById('ctaChequeMontoCents');
    function update() {
        var total = 0;
        checks.forEach(function(check) {
            var input = check.closest('tr').querySelector('.cta-factura-monto');
            input.disabled = !check.checked;
            if (check.checked) total += Math.round((parseFloat(input.value) || 0) * 100);
        });
        totalEl.textContent = '$' + (total / 100).toLocaleString('es-AR', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        chequeAmount.value = String(total);
    }
    checks.forEach(function(check) { check.addEventListener('change', update); });
    form.querySelectorAll('.cta-factura-monto').forEach(function(input) { input.addEventListener('input', update); });
    document.getElementById('ctaCobroForma').addEventListener('change', function() {
        chequeBox.style.display = this.value === 'cheque' ? '' : 'none';
    });
    form.addEventListener('submit', function(e) {
        if (!checks.some(function(check) { return check.checked; })) {
            e.preventDefault();
            alert('Tildá al menos una factura para aplicar el cobro.');
        }
    });
    update();
})();
</script>
