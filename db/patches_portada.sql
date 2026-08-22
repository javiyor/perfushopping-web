-- Portada configurable: que productos mostrar en la home publica.
-- Modos: auto (novedades 6 meses), rubro, marca, ultimos (100 mas recientes), manual (checks puntuales).

CREATE TABLE IF NOT EXISTS portada_config (
  id TINYINT UNSIGNED NOT NULL PRIMARY KEY,
  modo ENUM('auto','rubro','marca','ultimos','manual') NOT NULL DEFAULT 'auto',
  codrub INT UNSIGNED DEFAULT NULL,
  codsub INT UNSIGNED DEFAULT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO portada_config (id, modo) VALUES (1, 'auto');

CREATE TABLE IF NOT EXISTS portada_productos (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  idprodu INT UNSIGNED NOT NULL,
  orden INT UNSIGNED NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_portada_productos_idprodu (idprodu),
  KEY idx_portada_productos_orden (orden, id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
