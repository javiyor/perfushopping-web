-- ============================================================
-- Migración: Caja General solo efectivo
-- ============================================================
-- Los gastos con transferencia o cheque no deben generar egresos en
-- caja_general_movimientos:
--   * transferencia -> queda solo en banco_movimientos (débito)
--   * cheque        -> queda solo en el módulo de cheques
--
-- Detecta las filas por el sufijo del concepto, que el código antiguo
-- siempre agregaba: 'Gasto: ... (transferencia)' / 'Gasto: ... (cheque)'.
-- (No se une a gastos: esa tabla no tiene columna id.)
--
-- IMPORTANTE: antes de ejecutar, configurar la cuenta por defecto de
-- transferencias en /admin/banco-cuentas (si no está, el PASO 1 no
-- inserta nada y el CONTROL mostrará filas pendientes; repetir
-- PASO 1 + PASO 2 después de configurarla).

-- ---------- PREVIEW ----------
-- Filas de caja general afectadas y si ya tienen débito bancario:
SELECT cg.id AS mov_id, cg.concepto, cg.monto_cents, cg.created_at, cg.origen_id AS gasto_id,
       (SELECT COUNT(*) FROM banco_movimientos bm WHERE bm.origen='gasto' AND bm.origen_id=cg.origen_id) AS tiene_debito_banco
FROM caja_general_movimientos cg
WHERE cg.origen='gasto' AND cg.tipo='egreso'
  AND (cg.concepto LIKE '% (transferencia)' OR cg.concepto LIKE '% (cheque)')
ORDER BY cg.id;

-- ---------- PASO 1 ----------
-- Crear débitos bancarios faltantes para gastos en transferencia
-- (solo si hay cuenta de transferencia por defecto configurada).
INSERT INTO banco_movimientos (banco_cuenta_id, tipo, origen, origen_id, concepto, monto_cents, fecha, created_by, created_at)
SELECT (SELECT cc.banco_cuenta_id FROM cobro_cuentas cc WHERE cc.tipo='transferencia' AND cc.idtarje=0 LIMIT 1),
       'debito', 'gasto', cg.origen_id,
       TRIM(TRAILING ' (transferencia)' FROM cg.concepto),
       cg.monto_cents, DATE(cg.created_at),
       (SELECT MIN(id) FROM admin_users), NOW()
FROM caja_general_movimientos cg
WHERE cg.origen='gasto' AND cg.tipo='egreso'
  AND cg.concepto LIKE '% (transferencia)'
  AND cg.origen_id > 0
  AND NOT EXISTS (SELECT 1 FROM banco_movimientos bm WHERE bm.origen='gasto' AND bm.origen_id=cg.origen_id)
  AND EXISTS (SELECT 1 FROM cobro_cuentas cc WHERE cc.tipo='transferencia' AND cc.idtarje=0 AND cc.banco_cuenta_id IS NOT NULL);

-- ---------- PASO 2 ----------
-- Eliminar de caja general los gastos en transferencia que ya tienen débito en banco:
DELETE FROM caja_general_movimientos
WHERE origen='gasto' AND tipo='egreso'
  AND concepto LIKE '% (transferencia)'
  AND EXISTS (SELECT 1 FROM banco_movimientos bm
              WHERE bm.origen='gasto' AND bm.origen_id=caja_general_movimientos.origen_id);

-- ---------- PASO 3 ----------
-- Eliminar de caja general los gastos en cheque (el flujo vive en el módulo cheques):
DELETE FROM caja_general_movimientos
WHERE origen='gasto' AND tipo='egreso'
  AND concepto LIKE '% (cheque)';

-- ---------- CONTROL ----------
-- 1) No debe quedar ninguna fila de transferencia/cheque:
SELECT id, concepto, monto_cents, created_at, origen_id
FROM caja_general_movimientos
WHERE origen='gasto' AND tipo='egreso'
  AND (concepto LIKE '% (transferencia)' OR concepto LIKE '% (cheque)');

-- 2) Todo débito bancario debe tener cuenta asignada:
SELECT COUNT(*) AS movimientos_banco_sin_cuenta FROM banco_movimientos WHERE banco_cuenta_id IS NULL;

-- 3) Revisión: egresos por gasto que quedan en caja general (deben ser solo efectivo):
SELECT id, concepto, monto_cents, created_at, origen_id
FROM caja_general_movimientos
WHERE origen='gasto' AND tipo='egreso'
ORDER BY id;
