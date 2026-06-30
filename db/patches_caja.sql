CREATE TABLE IF NOT EXISTS caja_aperturas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  sucursal_id INT UNSIGNED NOT NULL,
  turno ENUM('manana','tarde') NOT NULL,
  fecha DATE NOT NULL,
  monto_inicial_cents INT NOT NULL DEFAULT 0,
  estado ENUM('abierta','cerrada') NOT NULL DEFAULT 'abierta',
  observaciones TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  cerrada_por INT UNSIGNED DEFAULT NULL,
  monto_cierre_cents INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_caja_fecha (fecha),
  KEY idx_caja_sucursal (sucursal_id),
  KEY idx_caja_estado (estado),
  CONSTRAINT fk_caja_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS caja_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  caja_id INT UNSIGNED NOT NULL,
  tipo ENUM('ingreso','egreso') NOT NULL,
  concepto VARCHAR(255) NOT NULL,
  monto_cents INT NOT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_cajamov_caja (caja_id),
  CONSTRAINT fk_cajamov_caja FOREIGN KEY (caja_id) REFERENCES caja_aperturas(id) ON DELETE CASCADE,
  CONSTRAINT fk_cajamov_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS caja_arqueos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  caja_id INT UNSIGNED NOT NULL,
  total_cents INT NOT NULL,
  observaciones TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_cajaarq_caja (caja_id),
  CONSTRAINT fk_cajaarq_caja FOREIGN KEY (caja_id) REFERENCES caja_aperturas(id) ON DELETE CASCADE,
  CONSTRAINT fk_cajaarq_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
