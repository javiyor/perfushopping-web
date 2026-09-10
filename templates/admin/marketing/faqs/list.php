<?php
$faqs = $faqs ?? [];
$csrf = $csrf ?? '';
?>
<nav aria-label="breadcrumb" class="mb-2">
  <ol class="breadcrumb">
    <li class="breadcrumb-item">Contenido y Marketing</li>
    <li class="breadcrumb-item active">FAQs</li>
  </ol>
</nav>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h4 class="mb-0">Preguntas frecuentes</h4>
  <button class="btn btn-accent btn-sm" data-bs-toggle="modal" data-bs-target="#faqModal" onclick="resetFaqForm()"><i class="bi bi-plus-lg"></i> Nueva FAQ</button>
</div>

<div class="card shadow-sm">
  <div class="table-responsive">
    <table class="table table-admin table-hover mb-0">
      <thead>
        <tr>
          <th>Pregunta</th>
          <th>Respuesta</th>
          <th>Orden</th>
          <th>Activo</th>
          <th class="text-end">Acciones</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($faqs as $f): ?>
        <tr>
          <td><?= htmlspecialchars((string)$f['question']) ?></td>
          <td><?= htmlspecialchars(mb_substr(strip_tags((string)$f['answer']), 0, 80)) ?>...</td>
          <td><?= (int)$f['sort_order'] ?></td>
          <td><?= (int)$f['active'] ? '<span class="badge bg-success">Sí</span>' : '<span class="badge bg-secondary">No</span>' ?></td>
          <td class="text-end">
            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#faqModal"
              onclick='editFaq(<?= json_encode($f, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
              <i class="bi bi-pencil"></i>
            </button>
            <form method="post" action="/admin/marketing/faqs/eliminar" class="d-inline" onsubmit="return confirm('¿Eliminar?')">
              <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
              <input type="hidden" name="id" value="<?= (int)$f['id'] ?>" />
              <button class="btn btn-outline-danger btn-sm" type="submit"><i class="bi bi-trash"></i></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
        <?php if (!$faqs): ?>
        <tr><td colspan="5" class="text-muted text-center py-4">No hay FAQs cargadas.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="faqModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="post" action="/admin/marketing/faqs/guardar">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>" />
        <input type="hidden" name="id" id="faq_id" value="0" />
        <div class="modal-header">
          <h5 class="modal-title" id="faqModalLabel">Nueva FAQ</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <div class="mb-2">
            <label class="form-label small">Pregunta</label>
            <input class="form-control form-control-sm" name="question" id="faq_question" required />
          </div>
          <div class="mb-2">
            <label class="form-label small">Respuesta</label>
            <textarea class="form-control form-control-sm" name="answer" id="faq_answer" rows="4" required></textarea>
          </div>
          <div class="row g-2">
            <div class="col-md-4">
              <label class="form-label small">Orden</label>
              <input class="form-control form-control-sm" type="number" name="sort_order" id="faq_sort_order" value="0" />
            </div>
            <div class="col-md-4 d-flex align-items-end pb-1">
              <div class="form-check">
                <input class="form-check-input" type="checkbox" name="active" id="faq_active" checked />
                <label class="form-check-label small" for="faq_active">Activo</label>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-accent btn-sm">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function resetFaqForm(){
  document.getElementById('faqModalLabel').textContent = 'Nueva FAQ';
  document.getElementById('faq_id').value = '0';
  document.getElementById('faq_question').value = '';
  document.getElementById('faq_answer').value = '';
  document.getElementById('faq_sort_order').value = '0';
  document.getElementById('faq_active').checked = true;
}
function editFaq(f){
  document.getElementById('faqModalLabel').textContent = 'Editar FAQ';
  document.getElementById('faq_id').value = f.id;
  document.getElementById('faq_question').value = f.question;
  document.getElementById('faq_answer').value = f.answer;
  document.getElementById('faq_sort_order').value = f.sort_order;
  document.getElementById('faq_active').checked = !!f.active;
}
</script>
