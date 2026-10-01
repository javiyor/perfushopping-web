-- Backfill: facturas de compra sin razón social → la toma del proveedor.
-- Idempotente: se puede aplicar más de una vez.

UPDATE factura_compra fc
JOIN proveedo p ON p.idprovee = fc.idprovee
SET fc.razon_proveedor = p.razon
WHERE (fc.razon_proveedor IS NULL OR fc.razon_proveedor = '')
  AND p.razon IS NOT NULL AND p.razon <> '';

UPDATE factura_compra fc
JOIN proveedo p ON REPLACE(REPLACE(p.cuit, '-', ''), ' ', '') = REPLACE(REPLACE(fc.cuit_proveedor, '-', ''), ' ', '')
SET fc.razon_proveedor = p.razon
WHERE (fc.razon_proveedor IS NULL OR fc.razon_proveedor = '')
  AND p.razon IS NOT NULL AND p.razon <> '';
