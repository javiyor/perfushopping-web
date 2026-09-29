-- Cierre de caja por turno: imputación de documentos y saldo para próxima apertura.
-- Las columnas se crean solas al usar la pantalla; este patch es para despliegues manuales.
SET @db = DATABASE();

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'facturas' AND COLUMN_NAME = 'caja_apertura_id');
SET @sql = IF(@exists = 0, 'ALTER TABLE facturas ADD COLUMN caja_apertura_id INT UNSIGNED DEFAULT NULL, ADD KEY idx_caja_apertura (caja_apertura_id)', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'recibos' AND COLUMN_NAME = 'caja_apertura_id');
SET @sql2 = IF(@exists2 = 0, 'ALTER TABLE recibos ADD COLUMN caja_apertura_id INT UNSIGNED DEFAULT NULL, ADD KEY idx_caja_apertura (caja_apertura_id)', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @exists3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'caja_aperturas' AND COLUMN_NAME = 'monto_proxima_apertura_cents');
SET @sql3 = IF(@exists3 = 0, 'ALTER TABLE caja_aperturas ADD COLUMN monto_proxima_apertura_cents INT NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;
