-- Cheques de terceros: quién entregó el cheque
SET @db = DATABASE();

SET @exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
               WHERE TABLE_SCHEMA = @db AND TABLE_NAME = 'cheques' AND COLUMN_NAME = 'quien_entrego');
SET @sql = IF(@exists = 0,
              'ALTER TABLE cheques ADD COLUMN quien_entrego VARCHAR(200) DEFAULT NULL AFTER titular',
              'SELECT 1');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
