-- Vincula facturas de venta con pedidos web importados.
ALTER TABLE facturas
  ADD COLUMN IF NOT EXISTS order_id INT UNSIGNED DEFAULT NULL AFTER presupuesto_id;
