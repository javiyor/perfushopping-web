CREATE TABLE IF NOT EXISTS promo_tarjetas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tipo_tarjeta VARCHAR(20) NOT NULL,
  banco VARCHAR(100) NOT NULL,
  descripcion TEXT,
  detalle_promo TEXT,
  fecha_desde DATE DEFAULT NULL,
  fecha_hasta DATE DEFAULT NULL,
  publicado TINYINT(1) DEFAULT 0,
  created_by INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;