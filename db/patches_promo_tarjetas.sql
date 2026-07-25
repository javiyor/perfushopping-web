CREATE TABLE IF NOT EXISTS promo_tarjetas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_tarjeta VARCHAR(20) NOT NULL,
  banco VARCHAR(100) NOT NULL,
  descripcion TEXT,
  detalle_promo TEXT,
  imagen VARCHAR(255) DEFAULT NULL,
  fecha_desde DATE DEFAULT NULL,
  fecha_hasta DATE DEFAULT NULL,
  publicado TINYINT(1) DEFAULT 0,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Si la tabla ya existe y falta la columna imagen, correr:
-- ALTER TABLE promo_tarjetas ADD COLUMN imagen VARCHAR(255) DEFAULT NULL AFTER detalle_promo;