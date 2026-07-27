ALTER TABLE stockcab
  ADD COLUMN tipo_movimiento VARCHAR(20) DEFAULT NULL AFTER notas,
  ADD INDEX idx_stockcab_tipo_mov (tipo_movimiento),
  ADD INDEX idx_stockcab_iddepoh (iddepoh),
  ADD INDEX idx_stockcab_iddepod (iddepod);

-- Clasificar movimientos existentes según depósitos especiales:
-- iddepoh=6 (Ventas) = venta, iddepod=6 = devolucion_venta
-- iddepod=7 (Compras) = compra, iddepoh=7 = devolucion_compra
UPDATE stockcab
SET tipo_movimiento = CASE
  WHEN iddepoh = 6 THEN 'venta'
  WHEN iddepod = 6 THEN 'devolucion_venta'
  WHEN iddepod = 7 THEN 'compra'
  WHEN iddepoh = 7 THEN 'devolucion_compra'
END
WHERE tipo_movimiento IS NULL;
