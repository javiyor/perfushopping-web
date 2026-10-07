-- Fecha de ingreso del pedido en remitos (usado en remitos de entrada).
ALTER TABLE remitos
  ADD COLUMN IF NOT EXISTS fecha_ingreso DATE DEFAULT NULL AFTER fecha;
