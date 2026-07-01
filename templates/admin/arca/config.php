<?php
$config = $config ?? [];
$ambientes = ['homologacion' => 'Homologación (pruebas)', 'produccion' => 'Producción'];

$certPath = $config['cert_path'] ?? '';
$keyPath = $config['key_path'] ?? '';
$csrPendiente = $config['csr_pendiente'] ?? '';

$certExiste = $certPath !== '' && is_file($certPath);
$keyExiste = $keyPath !== '' && is_file($keyPath);

$certInfo = null;
if ($certExiste) {
    $certData = @openssl_x509_read(file_get_contents($certPath));
    if ($certData) {
        $certInfo = openssl_x509_parse($certData);
    }
}
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Configuración ARCA</h4>
        <p class="text-muted small">Certificados y conexión con ARCA (ex AFIP)</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/arca">Volver</a>
</div>

<?php if ($csrPendiente !== ''): ?>
<div class="alert alert-info mb-3">
    <h6 class="alert-heading fw-semibold"><i class="bi bi-file-text"></i> CSR pendiente de certificar</h6>
    <p class="small mb-2">Copiá el siguiente contenido y pegálo en el portal de ARCA (<strong>Administrador de Certificados Digitales &rarr; Solicitar certificado</strong>). Una vez que obtengas el certificado, subílo usando el formulario de más abajo.</p>
    <textarea class="form-control font-monospace small" rows="8" readonly onclick="this.select()"><?= htmlspecialchars($csrPendiente) ?></textarea>
</div>
<?php endif; ?>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Configuración general</div>
            <div class="card-body">
                <form method="post" action="/admin/arca/config/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <input type="hidden" name="cert_path" value="<?= htmlspecialchars($certPath) ?>" />
                    <input type="hidden" name="key_path" value="<?= htmlspecialchars($keyPath) ?>" />

                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" name="habilitado" value="1" id="habilitadoSwitch" <?= ($config['habilitado'] ?? '0') === '1' ? 'checked' : '' ?> />
                        <label class="form-check-label" for="habilitadoSwitch">Habilitar envío automático a ARCA</label>
                        <div class="form-text">Al emitir una factura, se enviará automáticamente a ARCA para obtener el CAE.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ambiente</label>
                        <select class="form-select" name="ambiente">
                            <?php foreach ($ambientes as $v => $l): ?>
                                <option value="<?= htmlspecialchars($v) ?>" <?= ($config['ambiente'] ?? '') === $v ? 'selected' : '' ?>><?= htmlspecialchars($l) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Usar "Homologación" para pruebas, "Producción" cuando estés operativo.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">CUIT del contribuyente</label>
                        <input class="form-control" name="cuit" value="<?= htmlspecialchars($config['cuit'] ?? '') ?>" placeholder="Ej: 20271234567" />
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Guardar configuración</button>
                </form>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Certificado digital</div>
            <div class="card-body">
                <div class="d-flex align-items-center gap-3 mb-3 p-3 bg-light rounded">
                    <div class="text-center">
                        <div class="mb-1"><?= $keyExiste ? '<span class="text-success"><i class="bi bi-check-circle-fill fs-4"></i></span>' : '<span class="text-danger"><i class="bi bi-x-circle-fill fs-4"></i></span>' ?></div>
                        <div class="small fw-semibold">Clave privada</div>
                        <div class="small text-muted"><?= $keyExiste ? 'Existente' : 'Pendiente' ?></div>
                    </div>
                    <div class="text-center">
                        <div class="mb-1"><?= $certExiste ? '<span class="text-success"><i class="bi bi-check-circle-fill fs-4"></i></span>' : '<span class="text-danger"><i class="bi bi-x-circle-fill fs-4"></i></span>' ?></div>
                        <div class="small fw-semibold">Certificado</div>
                        <div class="small text-muted"><?= $certExiste ? 'Existente' : 'Pendiente' ?></div>
                    </div>
                </div>

                <hr />
                <h6 class="fw-semibold mb-2">1. Generar solicitud (CSR)</h6>
                <p class="small text-muted mb-2">Generá el Certificate Signing Request para solicitar el certificado en el portal de ARCA.</p>
                <form method="post" action="/admin/arca/generar-csr">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <button class="btn btn-outline-primary" type="submit"><i class="bi bi-file-earmark-lock"></i> Generar CSR</button>
                </form>

                <hr />
                <h6 class="fw-semibold mb-2">2. Subir certificado firmado</h6>
                <p class="small text-muted mb-2">Una vez que ARCA emita el certificado, subílo aquí.</p>
                <form method="post" action="/admin/arca/cargar-certificado" enctype="multipart/form-data">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />
                    <div class="mb-2">
                        <input class="form-control form-control-sm" type="file" name="certificado" accept=".crt,.cer,.pem" required />
                    </div>
                    <button class="btn btn-outline-success" type="submit"><i class="bi bi-upload"></i> Subir certificado</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <?php if ($certInfo): ?>
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">Detalles del certificado</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-sm-5">Sujeto</dt>
                    <dd class="col-sm-7 text-muted"><?= htmlspecialchars($certInfo['subject']['CN'] ?? '—') ?></dd>
                    <dt class="col-sm-5">Emisor</dt>
                    <dd class="col-sm-7 text-muted"><?= htmlspecialchars($certInfo['issuer']['CN'] ?? '—') ?></dd>
                    <dt class="col-sm-5">Válido desde</dt>
                    <dd class="col-sm-7 text-muted"><?= isset($certInfo['validFrom_time_t']) ? date('d/m/Y', $certInfo['validFrom_time_t']) : '—' ?></dd>
                    <dt class="col-sm-5">Válido hasta</dt>
                    <dd class="col-sm-7 text-muted"><?= isset($certInfo['validTo_time_t']) ? date('d/m/Y', $certInfo['validTo_time_t']) : '—' ?></dd>
                    <dt class="col-sm-5">Serial</dt>
                    <dd class="col-sm-7 text-muted" style="font-size:10px;word-break:break-all"><?= htmlspecialchars($certInfo['serialNumber'] ?? '—') ?></dd>
                </dl>
            </div>
        </div>
        <?php endif; ?>

        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">¿Cómo obtener el certificado?</div>
            <div class="card-body small">
                <ol class="mb-0 ps-3">
                    <li class="mb-1">Completar CUIT y ambiente en "Configuración general" y guardar</li>
                    <li class="mb-1">Hacer clic en <strong>"Generar CSR"</strong> — se crea la clave privada y la solicitud</li>
                    <li class="mb-1">Copiar el CSR generado (texto que aparece arriba)</li>
                    <li class="mb-1">Ingresar al portal ARCA con CUIT y clave fiscal</li>
                    <li class="mb-1">Administrador de Certificados Digitales &rarr; Solicitar certificado</li>
                    <li class="mb-1">Pegar el CSR, completar el formulario y obtener el certificado (.crt)</li>
                    <li class="mb-1">Volver a esta página y subir el certificado con <strong>"Subir certificado"</strong></li>
                </ol>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Endpoint de servicios</div>
            <div class="card-body small">
                <dl class="row mb-0">
                    <dt class="col-sm-5">WSAA (auth)</dt>
                    <dd class="col-sm-7 text-muted" style="font-size:11px;word-break:break-all">
                        <?= ($config['ambiente'] ?? '') === 'homologacion'
                            ? 'https://wsaahomo.afip.gov.ar/ws/services/LoginCms'
                            : 'https://wsaa.afip.gov.ar/ws/services/LoginCms' ?>
                    </dd>
                    <dt class="col-sm-5">WSFE (FE)</dt>
                    <dd class="col-sm-7 text-muted" style="font-size:11px;word-break:break-all">
                        <?= ($config['ambiente'] ?? '') === 'homologacion'
                            ? 'https://wswhomo.afip.gov.ar/wsfe/service.asmx'
                            : 'https://servicios1.afip.gov.ar/wsfe/service.asmx' ?>
                    </dd>
                </dl>
            </div>
        </div>
    </div>
</div>
