-- Backfill: facturas guardadas con "Consumidor Final" (o vacío) que sí tienen cliente.
-- Idempotente: se puede aplicar más de una vez.

UPDATE facturas f
JOIN clientes c ON c.idclien = f.idclien
SET f.cliente_nombre = c.razon
WHERE f.idclien IS NOT NULL
  AND (f.cliente_nombre = 'Consumidor Final' OR f.cliente_nombre = '');
