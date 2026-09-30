-- Backfill: facturas ya autorizadas por ARCA que quedaron con código interno F-.
-- Las deja con el número que envió ARCA: PPVVVVV-NNNNNNNN (5 + 8 dígitos).
-- El punto de venta sale de admin_sucursales.punto_venta_arca (o del interno como fallback).
-- Ejecutar UNA sola vez (solo toca códigos con formato F-, es seguro repetirlo).
UPDATE facturas f
INNER JOIN (
    SELECT ac1.factura_id, ac1.codigo_emision
    FROM arca_comprobantes ac1
    INNER JOIN (
        SELECT factura_id, MAX(id) AS max_id
        FROM arca_comprobantes
        WHERE resultado = 'A' AND cae IS NOT NULL AND cae != '' AND cae != 'NULL'
        GROUP BY factura_id
    ) ac2 ON ac2.factura_id = ac1.factura_id AND ac2.max_id = ac1.id
) ac ON ac.factura_id = f.id
LEFT JOIN admin_sucursales s ON s.id = f.sucursal_id
SET f.codigo = CONCAT(
        LPAD(COALESCE(s.punto_venta_arca, f.punto_venta), 5, '0'),
        '-',
        LPAD(ac.codigo_emision, 8, '0')
    ),
    f.updated_at = NOW()
WHERE f.codigo LIKE 'F-%'
  AND ac.codigo_emision > 0
  AND (s.punto_venta_arca IS NOT NULL OR f.punto_venta IS NOT NULL);
