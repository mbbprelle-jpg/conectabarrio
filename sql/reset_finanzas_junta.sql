-- =============================================================================
-- Reset financiero de una junta (movimientos de caja + cierres mensuales)
-- Reemplazar @junta_id por el ID de la organización (ej: 6)
-- =============================================================================

SET @junta_id = 6;

-- 1) DIAGNÓSTICO: revisar qué hay antes de borrar
SELECT 'transacciones' AS tabla, COUNT(*) AS filas,
       COALESCE(SUM(CASE WHEN tipo = 'ingreso' THEN monto ELSE 0 END), 0) AS total_ingresos,
       COALESCE(SUM(CASE WHEN tipo = 'egreso' THEN monto ELSE 0 END), 0) AS total_egresos
FROM transacciones WHERE junta_id = @junta_id;

SELECT id, tipo, categoria, monto, fecha, descripcion
FROM transacciones WHERE junta_id = @junta_id ORDER BY fecha DESC, id DESC;

SELECT 'cierres_mensuales' AS tabla, COUNT(*) AS filas
FROM cierres_mensuales WHERE junta_id = @junta_id;

SELECT id, mes, ingresos, egresos, saldo_anterior, saldo_final, saldo_neto
FROM cierres_mensuales WHERE junta_id = @junta_id ORDER BY mes DESC;

-- 2) LIMPIEZA (ejecutar solo si el diagnóstico es correcto)
-- START TRANSACTION;

DELETE FROM transacciones WHERE junta_id = @junta_id;
DELETE FROM cierres_mensuales WHERE junta_id = @junta_id;

-- COMMIT;

-- 3) VERIFICACIÓN post-limpieza (debe dar 0 en todo)
SELECT COUNT(*) AS transacciones_restantes FROM transacciones WHERE junta_id = @junta_id;
SELECT COUNT(*) AS cierres_restantes FROM cierres_mensuales WHERE junta_id = @junta_id;
