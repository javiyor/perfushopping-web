<?php
$config = $config ?? [];
$ambientes = ['homologacion' => 'Homologación (pruebas)', 'produccion' => 'Producción'];
?>
<div class="d-flex justify-content-between align-items-start mb-3">
    <div>
        <h4 class="fw-bold mb-1">Configuración ARCA</h4>
        <p class="text-muted small">Certificados y conexión con ARCA (ex AFIP)</p>
    </div>
    <a class="btn btn-outline-secondary btn-sm" href="/admin/arca">Volver</a>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white fw-semibold">Configuración general</div>
            <div class="card-body">
                <form method="post" action="/admin/arca/config/guardar">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf ?? '') ?>" />

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

                    <hr />
                    <h6 class="fw-semibold mb-3">Certificado digital (X.509)</h6>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ruta del certificado (.crt)</label>
                        <input class="form-control" name="cert_path" value="<?= htmlspecialchars($config['cert_path'] ?? '') ?>" placeholder="Ej: /etc/afip/cert.crt" />
                        <div class="form-text">Ruta absoluta en el servidor al archivo del certificado emitido por ARCA.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Ruta de la clave privada (.key)</label>
                        <input class="form-control" name="key_path" value="<?= htmlspecialchars($config['key_path'] ?? '') ?>" placeholder="Ej: /etc/afip/key.key" />
                        <div class="form-text">Ruta absoluta a la clave privada (sin passphrase o con passphrase).</div>
                    </div>

                    <button class="btn btn-accent" type="submit"><i class="bi bi-check-lg"></i> Guardar configuración</button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm mb-3">
            <div class="card-header bg-white fw-semibold">¿Cómo obtener el certificado?</div>
            <div class="card-body small">
                <ol class="mb-0 ps-3">
                    <li class="mb-1">Generar clave privada y CSR con OpenSSL</li>
                    <li class="mb-1">Ingresar al portal ARCA con CUIT y clave fiscal</li>
                    <li class="mb-1">Administrador de Certificados Digitales &rarr; Solicitar</li>
                    <li class="mb-1">Subir el CSR y obtener el certificado (.crt)</li>
                    <li class="mb-1">Configurar las rutas en este formulario</li>
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
