CREATE TABLE IF NOT EXISTS recibos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  tipo_comprobante VARCHAR(10) NOT NULL DEFAULT 'REC-B',
  cliente_id INT UNSIGNED DEFAULT NULL,
  idclien INT UNSIGNED DEFAULT NULL,
  cliente_nombre VARCHAR(200) DEFAULT NULL,
  cliente_cuit VARCHAR(20) DEFAULT NULL,
  cliente_direc VARCHAR(190) DEFAULT NULL,
  cliente_condicion_iva VARCHAR(20) DEFAULT 'consumidor_final',
  fecha DATE NOT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  forma_pago VARCHAR(40) DEFAULT NULL,
  concepto VARCHAR(255) DEFAULT NULL,
  estado ENUM('emitido','anulado') NOT NULL DEFAULT 'emitido',
  notas TEXT,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_recibos_estado (estado),
  KEY idx_recibos_fecha (fecha),
  KEY idx_recibos_cliente (cliente_id),
  CONSTRAINT fk_recibos_cliente FOREIGN KEY (cliente_id) REFERENCES web_users(id) ON DELETE SET NULL,
  CONSTRAINT fk_recibos_admin FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS recibo_pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  recibo_id INT UNSIGNED NOT NULL,
  factura_id INT UNSIGNED DEFAULT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  KEY idx_recibo_pagos_recibo (recibo_id),
  KEY idx_recibo_pagos_factura (factura_id),
  CONSTRAINT fk_recibo_pagos_recibo FOREIGN KEY (recibo_id) REFERENCES recibos(id) ON DELETE CASCADE,
  CONSTRAINT fk_recibo_pagos_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
