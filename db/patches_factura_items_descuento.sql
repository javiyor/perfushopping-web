-- Descuento porcentual por ítem en facturas de venta.
ALTER TABLE factura_items
  ADD COLUMN IF NOT EXISTS descuento_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00;
