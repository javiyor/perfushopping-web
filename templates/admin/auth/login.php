<?php
$sucursales = $sucursales ?? [];
?>
<div class="row justify-content-center" style="margin-top:60px">
    <div class="col-md-5 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <div class="text-center mb-4">
                    <img src="/assets/brand/logo-header.png" alt="Perfushopping" style="height:40px" />
                    <h5 class="mt-3 mb-1 fw-bold">Panel de gestión</h5>
                    <p class="text-muted small">Ingresá con tu usuario y clave</p>
                </div>

                <form method="post" action="/admin/login">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <div class="mb-3">
                        <label class="form-label">Usuario</label>
                        <input class="form-control" name="username" placeholder="Tu usuario" required autofocus />
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Clave</label>
                        <input class="form-control" type="password" name="password" placeholder="Tu clave" required />
                    </div>
                    <button class="btn btn-accent w-100" type="submit">Ingresar</button>
                </form>
            </div>
        </div>
    </div>
</div>
