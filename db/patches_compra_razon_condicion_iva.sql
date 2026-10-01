-- Backfill: facturas de compra cuya "razón social" es en realidad una condición IVA.
-- Se reemplaza por la razón real del proveedor (maestro o por CUIT).
-- Idempotente: se puede aplicar más de una vez.

UPDATE factura_compra fc
JOIN proveedo p ON p.idprovee = fc.idprovee
SET fc.razon_proveedor = p.razon
WHERE LOWER(TRIM(fc.razon_proveedor)) IN (
    'responsable inscripto','responsable inscripta','monotributista','monotributo',
    'exento','exenta','consumidor final','no categorizado','no alcanzado',
    'sujeto exento','no responsable','iva responsable inscripto','iva exento',
    'monotributista social','pequeño contribuyente'
)
AND p.razon IS NOT NULL AND p.razon <> '';

UPDATE factura_compra fc
JOIN proveedo p ON REPLACE(REPLACE(p.cuit, '-', ''), ' ', '') = REPLACE(REPLACE(fc.cuit_proveedor, '-', ''), ' ', '')
SET fc.razon_proveedor = p.razon
WHERE fc.idprovee IS NULL
AND LOWER(TRIM(fc.razon_proveedor)) IN (
    'responsable inscripto','responsable inscripta','monotributista','monotributo',
    'exento','exenta','consumidor final','no categorizado','no alcanzado',
    'sujeto exento','no responsable','iva responsable inscripto','iva exento',
    'monotributista social','pequeño contribuyente'
)
AND p.razon IS NOT NULL AND p.razon <> '';
