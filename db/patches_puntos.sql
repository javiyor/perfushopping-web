-- Programa de puntos tipo Serviclub
-- 1 punto = $1 de crédito en mercadería.
-- Se acumula 1% (configurable) del importe de la compra, más bonus por marca (subrubro) y por producto.

CREATE TABLE IF NOT EXISTS puntos_cuentas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idclien INT UNSIGNED NOT NULL,
  saldo_puntos INT NOT NULL DEFAULT 0,
  total_acumulado INT NOT NULL DEFAULT 0,
  total_usados INT NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL,
  UNIQUE KEY uq_puntos_cuenta_idclien (idclien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS puntos_movimientos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idclien INT UNSIGNED NOT NULL,
  tipo ENUM('acumulacion','uso','ajuste') NOT NULL DEFAULT 'acumulacion',
  puntos INT NOT NULL DEFAULT 0,
  factura_id INT UNSIGNED DEFAULT NULL,
  order_id INT UNSIGNED DEFAULT NULL,
  descripcion VARCHAR(255) DEFAULT NULL,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uq_puntos_mov_factura_tipo (factura_id, tipo),
  UNIQUE KEY uq_puntos_mov_order_tipo (order_id, tipo),
  KEY idx_puntos_mov_idclien (idclien)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS puntos_config (
  clave VARCHAR(50) PRIMARY KEY,
  valor VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO puntos_config (clave, valor) VALUES ('general_pct', '1');

CREATE TABLE IF NOT EXISTS puntos_marcas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  codsub INT UNSIGNED NOT NULL,
  porcentaje DECIMAL(6,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_puntos_marca_codsub (codsub)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS puntos_productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idprodu INT UNSIGNED NOT NULL,
  porcentaje DECIMAL(6,2) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_puntos_producto_idprodu (idprodu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Importe usado en puntos en una factura (se descuenta del total)
-- Si ya existe la columna, correr este ALTER es innecesario.
ALTER TABLE facturas ADD COLUMN puntos_cents INT NOT NULL DEFAULT 0 AFTER descuento_cents;
