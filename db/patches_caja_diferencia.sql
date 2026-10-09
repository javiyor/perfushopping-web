-- Cierre con diferencia: marca e importe del desvío vs esperado.
ALTER TABLE caja_aperturas
  ADD COLUMN IF NOT EXISTS diferencia_cents INT NOT NULL DEFAULT 0 AFTER monto_cierre_cents,
  ADD COLUMN IF NOT EXISTS con_diferencia TINYINT(1) NOT NULL DEFAULT 0 AFTER diferencia_cents;
