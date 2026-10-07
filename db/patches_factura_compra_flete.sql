-- Apartado de flete en facturas de compra: monto, % sobre gravado y transporte.
ALTER TABLE factura_compra
  ADD COLUMN IF NOT EXISTS flete_cents INT NOT NULL DEFAULT 0 AFTER observaciones,
  ADD COLUMN IF NOT EXISTS flete_pct DECIMAL(5,2) DEFAULT NULL AFTER flete_cents,
  ADD COLUMN IF NOT EXISTS flete_transporte_id INT UNSIGNED DEFAULT NULL AFTER flete_pct,
  ADD COLUMN IF NOT EXISTS flete_transporte VARCHAR(200) DEFAULT NULL AFTER flete_transporte_id;
