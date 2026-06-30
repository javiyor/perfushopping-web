CREATE TABLE IF NOT EXISTS admin_sucursales (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idsucemp INT UNSIGNED NOT NULL,
  nomsuc VARCHAR(100) DEFAULT NULL,
  numsuc VARCHAR(10) DEFAULT NULL,
  punto_venta INT NOT NULL DEFAULT 1,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uk_idsucemp (idsucemp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Auto-import from ERP sucur if empty
INSERT IGNORE INTO admin_sucursales (idsucemp, nomsuc, numsuc, punto_venta, activo, created_at, updated_at)
SELECT s.idsucemp, s.nomsuc, s.numsuc, 1, 1, NOW(), NOW()
FROM sucur s
WHERE NOT EXISTS (SELECT 1 FROM admin_sucursales WHERE idsucemp = s.idsucemp);

-- Add punto_venta to facturas if not exists (for existing installations)
SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'facturas' AND COLUMN_NAME = 'punto_venta');
SET @sql = IF(@exists = 0, 'ALTER TABLE facturas ADD COLUMN punto_venta INT NOT NULL DEFAULT 1 AFTER cliente_condicion_iva', 'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
