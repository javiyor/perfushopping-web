<div class="page">
  <h2 style="margin:0 0 10px">Admin</h2>
  <a class="btn secondary" href="/admin/productos">Productos / imagenes</a>
  <a class="btn" href="/admin/wholesale">Solicitudes mayoristas</a>
  <a class="btn secondary" href="/admin/withdrawals" style="margin-left:10px">Retiros</a>
  <a class="btn secondary" href="/admin/demo-tecnica" style="margin-left:10px">Demo tecnica</a>
  <a class="btn secondary" href="/admin/demo-tecnica/horarios" style="margin-left:10px">Horarios demo</a>

  <form method="post" action="/admin/affiliate/release" style="margin-top:12px">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\Perfushopping\Web\Support\Csrf::token()) ?>" />
    <button class="btn secondary" type="submit">Liberar comisiones (pendientes)</button>
  </form>
</div>
