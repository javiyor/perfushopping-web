CREATE TABLE IF NOT EXISTS remitos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  tipo ENUM('salida','entrada') NOT NULL DEFAULT 'salida',
  cliente_id INT UNSIGNED DEFAULT NULL,
  idclien INT UNSIGNED DEFAULT NULL,
  cliente_nombre VARCHAR(200) DEFAULT NULL,
  proveedor_id INT UNSIGNED DEFAULT NULL,
  proveedor_nombre VARCHAR(200) DEFAULT NULL,
  presupuesto_id INT UNSIGNED DEFAULT NULL,
  fecha DATE NOT NULL,
  total_cents INT NOT NULL DEFAULT 0,
  estado ENUM('pendiente','completado','anulado') NOT NULL DEFAULT 'pendiente',
  notas TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_remitos_tipo (tipo),
  KEY idx_remitos_estado (estado),
  KEY idx_remitos_fecha (fecha),
  KEY idx_remitos_cliente (cliente_id),
  KEY idx_remitos_presupuesto (presupuesto_id),
  CONSTRAINT fk_remitos_cliente FOREIGN KEY (cliente_id) REFERENCES web_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_remitos_presupuesto FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id) ON DELETE SET NULL,
  CONSTRAINT fk_remitos_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS remito_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  remito_id INT UNSIGNED NOT NULL,
  idprodu INT UNSIGNED DEFAULT NULL,
  idcodgusto INT UNSIGNED DEFAULT NULL,
  producto VARCHAR(220) NOT NULL,
  variedad VARCHAR(60) DEFAULT NULL,
  qty INT NOT NULL DEFAULT 1,
  created_at DATETIME NOT NULL,
  KEY idx_remito_items_remito (remito_id),
  CONSTRAINT fk_remito_items_remito FOREIGN KEY (remito_id) REFERENCES remitos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
