<?php
// Variables disponibles desde el padre:
// $title, $showNavbar, $csvExists, $csvModTime, $csvSize, $recordCount

// Botón de acción principal
$actionBtn = $csvExists
    ? '<div class="text-center">
          <a href="/admin/whatsApp-catalog/download" class="btn btn-success" download>
              <i class="fas fa-download"></i> Descargar catálogo
          </a>
          <a href="/admin/whatsApp-catalog/generate" class="btn btn-secondary">Re-generar</a>
       </div>'
    : '<div class="text-center">
          <a href="/admin/whatsApp-catalog/generate" class="btn btn-primary">
              <i class="fas fa-download"></i> Generar catálogo ahora
          </a>
       </div>';

// Estado del catálogo
$statusBadge = $csvExists
    ? '<span class="badge bg-success">Generado</span>'
    : '<span class="badge bg-warning">No generado</span>';

// Información del archivo
?>
<!-- Empieza el contenido con margin-left para compensar sidebar fixed de 250px -->
<div style="margin-left: 250px; padding-left: 0; width: calc(100% - 250px);">
    <div class="row">
        <div class="col-12">
            <h2 class="text-center mb-4"><?php echo htmlspecialchars($title); ?></h2>
        </div>
    </row>
    
    <div class="row mt-3 justify-content-center">
        <div class="col-12 col-md-9 col-lg-7">
            <div class="card">
                <div class="card-body p-0">
                    <h5 class="card-title text-center py-3">Información del catálogo</h5>
                    <hr class="m-0">
                    <p><strong>Archivo:</strong> catalog_products.csv</p>
                    <p><strong>Última actualización:</strong> <?php echo htmlspecialchars($csvModTime); ?></p>
                    <p><strong>Tamaño:</strong> <?php echo htmlspecialchars($csvSize); ?></p>
                    <p><strong>Número de productos:</strong> <?php echo $recordCount; ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4 justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body p-0">
                <h5 class="card-title text-center py-3">Estado</h5>
                <hr class="m-0">
                <?php echo $statusBadge; ?>
            </div>
        </div>
    </div>
</div>

<hr class="w-75 mx-auto">

<div class="row mt-4 justify-content-center">
    <div class="col-12 col-md-7 col-lg-5">
        <div class="card">
            <div class="card-body p-0">
                <h5 class="card-title text-center py-3">Acciones</h5>
                <hr class="m-0">
                <?php echo $actionBtn; ?>
            </div>
        </div>
    </div>
</div>

<hr class="w-75 mx-auto">

<div class="row mt-4 justify-content-center">
    <div class="col-12 col-md-9 col-lg-7">
        <div class="card">
            <div class="card-body p-0">
                <h5 class="card-title text-center py-3">Vista previa de datos (primeros 5 registros)</h5>
                <hr class="m-0">
                <?php if ($csvExists && $recordCount > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm mb-0">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Título</th>
                                    <th>Precio</th>
                                    <th>Disponibilidad</th>
                                    <th>Condición</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $handle = fopen(self::$_SESSION['csv_path'] ?? 'catalog_products.csv', 'r');
                                if ($handle) {
                                    // Skip header
                                    fgetcsv($handle, 8192, ',');
                                    $count = 0;
                                    while (($row = fgetcsv($handle, 8192, ',')) !== false && $count < 5) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($row[0] ?? '') . '</td>';
                                        echo '<td>' . htmlspecialchars(substr($row[1] ?? '', 0, 30) . '...') . '</td>';
                                        echo '<td>' . htmlspecialchars($row[5] ?? '0.00 USD') . '</td>';
                                        echo '<td>' . htmlspecialchars($row[3] ?? 'out of stock') . '</td>';
                                        echo '<td>' . htmlspecialchars($row[4] ?? 'new') . '</td>';
                                        echo '</tr>';
                                        $count++;
                                    }
                                    fclose($handle);
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p>No hay datos para mostrar. Genere el catálogo primero.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<hr class="w-75 mx-auto">

<div class="row mt-4 justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body p-0">
                <h5 class="card-title text-center py-3">Configuración de actualización automática</h5>
                <hr class="m-0">
                <p>El catálogo puede actualizarse automáticamente mediante <strong>cron job</strong> en el servidor.</p>
                <div class="alert alert-secondary">
                    <code>*/30 * * * * cd /ruta/perfushopping/web && php src/Admin/WhatsappCatalog/Generator.php > /dev/null 2>&1</code>
                </div>
                <p>O usando el controlador PHP:</p>
                <div class="alert alert-info">
                    <code>curl -X GET http://TU_DOMINIO/admin/whatsApp-catalog/generate</code>
                </p>
                <p><small>Ubicación del script: <code>src/Admin/WhatsappCatalog/Generator.php</code></small></p>
            </div>
        </div>
    </div>
</div>