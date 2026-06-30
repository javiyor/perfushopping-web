ALTER TABLE facturas ADD COLUMN cae VARCHAR(14) DEFAULT NULL AFTER estado;
ALTER TABLE facturas ADD COLUMN cae_vto DATE DEFAULT NULL AFTER cae;
ALTER TABLE facturas ADD COLUMN resultado_arca VARCHAR(20) DEFAULT NULL AFTER cae_vto;
ALTER TABLE facturas ADD COLUMN arca_obs TEXT DEFAULT NULL AFTER resultado_arca;

CREATE TABLE IF NOT EXISTS arca_config (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  cfg_key VARCHAR(60) NOT NULL UNIQUE,
  cfg_value TEXT,
  created_at DATETIME NOT NULL,
  updated_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO arca_config (cfg_key, cfg_value, created_at, updated_at) VALUES
('ambiente', 'homologacion', NOW(), NOW()),
('cuit', '', NOW(), NOW()),
('cert_path', '', NOW(), NOW()),
('key_path', '', NOW(), NOW()),
('habilitado', '0', NOW(), NOW());

CREATE TABLE IF NOT EXISTS arca_tickets_acceso (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  token TEXT NOT NULL,
  sign TEXT NOT NULL,
  expiration DATETIME NOT NULL,
  created_at DATETIME NOT NULL,
  KEY idx_arca_ta_exp (expiration)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS arca_comprobantes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  factura_id INT UNSIGNED NOT NULL UNIQUE,
  cae VARCHAR(14) DEFAULT NULL,
  cae_vto DATE DEFAULT NULL,
  resultado VARCHAR(20) DEFAULT NULL,
  codigo_emision INT DEFAULT NULL,
  observaciones TEXT,
  request_xml LONGTEXT,
  response_xml LONGTEXT,
  created_at DATETIME NOT NULL,
  KEY idx_arca_comp_factura (factura_id),
  CONSTRAINT fk_arca_comp_factura FOREIGN KEY (factura_id) REFERENCES facturas(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
