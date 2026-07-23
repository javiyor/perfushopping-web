<?php $list = $list ?? []; ?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Promo Tarjetas</h4>
        <p class="text-muted small">Gestioná las promociones bancarias que se muestran en la landing pública</p>
    </div>
    <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#promoModal" onclick="editarPromo()">
        <i class="bi bi-plus-lg"></i> Nueva promo
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-admin table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Banco</th>
                    <th>Tipo</th>
                    <th>Descripción</th>
                    <th>Vigencia</th>
                    <th>Público</th>
                    <th style="width:100px">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$list): ?>
                    <tr><td colspan="7" class="text-muted text-center">Sin promociones cargadas.</td></tr>
                <?php else: ?>
                    <?php foreach ($list as $item):
                        $itemId = (int)($item['id'] ?? 0);
                        $desde = (string)($item['fecha_desde'] ?? '');
                        $hasta = (string)($item['fecha_hasta'] ?? '');
                        $vigente = ($desde === '' || $desde <= date('Y-m-d')) && ($hasta === '' || $hasta >= date('Y-m-d'));
                    ?>
                    <tr>
                        <td><strong>#<?= $itemId ?></strong></td>
                        <td><?= htmlspecialchars((string)($item['banco'] ?? '')) ?></td>
                        <td><span class="badge bg-<?= ((string)($item['tipo_tarjeta'] ?? '') === 'credito') ? 'warning text-dark' : 'info' ?>"><?= htmlspecialchars((string)($item['tipo_tarjeta'] ?? '')) ?></span></td>
                        <td><?= htmlspecialchars(mb_substr((string)($item['descripcion'] ?? ''), 0, 60)) ?></td>
                        <td class="small">
                            <?php if ($desde !== '' && $hasta !== ''): ?>
                                <?= htmlspecialchars($desde) ?> → <?= htmlspecialchars($hasta) ?>
                                <?php if ($vigente): ?>
                                    <span class="badge bg-success">Vigente</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">Vencida</span>
                                <?php endif; ?>
                            <?php elseif ($desde !== ''): ?>
                                Desde <?= htmlspecialchars($desde) ?>
                            <?php elseif ($hasta !== ''): ?>
                                Hasta <?= htmlspecialchars($hasta) ?>
                            <?php else: ?>
                                <span class="text-muted">Sin fecha</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ((int)($item['publicado'] ?? 0) === 1): ?>
                                <span class="badge bg-success">Sí</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">No</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button class="btn btn-sm btn-outline-secondary py-0 px-1" onclick="editarPromo(<?= htmlspecialchars(json_encode($item)) ?>)"><i class="bi bi-pencil"></i></button>
                                <form method="post" action="/admin/promo-tarjetas/delete" onsubmit="return confirm('Eliminar esta promo?')">
                                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                                    <input type="hidden" name="id" value="<?= $itemId ?>" />
                                    <button class="btn btn-sm btn-outline-danger py-0 px-1" type="submit"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="promoModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog">
        <form method="post" action="/admin/promo-tarjetas/save" class="modal-content">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
            <input type="hidden" name="id" id="inputId" value="0" />
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva promo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2">
                    <label class="form-label small">Banco</label>
                    <input class="form-control form-control-sm" name="banco" id="inputBanco" required />
                </div>
                <div class="mb-2">
                    <label class="form-label small">Tipo de tarjeta</label>
                    <select class="form-select form-select-sm" name="tipo_tarjeta" id="inputTipo">
                        <option value="credito">Crédito</option>
                        <option value="debito">Débito</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Descripción</label>
                    <textarea class="form-control form-control-sm" name="descripcion" id="inputDescripcion" rows="2" placeholder="Ej: 3 cuotas sin interés"></textarea>
                </div>
                <div class="mb-2">
                    <label class="form-label small">Detalle de la promo</label>
                    <textarea class="form-control form-control-sm" name="detalle_promo" id="inputDetalle" rows="3" placeholder="Ej: Hasta 12 cuotas fijas con Banco XX. Descuento del 15% en compras mayores a $50.000"></textarea>
                </div>
                <div class="row g-2 mb-2">
                    <div class="col-6">
                        <label class="form-label small">Vigencia desde</label>
                        <input class="form-control form-control-sm" type="date" name="fecha_desde" id="inputFechaDesde" />
                    </div>
                    <div class="col-6">
                        <label class="form-label small">Vigencia hasta</label>
                        <input class="form-control form-control-sm" type="date" name="fecha_hasta" id="inputFechaHasta" />
                    </div>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="publicado" id="inputPublicado" value="1" />
                    <label class="form-check-label" for="inputPublicado">Publicar en la web</label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-accent">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
function editarPromo(data) {
    const modal = document.getElementById('promoModal');
    const title = document.getElementById('modalTitle');
    const inputId = document.getElementById('inputId');
    const inputBanco = document.getElementById('inputBanco');
    const inputTipo = document.getElementById('inputTipo');
    const inputDescripcion = document.getElementById('inputDescripcion');
    const inputDetalle = document.getElementById('inputDetalle');
    const inputFechaDesde = document.getElementById('inputFechaDesde');
    const inputFechaHasta = document.getElementById('inputFechaHasta');
    const inputPublicado = document.getElementById('inputPublicado');

    if (data && data.id) {
        title.textContent = 'Editar promo';
        inputId.value = data.id;
        inputBanco.value = data.banco || '';
        inputTipo.value = data.tipo_tarjeta || 'credito';
        inputDescripcion.value = data.descripcion || '';
        inputDetalle.value = data.detalle_promo || '';
        inputFechaDesde.value = data.fecha_desde || '';
        inputFechaHasta.value = data.fecha_hasta || '';
        inputPublicado.checked = parseInt(data.publicado) === 1;
    } else {
        title.textContent = 'Nueva promo';
        inputId.value = 0;
        inputBanco.value = '';
        inputTipo.value = 'credito';
        inputDescripcion.value = '';
        inputDetalle.value = '';
        inputFechaDesde.value = '';
        inputFechaHasta.value = '';
        inputPublicado.checked = false;
    }
}
</script>