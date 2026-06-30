ALTER TABLE facturas ADD COLUMN vendedor_id INT UNSIGNED DEFAULT NULL AFTER created_by;
ALTER TABLE facturas ADD KEY idx_facturas_vendedor (vendedor_id);

CREATE TABLE IF NOT EXISTS empleado_config (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED NOT NULL UNIQUE,
  tipo enum('fijo','horas','comision','mixto') NOT NULL DEFAULT 'fijo',
  sueldo_base_cents INT NOT NULL DEFAULT 0,
  valor_hora_cents INT NOT NULL DEFAULT 0,
  cuil VARCHAR(20) DEFAULT NULL,
  banco VARCHAR(100) DEFAULT NULL,
  cbu VARCHAR(22) DEFAULT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  CONSTRAINT fk_emp_config_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empleado_comisiones (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED NOT NULL,
  codsub INT UNSIGNED NOT NULL,
  porcentaje DECIMAL(5,2) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_emp_comi (admin_user_id, codsub),
  CONSTRAINT fk_emp_comi_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empleado_horas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  horas DECIMAL(5,2) NOT NULL DEFAULT 0,
  concepto VARCHAR(200) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_emp_horas_user (admin_user_id),
  KEY idx_emp_horas_fecha (fecha),
  CONSTRAINT fk_emp_horas_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_emp_horas_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS empleado_liquidacion (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_user_id INT UNSIGNED NOT NULL,
  periodo VARCHAR(7) NOT NULL,
  sueldo_base_cents INT NOT NULL DEFAULT 0,
  horas_cents INT NOT NULL DEFAULT 0,
  comision_cents INT NOT NULL DEFAULT 0,
  total_cents INT NOT NULL DEFAULT 0,
  estado enum('calculada','pagada','anulada') NOT NULL DEFAULT 'calculada',
  detalle JSON DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  pagada_at DATETIME DEFAULT NULL,
  KEY idx_emp_liq_user (admin_user_id),
  KEY idx_emp_liq_periodo (periodo),
  CONSTRAINT fk_emp_liq_admin FOREIGN KEY (admin_user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
  CONSTRAINT fk_emp_liq_creator FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
