-- Ubicaciones del personal enviadas desde la app (cada 5 minutos).
-- La tabla se crea sola al usar la pantalla; este patch es para despliegues manuales.
CREATE TABLE IF NOT EXISTS admin_ubicaciones (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id INT UNSIGNED NOT NULL,
    lat DECIMAL(10, 7) NOT NULL,
    lng DECIMAL(10, 7) NOT NULL,
    accuracy INT DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_user_fecha (admin_user_id, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS admin_ubicacion_tokens (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    admin_user_id INT UNSIGNED NOT NULL,
    dispositivo VARCHAR(80) NOT NULL DEFAULT '',
    token VARCHAR(64) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    KEY idx_user (admin_user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
