-- Habilitar módulo Flujo de Caja por organización
ALTER TABLE juntas_vecinos
ADD COLUMN flujo_caja_habilitado TINYINT(1) NOT NULL DEFAULT 0;

-- Permiso por miembro para ver el flujo de caja anual (solo lectura)
ALTER TABLE usuario_membresias
ADD COLUMN permiso_flujo_caja TINYINT(1) NOT NULL DEFAULT 0;
