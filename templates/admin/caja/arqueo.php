<?php
use Perfushopping\Web\Support\Format;
$apertura = $apertura ?? null;
$ventasEfectivo = (int)($ventasEfectivo ?? 0);
$totalesMov = $totalesMov ?? ['total_ingresos' => 0, 'total_egresos' => 0];
$arqueos = $arqueos ?? [];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Arqueo de caja</h4>
        <p class="text-muted small">Conteo físico del efectivo en caja</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/caja">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Registrar arqueo</div>
            <div class="card-body">
                <form method="post" action="/admin/caja/arqueo/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Total contado (efectivo físico)</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input class="form-control" name="total_cents" type="number" required min="0" step="1" id="arqueoTotal" />
                        </div>
                        <div class="form-text">En centavos (ej: 150000 = $1.500,00)</div>
                    </div>

                    <div class="mb-3">
                        <div class="bg-light p-3 rounded small">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Saldo esperado:</span>
                                <strong id="saldoEsperado">
                                    <?php
                                    $montoInicial = (int)($apertura['monto_inicial_cents'] ?? 0);
                                    $esperado = $montoInicial + $ventasEfectivo + (int)$totalesMov['total_ingresos'] - (int)$totalesMov['total_egresos'];
                                    echo Format::moneyFromCents($esperado);
                                    ?>
                                </strong>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>Apertura:</span>
                                <span><?= Format::moneyFromCents($montoInicial) ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>+ Ventas efectivo:</span>
                                <span><?= Format::moneyFromCents($ventasEfectivo) ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>+ Ingresos extra:</span>
                                <span><?= Format::moneyFromCents((int)$totalesMov['total_ingresos']) ?></span>
                            </div>
                            <div class="d-flex justify-content-between text-muted">
                                <span>- Egresos extra:</span>
                                <span><?= Format::moneyFromCents((int)$totalesMov['total_egresos']) ?></span>
                            </div>
                            <hr class="my-1" />
                            <div class="d-flex justify-content-between fw-bold" id="diferenciaRow">
                                <span>Diferencia:</span>
                                <span id="diferenciaLabel">$0,00</span>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Observaciones</label>
                        <textarea class="form-control form-control-sm" name="observaciones" rows="2" placeholder="Ej: Billetes contados, faltante, sobrante..."></textarea>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Registrar arqueo</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Arqueos anteriores</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead>
                        <tr>
                            <th>Hora</th>
                            <th class="text-end">Total contado</th>
                            <th class="text-end">Esperado</th>
                            <th class="text-end">Diferencia</th>
                            <th>Obs.</th>
                            <th>Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$arqueos): ?>
                            <tr><td colspan="6" class="text-muted text-center">Sin arqueos registrados</td></tr>
                        <?php else: ?>
                            <?php foreach ($arqueos as $a): ?>
                                <?php $dif = (int)($a['total_cents'] ?? 0) - $esperado; ?>
                                <tr>
                                    <td class="small"><?= date('H:i', strtotime($a['created_at'] ?? '')) ?></td>
                                    <td class="text-end"><?= Format::moneyFromCents((int)($a['total_cents'] ?? 0)) ?></td>
                                    <td class="text-end"><?= Format::moneyFromCents($esperado) ?></td>
                                    <td class="text-end <?= $dif < 0 ? 'text-danger' : ($dif > 0 ? 'text-success' : '') ?>"><?= Format::moneyFromCents($dif) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars(mb_substr((string)($a['observaciones'] ?? ''), 0, 30)) ?></td>
                                    <td class="small text-muted"><?= htmlspecialchars((string)($a['created_by_nombre'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('arqueoTotal').addEventListener('input', function() {
    const total = parseInt(this.value) || 0;
    const esperado = <?= $esperado ?? 0 ?>;
    const dif = total - esperado;
    const el = document.getElementById('diferenciaLabel');
    el.textContent = (dif >= 0 ? '+' : '') + '$' + Math.abs(dif / 100).toLocaleString('es-AR', {minimumFractionDigits:2});
    el.className = dif < 0 ? 'text-danger' : dif > 0 ? 'text-success' : '';
});
</script>
