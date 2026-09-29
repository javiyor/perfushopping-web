-- Corrige aperturas de caja guardadas en pesos dentro de monto_inicial_cents.
-- El formulario de apertura sumaba denominaciones en pesos y lo guardaba sin x100.
-- Ejecutar UNA sola vez (no es idempotente).
UPDATE caja_aperturas
SET monto_inicial_cents = monto_inicial_cents * 100
WHERE monto_inicial_cents > 0;
