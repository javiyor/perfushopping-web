-- Spooler de tickets: impresoras por punto de venta y cola de impresión.
-- Las tablas se crean solas al usar la pantalla; este patch es para despliegues manuales.
CREATE TABLE IF NOT EXISTS impresoras (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre VARCHAR(80) NOT NULL,
    punto_venta INT NOT NULL DEFAULT 0,
    sucursal_id INT UNSIGNED DEFAULT NULL,
    token VARCHAR(64) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_token (token),
    KEY idx_pv (punto_venta)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS print_jobs (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    factura_id INT UNSIGNED NOT NULL,
    punto_venta INT NOT NULL DEFAULT 0,
    sucursal_id INT UNSIGNED DEFAULT NULL,
    estado ENUM('pendiente','impreso','error') NOT NULL DEFAULT 'pendiente',
    intentos INT NOT NULL DEFAULT 0,
    mensaje VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT NULL,
    printed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pv_estado (punto_venta, estado),
    KEY idx_factura (factura_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
