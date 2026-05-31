-- Corregir opciones de estado civil (quitar SEPARADO y UNION_CIVIL)
-- Ejecutar solo si ya aplicó la versión anterior de add_usuario_estado_civil_nacionalidad.sql

UPDATE usuarios SET estado_civil = NULL
WHERE estado_civil IN ('SEPARADO', 'UNION_CIVIL');

ALTER TABLE usuarios MODIFY COLUMN estado_civil ENUM(
    'SOLTERO',
    'CASADO',
    'CONVIVIENTE_CIVIL',
    'DIVORCIADO',
    'VIUDO'
) NULL;
