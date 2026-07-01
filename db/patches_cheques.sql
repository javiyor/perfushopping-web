-- Gestión de cheques y órdenes de pago a proveedores

-- Cuentas bancarias de la empresa
CREATE TABLE IF NOT EXISTS banco_cuentas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  banco VARCHAR(100) NOT NULL,
  tipo_cuenta VARCHAR(30) DEFAULT 'corriente',
  numero_cuenta VARCHAR(30) DEFAULT NULL,
  cbu VARCHAR(22) DEFAULT NULL,
  titular VARCHAR(200) DEFAULT NULL,
  saldo_inicial_cents INT NOT NULL DEFAULT 0,
  activo TINYINT(1) DEFAULT 1,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cheques (propios y de terceros)
CREATE TABLE IF NOT EXISTS cheques (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('propio','tercero') NOT NULL,
  estado VARCHAR(30) NOT NULL DEFAULT 'en_cartera',
  banco_emisor VARCHAR(100) DEFAULT NULL,
  numero_cheque VARCHAR(30) DEFAULT NULL,
  titular VARCHAR(200) DEFAULT NULL,
  cuit_titular VARCHAR(20) DEFAULT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  fecha_emision DATE NOT NULL,
  fecha_vencimiento DATE DEFAULT NULL,
  banco_cuenta_id INT UNSIGNED DEFAULT NULL,
  concepto VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_cheques_estado (estado),
  KEY idx_cheques_tipo (tipo),
  KEY idx_cheques_banco_cuenta (banco_cuenta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Movimientos / trazabilidad de cheques
CREATE TABLE IF NOT EXISTS cheque_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cheque_id INT UNSIGNED NOT NULL,
  tipo VARCHAR(30) NOT NULL,
  origen VARCHAR(30) DEFAULT NULL,
  origen_id INT UNSIGNED DEFAULT NULL,
  observaciones VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_cheqmov_cheque (cheque_id),
  KEY idx_cheqmov_origen (origen, origen_id),
  CONSTRAINT fk_cheqmov_cheque FOREIGN KEY (cheque_id) REFERENCES cheques(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Órdenes de pago a proveedores
CREATE TABLE IF NOT EXISTS ordenes_pago (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codigo VARCHAR(20) NOT NULL UNIQUE,
  proveedor_id INT UNSIGNED DEFAULT NULL,
  proveedor_nombre VARCHAR(200) DEFAULT NULL,
  fecha DATE NOT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  estado ENUM('pendiente','pagada','anulada') NOT NULL DEFAULT 'pendiente',
  concepto VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_op_proveedor (proveedor_id),
  KEY idx_op_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Medios de pago de cada orden de pago
CREATE TABLE IF NOT EXISTS orden_pago_pagos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  orden_pago_id INT UNSIGNED NOT NULL,
  forma_pago VARCHAR(30) NOT NULL,
  cheque_id INT UNSIGNED DEFAULT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  KEY idx_opp_orden (orden_pago_id),
  KEY idx_opp_cheque (cheque_id),
  CONSTRAINT fk_opp_orden FOREIGN KEY (orden_pago_id) REFERENCES ordenes_pago(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Referencia a cheques en pagos de facturas y recibos
ALTER TABLE factura_pagos
  ADD COLUMN cheque_id INT UNSIGNED DEFAULT NULL AFTER forma_pago,
  ADD KEY idx_fp_cheque (cheque_id);

ALTER TABLE recibo_pagos
  ADD COLUMN forma_pago VARCHAR(40) DEFAULT NULL AFTER factura_id,
  ADD COLUMN cheque_id INT UNSIGNED DEFAULT NULL AFTER forma_pago,
  ADD KEY idx_rp_cheque (cheque_id);
