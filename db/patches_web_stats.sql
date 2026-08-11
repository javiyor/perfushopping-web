-- Estadísticas de la web: visitas por página y productos más vistos.

CREATE TABLE IF NOT EXISTS web_visitas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  url VARCHAR(255) NOT NULL,
  idprodu INT UNSIGNED DEFAULT NULL,
  user_id INT UNSIGNED DEFAULT NULL,
  session_key VARCHAR(64) DEFAULT NULL,
  ip VARCHAR(45) DEFAULT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_web_visitas_created (created_at),
  KEY idx_web_visitas_idprodu (idprodu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS producto_visitas (
  idprodu INT UNSIGNED NOT NULL,
  fecha DATE NOT NULL,
  vistas INT UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY (idprodu, fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
