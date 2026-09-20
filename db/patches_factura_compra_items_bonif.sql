-- Bonificación porcentual por ítem en facturas de compra.
ALTER TABLE factura_compra_items
  ADD COLUMN IF NOT EXISTS bonif_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00;
