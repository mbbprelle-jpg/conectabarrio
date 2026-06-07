-- Verificación rápida: no modifica nada, solo confirma que Finanzas está listo.
-- Ejecute en la MISMA base de datos que usa Coolify (DB_NAME / DB_HOST).

SELECT 'juntas_vecinos.saldo_inicial' AS chequeo,
       COUNT(*) AS ok
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'juntas_vecinos'
  AND COLUMN_NAME = 'saldo_inicial';

SELECT 'finanzas_conceptos' AS chequeo,
       COUNT(*) AS filas
FROM finanzas_conceptos;

SELECT junta_id, tipo, COUNT(*) AS conceptos
FROM finanzas_conceptos
GROUP BY junta_id, tipo
ORDER BY junta_id, tipo;
