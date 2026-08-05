<?php if (empty($showOnboarding)) return; ?>
<div class="card border-info shadow-sm mb-4" id="onboarding-manual">
    <div class="card-header bg-info text-white d-flex justify-content-between align-items-center py-2">
        <span><i class="bi bi-journal-text me-1"></i> Guía rápida: cómo cargar un producto</span>
        <button type="button" class="btn-close btn-close-white btn-sm" aria-label="Cerrar" onclick="document.getElementById('onboarding-manual').style.display='none'"></button>
    </div>
    <div class="card-body">
        <p class="small text-muted mb-2">Esto aparece solo en tus primeros accesos. Hacé clic en <i class="bi bi-x-lg"></i> para cerrarlo.</p>
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 small">
                <thead class="table-light">
                    <tr><th style="width:32%">Campo</th><th>Qué ingresar</th></tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong>Nombre del producto</strong> <span class="text-danger">*</span></td>
                        <td>Nombre identificador del producto (marca + modelo/presentación, ej.: <em>Perfume, Eau de Parfum 100ml</em>). Es obligatorio.</td>
                    </tr>
                    <tr>
                        <td><strong>Costo (sin IVA)</strong></td>
                        <td>Precio al que comprás el producto, sin IVA. Si lo cargás junto con los márgenes, los precios de venta se calculan solos.</td>
                    </tr>
                    <tr>
                        <td><strong>Margen minorista / mayorista</strong></td>
                        <td>Porcentaje de ganancia sobre el costo. Se usa para calcular el precio de venta (minorista para público, mayorista para ventas en volumen).</td>
                    </tr>
                    <tr>
                        <td><strong>Precio minorista / mayorista (IVA incl.)</strong></td>
                        <td>Precio final de venta con IVA incluido. Si ya cargaste costo + margen se completa automáticamente.</td>
                    </tr>
                    <tr>
                        <td><strong>Categoría</strong></td>
                        <td>Clasificación principal del producto (ej. "Fragancias"). Podés crear una nueva con el botón <i class="bi bi-plus-lg"></i>.</td>
                    </tr>
                    <tr>
                        <td><strong>Marca / Subrubro</strong></td>
                        <td>Marca o subclasificación del producto. Podés crear una nueva con el botón <i class="bi bi-plus-lg"></i>.</td>
                    </tr>
                    <tr>
                        <td><strong>Departamento</strong></td>
                        <td>Sección de la tienda en la que se ubica (ej. "Perfumería"). Podés crear uno nuevo con el botón <i class="bi bi-plus-lg"></i>.</td>
                    </tr>
                    <tr>
                        <td><strong>Proveedor</strong></td>
                        <td>Distribuidor o fábrica de quien se compra. Es opcional.</td>
                    </tr>
                    <tr>
                        <td><strong>IVA</strong></td>
                        <td>Tasa de IVA que corresponde al producto (generalmente 21%). Ajustala según el caso.</td>
                    </tr>
                    <tr>
                        <td><strong>Visible en web</strong></td>
                        <td>Si está marcado, el producto aparece en la tienda pública. Si no, solo se ve desde el panel de administración.</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>