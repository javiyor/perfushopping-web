-- Categorización de clientes (minorista/mayorista/profesional).
-- Las columnas se crean solas al usar la pantalla; este patch es para despliegues manuales.
SET @db = DATABASE();

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'categoria');
SET @sql = IF(@exists = 0, 'ALTER TABLE clientes ADD COLUMN categoria VARCHAR(20) NOT NULL DEFAULT \'minorista\'', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'precio_mayorista');
SET @sql2 = IF(@exists2 = 0, 'ALTER TABLE clientes ADD COLUMN precio_mayorista TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

SET @exists3 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'clientes' AND COLUMN_NAME = 'especialidad');
SET @sql3 = IF(@exists3 = 0, 'ALTER TABLE clientes ADD COLUMN especialidad VARCHAR(60) DEFAULT NULL', 'SELECT 1');
PREPARE stmt3 FROM @sql3;
EXECUTE stmt3;
DEALLOCATE PREPARE stmt3;
