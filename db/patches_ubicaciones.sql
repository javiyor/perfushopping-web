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
