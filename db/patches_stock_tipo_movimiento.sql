ALTER TABLE stockcab
  ADD COLUMN tipo_movimiento VARCHAR(20) DEFAULT NULL AFTER notas,
  ADD INDEX idx_stockcab_tipo_mov (tipo_movimiento),
  ADD INDEX idx_stockcab_iddepoh (iddepoh),
  ADD INDEX idx_stockcab_iddepod (iddepod);
