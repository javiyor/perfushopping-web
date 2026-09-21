SET @db = DATABASE();

-- Agregar punto_venta_arca a admin_sucursales si no existe
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'admin_sucursales' AND COLUMN_NAME = 'punto_venta_arca');
SET @sql = IF(@exists = 0, 'ALTER TABLE admin_sucursales ADD COLUMN punto_venta_arca INT UNSIGNED DEFAULT NULL AFTER punto_venta', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Agregar sucursal_id a facturas si no existe
SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'facturas' AND COLUMN_NAME = 'sucursal_id');
SET @sql2 = IF(@exists2 = 0, 'ALTER TABLE facturas ADD COLUMN sucursal_id INT UNSIGNED DEFAULT NULL AFTER punto_venta', 'SELECT 1');
PREPARE stmt2 FROM @sql2;
EXECUTE stmt2;
DEALLOCATE PREPARE stmt2;

-- Inicializar puntos de venta ARCA según mapeo acordado
UPDATE admin_sucursales
SET punto_venta_arca = COALESCE(punto_venta_arca, CASE id
    WHEN 1 THEN 2
    WHEN 4 THEN 3
    ELSE punto_venta_arca
END)
WHERE id IN (1, 4);
