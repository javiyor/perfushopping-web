<?php
$quiz = $quiz ?? null;
$questions = $questions ?? [];
$rules = $rules ?? [];
$id = $quiz ? (int)$quiz['id'] : 0;
?>
<div class="page-title">
  <h2><?= $quiz ? 'Editar recomendador' : 'Nuevo recomendador' ?></h2>
</div>

<div class="card shadow-sm mb-3">
  <div class="card-body small">
    <h6 class="fw-semibold">¿Cómo funciona?</h6>
    <p class="mb-1">Un recomendador le hace preguntas al usuario y, según sus respuestas, genera un listado de <strong>tags</strong>. Luego compara esos tags con los tags de productos/rutinas y aplica <strong>reglas</strong> de puntaje para mostrar los mejores resultados.</p>
    <ol class="mb-1 ps-3">
      <li><strong>Datos:</strong> completá nombre (interno), slug (URL) y título público. Guardá para desbloquear las otras pestañas.</li>
      <li><strong>Preguntas:</strong> agregá preguntas cortas (ej. “¿Cómo sentís tu cabello?”). Para cada pregunta, agregá opciones de respuesta.</li>
      <li><strong>Tags por opción:</strong> cada opción debe devolver un tag en formato <code>taxonomy_key:term_value</code> (ej. <code>need:cabello_danado</code> o <code>hair_type:rizado</code>). Esos valores deben existir en las taxonomías del sistema.</li>
      <li><strong>Reglas:</strong> si el usuario selecciona ciertos tags, podés forzar que suba de puntaje un producto o rutina específica. Ej. si selecciona <code>need:cabello_danado</code>, sumar 20 puntos a la rutina “Reparación intensa”.</li>
      <li><strong>Publicar:</strong> el recomendador queda disponible en <code>/encontra-tu-rutina/{slug}</code>.</li>
    </ol>
    <p class="mb-0 text-muted">Tip: mantené entre 3 y 7 preguntas. Usá lenguaje simple y opciones mutuamente excluyentes dentro de cada pregunta.</p>
  </div>
</div>

<ul class="nav nav-tabs mb-3" role="tablist">
  <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-datos" type="button">Datos</button></li>
  <?php if ($quiz): ?>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-preguntas" type="button">Preguntas</button></li>
  <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-reglas" type="button">Reglas</button></li>
  <?php endif; ?>
</ul>

<div class="tab-content">
  <div class="tab-pane fade show active" id="tab-datos">
    <div class="card shadow-sm">
      <div class="card-body">
        <form method="post" action="/admin/marketing/recomendadores/guardar">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
          <input type="hidden" name="id" value="<?= $id ?>" />
          <div class="row g-3 compact-form">
            <div class="col-md-4"><label class="form-label small">Nombre</label><input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)($quiz['name'] ?? '')) ?>" required /></div>
            <div class="col-md-4"><label class="form-label small">Slug</label><input class="form-control form-control-sm" name="slug" value="<?= htmlspecialchars((string)($quiz['slug'] ?? '')) ?>" /></div>
            <div class="col-md-4"><label class="form-label small">Título público</label><input class="form-control form-control-sm" name="title" value="<?= htmlspecialchars((string)($quiz['title'] ?? '')) ?>" required /></div>
            <div class="col-md-2"><label class="form-label small">Orden</label><input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)($quiz['sort_order'] ?? 0) ?>" /></div>
            <div class="col-md-2"><div class="form-check mt-4"><input class="form-check-input" type="checkbox" name="active" id="active" <?= (($quiz['active'] ?? 1) == 1) ? 'checked' : '' ?> /><label class="form-check-label" for="active">Activo</label></div></div>
            <div class="col-12"><label class="form-label small">Descripción</label><textarea class="form-control form-control-sm" name="description" rows="3"><?= htmlspecialchars((string)($quiz['description'] ?? '')) ?></textarea></div>
          </div>
          <div class="mt-3">
            <button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-check-lg"></i> Guardar</button>
            <a class="btn btn-outline-secondary btn-sm" href="/admin/marketing/recomendadores">Cancelar</a>
          </div>
        </form>
      </div>
    </div>
  </div>

  <?php if ($quiz): ?>
  <div class="tab-pane fade" id="tab-preguntas">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Nueva pregunta</div>
      <div class="card-body">
        <form method="post" action="/admin/marketing/recomendadores/pregunta/guardar">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
          <input type="hidden" name="quiz_id" value="<?= $id ?>" />
          <div class="row g-2 compact-form">
            <div class="col-md-8"><input class="form-control form-control-sm" name="question" placeholder="Pregunta" required /></div>
            <div class="col-md-2"><input type="number" class="form-control form-control-sm" name="sort_order" placeholder="Orden" /></div>
            <div class="col-md-2"><button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-plus-lg"></i> Agregar</button></div>
          </div>
        </form>
      </div>
    </div>

    <?php foreach ($questions as $q): ?>
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold d-flex justify-content-between align-items-center">
        <span><?= htmlspecialchars((string)$q['question']) ?></span>
        <form method="post" action="/admin/marketing/recomendadores/pregunta/eliminar" style="display:inline" onsubmit="return confirm('¿Eliminar pregunta?')">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
          <input type="hidden" name="quiz_id" value="<?= $id ?>" />
          <input type="hidden" name="id" value="<?= (int)$q['id'] ?>" />
          <button class="btn btn-sm btn-outline-danger" type="submit"><i class="bi bi-trash"></i></button>
        </form>
      </div>
      <div class="card-body">
        <h6 class="fw-semibold small">Opciones</h6>
        <?php foreach ($q['options'] as $opt): ?>
        <form method="post" action="/admin/marketing/recomendadores/opcion/guardar" class="row g-2 compact-form align-items-end mb-2 border-bottom pb-2">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
          <input type="hidden" name="quiz_id" value="<?= $id ?>" />
          <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>" />
          <input type="hidden" name="id" value="<?= (int)$opt['id'] ?>" />
          <div class="col-md-3"><input class="form-control form-control-sm" name="label" value="<?= htmlspecialchars((string)$opt['label']) ?>" placeholder="Texto" required /></div>
          <div class="col-md-2"><input class="form-control form-control-sm" name="tags[taxonomy_key][]" value="<?= htmlspecialchars((string)($opt['tags'][0]['taxonomy_key'] ?? '')) ?>" placeholder="taxonomy" /></div>
          <div class="col-md-2"><input class="form-control form-control-sm" name="tags[term_value][]" value="<?= htmlspecialchars((string)($opt['tags'][0]['term_value'] ?? '')) ?>" placeholder="valor" /></div>
          <div class="col-md-2"><input type="number" class="form-control form-control-sm" name="sort_order" value="<?= (int)$opt['sort_order'] ?>" placeholder="Orden" /></div>
          <div class="col-md-2"><button class="btn btn-accent btn-sm" type="submit">Guardar</button></div>
          <div class="col-md-1">
            <button type="submit" formaction="/admin/marketing/recomendadores/opcion/eliminar" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
          </div>
        </form>
        <?php endforeach; ?>

        <form method="post" action="/admin/marketing/recomendadores/opcion/guardar" class="row g-2 compact-form align-items-end mt-2">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
          <input type="hidden" name="quiz_id" value="<?= $id ?>" />
          <input type="hidden" name="question_id" value="<?= (int)$q['id'] ?>" />
          <div class="col-md-3"><input class="form-control form-control-sm" name="label" placeholder="Nueva opción" required /></div>
          <div class="col-md-2"><input class="form-control form-control-sm" name="tags[taxonomy_key][]" placeholder="taxonomy" /></div>
          <div class="col-md-2"><input class="form-control form-control-sm" name="tags[term_value][]" placeholder="valor" /></div>
          <div class="col-md-2"><input type="number" class="form-control form-control-sm" name="sort_order" placeholder="Orden" /></div>
          <div class="col-md-3"><button class="btn btn-outline-secondary btn-sm" type="submit"><i class="bi bi-plus-lg"></i> Agregar opción</button></div>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="tab-pane fade" id="tab-reglas">
    <div class="card shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold">Nueva regla</div>
      <div class="card-body">
        <form method="post" action="/admin/marketing/recomendadores/regla/guardar" class="row g-2 compact-form align-items-end">
          <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
          <input type="hidden" name="quiz_id" value="<?= $id ?>" />
          <div class="col-md-3"><input class="form-control form-control-sm" name="name" placeholder="Nombre" required /></div>
          <div class="col-md-2"><input class="form-control form-control-sm" name="conditions[taxonomy_key][]" placeholder="taxonomy" /></div>
          <div class="col-md-2"><input class="form-control form-control-sm" name="conditions[term_value][]" placeholder="valor" /></div>
          <div class="col-md-2">
            <select class="form-select form-select-sm" name="target_type">
              <option value="product">Producto</option>
              <option value="routine">Rutina</option>
            </select>
          </div>
          <div class="col-md-1"><input type="number" class="form-control form-control-sm" name="target_id" placeholder="ID" required /></div>
          <div class="col-md-1"><input type="number" class="form-control form-control-sm" name="score" placeholder="Score" value="10" /></div>
          <div class="col-md-1"><button class="btn btn-accent btn-sm" type="submit"><i class="bi bi-plus-lg"></i></button></div>
        </form>
      </div>
    </div>

    <?php foreach ($rules as $rule): ?>
    <form method="post" action="/admin/marketing/recomendadores/regla/guardar" class="row g-2 compact-form align-items-end mb-2 border-bottom pb-2">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
      <input type="hidden" name="quiz_id" value="<?= $id ?>" />
      <input type="hidden" name="id" value="<?= (int)$rule['id'] ?>" />
      <div class="col-md-3"><input class="form-control form-control-sm" name="name" value="<?= htmlspecialchars((string)$rule['name']) ?>" /></div>
      <div class="col-md-2"><input class="form-control form-control-sm" name="conditions[taxonomy_key][]" value="<?= htmlspecialchars((string)($rule['conditions'][0]['taxonomy_key'] ?? '')) ?>" /></div>
      <div class="col-md-2"><input class="form-control form-control-sm" name="conditions[term_value][]" value="<?= htmlspecialchars((string)($rule['conditions'][0]['term_value'] ?? '')) ?>" /></div>
      <div class="col-md-2">
        <select class="form-select form-select-sm" name="target_type">
          <option value="product" <?= ($rule['target_type'] ?? '') === 'product' ? 'selected' : '' ?>>Producto</option>
          <option value="routine" <?= ($rule['target_type'] ?? '') === 'routine' ? 'selected' : '' ?>>Rutina</option>
        </select>
      </div>
      <div class="col-md-1"><input type="number" class="form-control form-control-sm" name="target_id" value="<?= (int)$rule['target_id'] ?>" /></div>
      <div class="col-md-1"><input type="number" class="form-control form-control-sm" name="score" value="<?= (int)$rule['score'] ?>" /></div>
      <div class="col-md-1"><button class="btn btn-accent btn-sm" type="submit">Guardar</button></div>
      <div class="col-md-1"><button type="submit" formaction="/admin/marketing/recomendadores/regla/eliminar" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button></div>
    </form>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
