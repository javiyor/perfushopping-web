-- Plazo de pago de facturas de compra (para ctacte de proveedores).
-- Idempotente: se puede aplicar más de una vez.

SET @db = DATABASE();

-- Cuotas (1 = contado / sin cuotas)
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'factura_compra' AND COLUMN_NAME = 'plazo_cuotas');
SET @sql = IF(@exists = 0,
              'ALTER TABLE factura_compra ADD COLUMN plazo_cuotas INT NOT NULL DEFAULT 1 AFTER imp_total',
              'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Días de cada cuota respecto de la fecha del comprobante (CSV: 30,60,90)
SET @exists2 = (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'factura_compra' AND COLUMN_NAME = 'plazo_dias');
SET @sql2 = IF(@exists2 = 0,
               'ALTER TABLE factura_compra ADD COLUMN plazo_dias VARCHAR(255) DEFAULT NULL AFTER plazo_cuotas',
               'SELECT 1');
PREPARE stmt FROM @sql2;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
