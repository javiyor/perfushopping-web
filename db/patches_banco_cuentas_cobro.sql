-- Cuentas propias ya existen (banco_cuentas). Agregamos configuración de cobros predeterminados

CREATE TABLE IF NOT EXISTS cobro_cuentas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo ENUM('transferencia','tarjeta') NOT NULL,
  idtarje INT UNSIGNED NOT NULL DEFAULT 0,
  banco_cuenta_id INT UNSIGNED NOT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_cobro_tipo_tarjeta (tipo, idtarje),
  KEY idx_cobro_banco (banco_cuenta_id),
  CONSTRAINT fk_cobro_banco FOREIGN KEY (banco_cuenta_id) REFERENCES banco_cuentas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Para transferencias, idtarje = 0 (única fila tipo=transferencia, idtarje=0)
