-- Georeferencia del domicilio del socio + opción "No informar" en estado civil
-- Ejecutar en la BD de producción (conectabarrio).

USE conectabarrio;

-- ========== BLOQUE 1 — columnas de georeferencia ==========
ALTER TABLE usuarios ADD COLUMN latitud DECIMAL(10, 7) NULL AFTER numero_casa;
ALTER TABLE usuarios ADD COLUMN longitud DECIMAL(10, 7) NULL AFTER latitud;
ALTER TABLE usuarios ADD COLUMN link_google VARCHAR(255) NULL AFTER longitud;

-- ========== BLOQUE 2 — estado civil: agregar NO_INFORMAR ==========
-- Si ya aplicó fix_estado_civil_enum.sql, ejecutar solo este MODIFY:
ALTER TABLE usuarios MODIFY COLUMN estado_civil ENUM(
    'NO_INFORMAR',
    'SOLTERO',
    'CASADO',
    'CONVIVIENTE_CIVIL',
    'DIVORCIADO',
    'VIUDO'
) NULL;
