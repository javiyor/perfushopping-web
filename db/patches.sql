-- Patches for existing tables (run once)

-- 1) Add idzona to provincias
ALTER TABLE provincias
  ADD COLUMN idzona INT(10) UNSIGNED NULL,
  ADD INDEX idx_provincias_idzona (idzona);

-- 2) Ensure Patagonia zone exists
INSERT INTO zonas (nomzona)
SELECT 'PATAGONIA'
WHERE NOT EXISTS (
  SELECT 1 FROM zonas WHERE TRIM(UPPER(nomzona))='PATAGONIA'
);

SET @patagonia_id := (SELECT idzona FROM zonas WHERE TRIM(UPPER(nomzona))='PATAGONIA' LIMIT 1);

-- 3) Map provincias -> zonas (based on agreed rules)
-- LOCAL (idzona=1): Santa Fe
UPDATE provincias SET idzona=1 WHERE codprov IN (3);

-- ALREDEDORES (idzona=3): Corrientes, Chaco, Formosa, Entre Rios, Misiones
UPDATE provincias SET idzona=3 WHERE codprov IN (5,6,7,8,10);

-- PAMPA (idzona=4): CABA, Buenos Aires, Cordoba, La Pampa
UPDATE provincias SET idzona=4 WHERE codprov IN (1,2,4,11);

-- NORTE (idzona=5): Santiago del Estero, Salta, Jujuy, La Rioja, Catamarca, Tucuman
UPDATE provincias SET idzona=5 WHERE codprov IN (9,13,14,15,16,23);

-- CUYO (idzona=6): San Luis, Mendoza, San Juan
UPDATE provincias SET idzona=6 WHERE codprov IN (12,17,24);

-- PATAGONIA: Neuquen, Rio Negro, Santa Cruz, Tierra del Fuego, Chubut
UPDATE provincias SET idzona=@patagonia_id WHERE codprov IN (18,19,20,21,22);

-- 4) Avoid duplicate tarifas for the same pair
ALTER TABLE envios
  ADD UNIQUE KEY uq_envios_transporte_zona (idtransporte, idzona);

-- 5) Customer category on web users and wholesale requests
ALTER TABLE web_users
  ADD COLUMN customer_category VARCHAR(40) NOT NULL DEFAULT 'none';

ALTER TABLE wholesale_requests
  ADD COLUMN customer_category VARCHAR(40) NOT NULL DEFAULT 'none' AFTER province_codprov;

ALTER TABLE web_users
  ADD COLUMN force_password_change TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE orders
  ADD COLUMN ship_cod_lugar INT UNSIGNED DEFAULT NULL AFTER ship_province_codprov;

ALTER TABLE gustos
  ADD COLUMN weight_g INT(10) UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN height_cm INT(10) UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN width_cm INT(10) UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN depth_cm INT(10) UNSIGNED NOT NULL DEFAULT 0,
  ADD COLUMN product_category VARCHAR(80) DEFAULT NULL;

ALTER TABLE orders
  ADD COLUMN correo_operation VARCHAR(60) DEFAULT NULL AFTER ship_cod_lugar,
  ADD COLUMN correo_tracking VARCHAR(60) DEFAULT NULL AFTER correo_operation;

CREATE TABLE IF NOT EXISTS correo_agencies (
  agency_id VARCHAR(20) NOT NULL,
  agency_name VARCHAR(190) DEFAULT NULL,
  state_id VARCHAR(5) DEFAULT NULL,
  state_name VARCHAR(80) DEFAULT NULL,
  city_name VARCHAR(120) DEFAULT NULL,
  street_name VARCHAR(120) DEFAULT NULL,
  street_number VARCHAR(30) DEFAULT NULL,
  zip_code VARCHAR(20) DEFAULT NULL,
  phone VARCHAR(60) DEFAULT NULL,
  email VARCHAR(190) DEFAULT NULL,
  schedule VARCHAR(190) DEFAULT NULL,
  pickup_availability TINYINT(1) NOT NULL DEFAULT 0,
  package_reception TINYINT(1) NOT NULL DEFAULT 0,
  raw_json TEXT DEFAULT NULL,
  updated_at DATETIME NOT NULL,
  PRIMARY KEY (agency_id),
  KEY idx_correo_agency_state (state_id, city_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
