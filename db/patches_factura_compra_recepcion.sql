-- Fecha de recepción del pedido en facturas de compra.
ALTER TABLE factura_compra
  ADD COLUMN IF NOT EXISTS fecha_recepcion DATE DEFAULT NULL AFTER fecha;
