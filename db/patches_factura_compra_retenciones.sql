-- Retenciones en facturas de compra (Ingresos Brutos e IVA).
ALTER TABLE factura_compra
  ADD COLUMN IF NOT EXISTS ret_ing_brutos DECIMAL(14,2) NOT NULL DEFAULT 0.00;
ALTER TABLE factura_compra
  ADD COLUMN IF NOT EXISTS ret_iva DECIMAL(14,2) NOT NULL DEFAULT 0.00;
