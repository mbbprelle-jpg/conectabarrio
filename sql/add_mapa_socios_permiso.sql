-- Mapa de socios: habilitación por organización y permiso delegado
ALTER TABLE juntas_vecinos
    ADD COLUMN mapa_socios_habilitado TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE usuario_membresias
    ADD COLUMN permiso_mapa_socios TINYINT(1) NOT NULL DEFAULT 0;
