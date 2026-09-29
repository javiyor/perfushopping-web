-- Formas de pago administrables (ABM en /admin/formas-pago).
-- La tabla se crea y precarga sola al usar la pantalla; este patch es para despliegues manuales.
CREATE TABLE IF NOT EXISTS formas_pago (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo VARCHAR(40) NOT NULL,
    nombre VARCHAR(80) NOT NULL,
    tipo VARCHAR(20) NOT NULL DEFAULT 'otro',
    moneda CHAR(3) DEFAULT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT NULL,
    updated_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO formas_pago (codigo, nombre, tipo, moneda, activo, orden, created_at, updated_at) VALUES
('efectivo', 'Efectivo', 'efectivo', NULL, 1, 1, NOW(), NOW()),
('transferencia', 'Transferencia bancaria', 'banco', NULL, 1, 2, NOW(), NOW()),
('tarjeta', 'Tarjetas', 'tarjeta', NULL, 1, 3, NOW(), NOW()),
('mercadopago', 'Mercado Pago', 'banco', NULL, 1, 4, NOW(), NOW()),
('cuenta_corriente', 'Cuenta corriente', 'ctacte', NULL, 1, 5, NOW(), NOW()),
('cheque', 'Cheque de terceros', 'cheque', NULL, 1, 6, NOW(), NOW()),
('billetera', 'Billetera / QR', 'banco', NULL, 1, 7, NOW(), NOW()),
('dolares', 'Dólares', 'moneda', 'USD', 1, 8, NOW(), NOW());

-- Detalle de moneda extranjera en pagos de facturas.
SET @db = DATABASE();

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'factura_pagos' AND COLUMN_NAME = 'moneda');
SET @sql = IF(@exists = 0, 'ALTER TABLE factura_pagos ADD COLUMN moneda CHAR(3) DEFAULT NULL', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'factura_pagos' AND COLUMN_NAME = 'monto_moneda_cents');
SET @sql2 = IF(@exists2 = 0, 'ALTER TABLE factura_pagos ADD COLUMN monto_moneda_cents INT DEFAULT NULL', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @exists3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'factura_pagos' AND COLUMN_NAME = 'cotizacion');
SET @sql3 = IF(@exists3 = 0, 'ALTER TABLE factura_pagos ADD COLUMN cotizacion DECIMAL(18,6) DEFAULT NULL', 'SELECT 1');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;
