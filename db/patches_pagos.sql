ALTER TABLE factura_pagos
  ADD COLUMN cupon_numero VARCHAR(60) NULL AFTER monto_cents,
  ADD COLUMN cupon_monto_cents INT UNSIGNED NULL AFTER cupon_numero,
  ADD COLUMN idplazo INT UNSIGNED NULL AFTER cupon_monto_cents,
  ADD COLUMN banco_id INT UNSIGNED NULL AFTER idplazo;

INSERT INTO bancos (nombanc, numbanc) VALUES
  ('Banco de la Nación Argentina', 011),
  ('Banco de la Provincia de Buenos Aires', 014),
  ('Banco de la Ciudad de Buenos Aires', 015),
  ('Banco Galicia', 007),
  ('Banco Santander Río', 020),
  ('Banco BBVA Argentina', 017),
  ('Banco Macro', 285),
  ('Banco Hipotecario', 053),
  ('Banco Credicoop', 191),
  ('Banco Patagonia', 008),
  ('Banco Industrial', 090),
  ('Banco Comafi', 316),
  ('Banco de la Nación (caja de ahorro)', NULL);

INSERT INTO plazopago (dias, cuotas, descripcion, pricuo, tipo) VALUES
  (15, 1, '1 CUOTA - 15 DIAS', 15, 1),
  (30, 1, '1 CUOTA - 30 DIAS', 30, 1),
  (45, 1, '1 CUOTA - 45 DIAS', 45, 1),
  (60, 1, '1 CUOTA - 60 DIAS', 60, 1),
  (90, 1, '1 CUOTA - 90 DIAS', 90, 1),
  (30, 3, '3 CUOTAS C/30 DIAS', 15, 1),
  (30, 6, '6 CUOTAS C/30 DIAS', 30, 1),
  (30, 12, '12 CUOTAS C/30 DIAS', 30, 1);