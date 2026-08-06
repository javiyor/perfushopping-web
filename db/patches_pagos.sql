-- Datos de pago por factura: cupón de tarjeta, plazo/cuotas en cta. cte. y banco para cheques. Idempotente.
ALTER TABLE factura_pagos
  ADD COLUMN IF NOT EXISTS cupon_numero VARCHAR(60) NULL AFTER monto_cents,
  ADD COLUMN IF NOT EXISTS cupon_monto_cents INT UNSIGNED NULL AFTER cupon_numero,
  ADD COLUMN IF NOT EXISTS idplazo INT UNSIGNED NULL AFTER cupon_monto_cents,
  ADD COLUMN IF NOT EXISTS banco_id INT UNSIGNED NULL AFTER idplazo;

INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco de la Nación Argentina' AS n, 011 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco de la Nación Argentina');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco de la Provincia de Buenos Aires' AS n, 014 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco de la Provincia de Buenos Aires');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco de la Ciudad de Buenos Aires' AS n, 015 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco de la Ciudad de Buenos Aires');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Galicia' AS n, 007 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Galicia');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Santander Río' AS n, 020 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Santander Río');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco BBVA Argentina' AS n, 017 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco BBVA Argentina');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Macro' AS n, 285 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Macro');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Hipotecario' AS n, 053 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Hipotecario');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Credicoop' AS n, 191 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Credicoop');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Patagonia' AS n, 008 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Patagonia');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Industrial' AS n, 090 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Industrial');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco Comafi' AS n, 316 AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco Comafi');
INSERT INTO bancos (nombanc, numbanc)
SELECT * FROM (SELECT 'Banco de la Nación (caja de ahorro)' AS n, NULL AS c) t
WHERE NOT EXISTS (SELECT 1 FROM bancos WHERE nombanc = 'Banco de la Nación (caja de ahorro)');

INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 15, 1, '1 CUOTA - 15 DIAS', 15, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 1 AND dias = 15 AND pricuo = 15);
INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 30, 1, '1 CUOTA - 30 DIAS', 30, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 1 AND dias = 30 AND pricuo = 30);
INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 45, 1, '1 CUOTA - 45 DIAS', 45, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 1 AND dias = 45 AND pricuo = 45);
INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 60, 1, '1 CUOTA - 60 DIAS', 60, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 1 AND dias = 60 AND pricuo = 60);
INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 90, 1, '1 CUOTA - 90 DIAS', 90, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 1 AND dias = 90 AND pricuo = 90);
INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 30, 3, '3 CUOTAS C/30 DIAS', 15, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 3 AND dias = 30 AND pricuo = 15);
INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 30, 6, '6 CUOTAS C/30 DIAS', 30, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 6 AND dias = 30 AND pricuo = 30);
INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo)
SELECT 30, 12, '12 CUOTAS C/30 DIAS', 30, 1 WHERE NOT EXISTS (SELECT 1 FROM plazopago WHERE cuotas = 12 AND dias = 30 AND pricuo = 30);