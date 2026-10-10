-- Solicitudes de corrección de apertura de caja con aprobación de administrador.
-- La tabla se crea sola al usar la pantalla; este patch es para despliegues manuales.
CREATE TABLE IF NOT EXISTS caja_apertura_ajustes (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    caja_id INT UNSIGNED NOT NULL,
    campo VARCHAR(50) NOT NULL DEFAULT 'monto_inicial_cents',
    valor_anterior_cents INT NOT NULL DEFAULT 0,
    valor_nuevo_cents INT NOT NULL DEFAULT 0,
    valor_anterior_text VARCHAR(50) DEFAULT NULL,
    valor_nuevo_text VARCHAR(50) DEFAULT NULL,
    motivo TEXT NOT NULL,
    estado ENUM('pendiente','aprobado','rechazado') NOT NULL DEFAULT 'pendiente',
    solicitado_por INT UNSIGNED DEFAULT NULL,
    resuelto_por INT UNSIGNED DEFAULT NULL,
    nota_resolucion VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    resolved_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_caja (caja_id),
    KEY idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
