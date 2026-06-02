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
