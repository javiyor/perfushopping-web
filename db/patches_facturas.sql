CREATE TABLE IF NOT EXISTS facturas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  tipo_comprobante VARCHAR(10) NOT NULL DEFAULT 'FACT-B',
  remito_id INT UNSIGNED DEFAULT NULL,
  presupuesto_id INT UNSIGNED DEFAULT NULL,
  cliente_id INT UNSIGNED DEFAULT NULL,
  idclien INT UNSIGNED DEFAULT NULL,
  cliente_nombre VARCHAR(200) DEFAULT NULL,
  cliente_cuit VARCHAR(20) DEFAULT NULL,
  cliente_direc VARCHAR(190) DEFAULT NULL,
  cliente_tele VARCHAR(40) DEFAULT NULL,
  cliente_mail VARCHAR(190) DEFAULT NULL,
  cliente_condicion_iva VARCHAR(20) DEFAULT 'consumidor_final',
  punto_venta INT NOT NULL DEFAULT 1,
  fecha DATE NOT NULL,
  subtotal_cents INT NOT NULL DEFAULT 0,
  iva_cents INT NOT NULL DEFAULT 0,
  total_cents INT NOT NULL DEFAULT 0,
  estado ENUM('pendiente','emitida','anulada') NOT NULL DEFAULT 'pendiente',
  forma_pago VARCHAR(40) DEFAULT NULL,
  notas TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_facturas_estado (estado),
  KEY idx_facturas_fecha (fecha),
  KEY idx_facturas_cliente (cliente_id),
  KEY idx_facturas_remito (remito_id),
  CONSTRAINT fk_facturas_cliente FOREIGN KEY (cliente_id) REFERENCES web_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_facturas_remito FOREIGN KEY (remito_id) REFERENCES remitos(id) ON DELETE SET NULL,
  CONSTRAINT fk_facturas_presupuesto FOREIGN KEY (presupuesto_id) REFERENCES presupuestos(id) ON DELETE SET NULL,
  CONSTRAINT fk_facturas_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS factura_items (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factura_id INT UNSIGNED NOT NULL,
  idprodu INT UNSIGNED DEFAULT NULL,
  idcodgusto INT UNSIGNED DEFAULT NULL,
  producto VARCHAR(220) NOT NULL,
  variedad VARCHAR(60) DEFAULT NULL,
  qty INT NOT NULL DEFAULT 1,
  unit_price_cents INT NOT NULL DEFAULT 0,
  iva_rate DECIMAL(7,2) NOT NULL DEFAULT 0,
  iva_cents INT NOT NULL DEFAULT 0,
  total_cents INT NOT NULL DEFAULT 0,
  KEY idx_factura_items_factura (factura_id),
  CONSTRAINT fk_factura_items_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS factura_pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factura_id INT UNSIGNED NOT NULL,
  forma_pago VARCHAR(40) NOT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  KEY idx_factura_pagos_factura (factura_id),
  CONSTRAINT fk_factura_pagos_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
