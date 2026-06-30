CREATE TABLE IF NOT EXISTS ordenes_compra (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  proveedor_id INT UNSIGNED DEFAULT NULL,
  proveedor_nombre VARCHAR(200) DEFAULT NULL,
  fecha DATE NOT NULL,
  fecha_estimada DATE DEFAULT NULL,
  total_cents INT NOT NULL DEFAULT 0,
  estado ENUM('pendiente','aprobada','recibida','anulada') NOT NULL DEFAULT 'pendiente',
  notas TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_oc_proveedor (proveedor_id),
  KEY idx_oc_estado (estado),
  KEY idx_oc_fecha (fecha),
  CONSTRAINT fk_oc_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS orden_compra_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_id INT UNSIGNED NOT NULL,
  idprodu INT UNSIGNED DEFAULT NULL,
  idcodgusto INT UNSIGNED DEFAULT NULL,
  producto VARCHAR(220) NOT NULL,
  variedad VARCHAR(60) DEFAULT NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_price_cents INT NOT NULL DEFAULT 0,
  total_cents INT NOT NULL DEFAULT 0,
  KEY idx_oc_items_orden (orden_id),
  CONSTRAINT fk_oc_items_orden FOREIGN KEY (orden_id) REFERENCES ordenes_compra(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
