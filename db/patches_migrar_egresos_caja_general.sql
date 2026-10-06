-- ============================================================
-- Migración: Caja General solo efectivo
-- ============================================================
-- Los gastos con transferencia o cheque no deben generar egresos en
-- caja_general_movimientos:
--   * transferencia -> queda solo en banco_movimientos (débito)
--   * cheque        -> queda solo en el módulo de cheques
--
-- IMPORTANTE: antes de ejecutar, configurar la cuenta por defecto de
-- transferencias en /admin/banco-cuentas (si no está, el PASO 1 no
-- inserta nada y el CONTROL final mostrará filas pendientes; repetir
-- PASO 1 + PASO 2 después de configurarla).

-- ---------- PREVIEW ----------
-- Filas de caja general afectadas y si ya tienen débito bancario:
SELECT cg.id AS mov_id, cg.concepto, cg.monto_cents, cg.created_at,
       g.id AS gasto_id, g.forma_pago, g.importe_cents,
       (SELECT COUNT(*) FROM banco_movimientos bm WHERE bm.origen='gasto' AND bm.origen_id=g.id) AS tiene_debito_banco
FROM caja_general_movimientos cg
JOIN gastos g ON g.id = cg.origen_id
WHERE cg.origen='gasto' AND cg.tipo='egreso'
  AND g.forma_pago IN ('transferencia','cheque')
ORDER BY cg.id;

-- ---------- PASO 1 ----------
-- Crear débitos bancarios faltantes para gastos en transferencia
-- (solo si hay cuenta de transferencia por defecto configurada).
INSERT INTO banco_movimientos (banco_cuenta_id, tipo, origen, origen_id, concepto, monto_cents, fecha, created_by, created_at)
SELECT (SELECT cc.banco_cuenta_id FROM cobro_cuentas cc WHERE cc.tipo='transferencia' AND cc.idtarje=0 LIMIT 1),
       'debito', 'gasto', g.id, CONCAT('Gasto: ', g.descripcion), g.importe_cents, g.fecha,
       (SELECT MIN(id) FROM admin_users), NOW()
FROM gastos g
WHERE g.forma_pago='transferencia'
  AND g.id IN (SELECT cg.origen_id FROM caja_general_movimientos cg WHERE cg.origen='gasto' AND cg.tipo='egreso')
  AND NOT EXISTS (SELECT 1 FROM banco_movimientos bm WHERE bm.origen='gasto' AND bm.origen_id=g.id)
  AND EXISTS (SELECT 1 FROM cobro_cuentas cc WHERE cc.tipo='transferencia' AND cc.idtarje=0 AND cc.banco_cuenta_id IS NOT NULL);

-- ---------- PASO 2 ----------
-- Eliminar de caja general los gastos en transferencia que ya tienen débito en banco:
DELETE cg FROM caja_general_movimientos cg
JOIN gastos g ON g.id = cg.origen_id
WHERE cg.origen='gasto' AND cg.tipo='egreso' AND g.forma_pago='transferencia'
  AND EXISTS (SELECT 1 FROM banco_movimientos bm WHERE bm.origen='gasto' AND bm.origen_id=g.id);

-- ---------- PASO 3 ----------
-- Eliminar de caja general los gastos en cheque (el flujo vive en el módulo cheques):
DELETE cg FROM caja_general_movimientos cg
JOIN gastos g ON g.id = cg.origen_id
WHERE cg.origen='gasto' AND cg.tipo='egreso' AND g.forma_pago='cheque';

-- ---------- PASO 4 (opcional) ----------
-- Filas huérfanas de gastos ya eliminados (revisar antes de borrar):
-- SELECT * FROM caja_general_movimientos WHERE origen='gasto'
--   AND (origen_id IS NULL OR origen_id NOT IN (SELECT id FROM gastos));
-- DELETE FROM caja_general_movimientos WHERE origen='gasto'
--   AND (origen_id IS NULL OR origen_id NOT IN (SELECT id FROM gastos));

-- ---------- CONTROL (ambos deben dar 0 / sin filas) ----------
SELECT cg.id, cg.concepto, cg.monto_cents, g.forma_pago
FROM caja_general_movimientos cg
JOIN gastos g ON g.id = cg.origen_id
WHERE cg.origen='gasto' AND cg.tipo='egreso' AND g.forma_pago IN ('transferencia','cheque');

SELECT COUNT(*) AS movimientos_banco_sin_cuenta FROM banco_movimientos WHERE banco_cuenta_id IS NULL;
