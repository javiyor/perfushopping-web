-- Caja general: movimientos de dinero globales
ALTER TABLE caja_aperturas
  ADD COLUMN monto_retirado_cents INT NOT NULL DEFAULT 0 AFTER monto_cierre_cents;

CREATE TABLE IF NOT EXISTS caja_general_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('ingreso','egreso') NOT NULL,
  origen VARCHAR(30) DEFAULT NULL,
  origen_id INT UNSIGNED DEFAULT NULL,
  concepto VARCHAR(255) NOT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  controlado TINYINT(1) NOT NULL DEFAULT 0,
  controlado_por INT UNSIGNED DEFAULT NULL,
  controlado_at DATETIME DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_cg_origen (origen, origen_id),
  KEY idx_cg_controlado (controlado),
  KEY idx_cg_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
