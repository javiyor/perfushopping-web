-- Gastos varios (cuenta contable similar a factura_compra)
CREATE TABLE IF NOT EXISTS gastos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fecha DATE NOT NULL,
  idcta1 INT UNSIGNED DEFAULT NULL,
  descripcion VARCHAR(255) NOT NULL,
  importe_cents INT NOT NULL DEFAULT 0,
  forma_pago ENUM('efectivo','transferencia','cheque') NOT NULL DEFAULT 'efectivo',
  caja_destino ENUM('chica','general') NOT NULL DEFAULT 'general',
  banco_cuenta_id INT UNSIGNED DEFAULT NULL,
  cheque_id INT UNSIGNED DEFAULT NULL,
  sucursal_id INT UNSIGNED DEFAULT NULL,
  punto_venta INT UNSIGNED DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  KEY idx_gastos_fecha (fecha),
  KEY idx_gastos_cuenta (idcta1),
  KEY idx_gastos_banco (banco_cuenta_id),
  KEY idx_gastos_cheque (cheque_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Parche para instalaciones donde la tabla gastos ya existía sin columnas nuevas (ejecuta y ignora si ya existe)
-- Si tu tabla gastos tiene columna 'concepto' en lugar de 'descripcion', este patch la normaliza:
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS descripcion VARCHAR(255) NOT NULL AFTER fecha;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS idcta1 INT UNSIGNED DEFAULT NULL AFTER fecha;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS importe_cents INT NOT NULL DEFAULT 0 AFTER descripcion;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS forma_pago ENUM('efectivo','transferencia','cheque') NOT NULL DEFAULT 'efectivo' AFTER importe_cents;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS caja_destino ENUM('chica','general') NOT NULL DEFAULT 'general' AFTER forma_pago;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS banco_cuenta_id INT UNSIGNED DEFAULT NULL AFTER caja_destino;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS cheque_id INT UNSIGNED DEFAULT NULL AFTER banco_cuenta_id;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS sucursal_id INT UNSIGNED DEFAULT NULL AFTER cheque_id;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS punto_venta INT UNSIGNED DEFAULT NULL AFTER sucursal_id;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS created_by INT UNSIGNED DEFAULT NULL AFTER punto_venta;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS created_at DATETIME NOT NULL AFTER created_by;
ALTER TABLE gastos ADD COLUMN IF NOT EXISTS updated_at DATETIME NOT NULL AFTER created_at;

-- Movimientos bancarios (para transferencias, cheques y depósitos de efectivo)
CREATE TABLE IF NOT EXISTS banco_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  banco_cuenta_id INT UNSIGNED NOT NULL,
  tipo ENUM('credito','debito') NOT NULL,
  origen VARCHAR(30) DEFAULT NULL,
  origen_id INT UNSIGNED DEFAULT NULL,
  concepto VARCHAR(255) NOT NULL,
  monto_cents INT NOT NULL DEFAULT 0,
  fecha DATE NOT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_bm_cuenta (banco_cuenta_id),
  KEY idx_bm_origen (origen, origen_id),
  KEY idx_bm_fecha (fecha),
  CONSTRAINT fk_bm_cuenta FOREIGN KEY (banco_cuenta_id) REFERENCES banco_cuentas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
