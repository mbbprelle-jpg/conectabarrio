-- Georef calles/sede, domicilio por membresía y solicitudes de cambio de datos
-- Ejecutar después de las columnas que el usuario ya aplicó manualmente.

USE conectabarrio;

-- ========== BLOQUE 1 — calles y sede (si aún no existen) ==========
-- ALTER TABLE calles ADD COLUMN lat_centro DECIMAL(10,7) NULL, ADD COLUMN lng_centro DECIMAL(10,7) NULL;
-- ALTER TABLE juntas_vecinos ADD COLUMN lat_sede DECIMAL(10,7) NULL, ADD COLUMN lng_sede DECIMAL(10,7) NULL;
-- ALTER TABLE usuario_membresias ADD COLUMN calle_id INT NULL, ADD COLUMN numero_casa VARCHAR(20) NULL,
--   ADD COLUMN direccion_texto VARCHAR(255) NULL, ADD COLUMN latitud DECIMAL(10,7) NULL,
--   ADD COLUMN longitud DECIMAL(10,7) NULL, ADD COLUMN link_google VARCHAR(255) NULL;

-- ========== BLOQUE 2 — solicitudes de cambio de datos del socio ==========
CREATE TABLE IF NOT EXISTS socio_cambio_solicitudes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    junta_id INT NOT NULL,
    membresia_id INT NULL,
    datos_json LONGTEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    motivo_rechazo VARCHAR(255) NULL,
    reviewed_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reviewed_at TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES usuarios(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE INDEX idx_cambio_junta_status ON socio_cambio_solicitudes (junta_id, status);

-- ========== BLOQUE 3 — cache geocodificación (gratis, evita repetir Nominatim) ==========
CREATE TABLE IF NOT EXISTS georef_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cache_key VARCHAR(120) NOT NULL,
    latitud DECIMAL(10,7) NOT NULL,
    longitud DECIMAL(10,7) NOT NULL,
    link_google VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_georef_cache_key (cache_key)
) ENGINE=InnoDB;

-- ========== BLOQUE 4 — migrar domicilio existente usuarios → membresía ==========
UPDATE usuario_membresias m
INNER JOIN usuarios u ON u.id = m.usuario_id AND u.junta_id = m.junta_id
SET
    m.calle_id = u.calle_id,
    m.numero_casa = u.numero_casa,
    m.latitud = u.latitud,
    m.longitud = u.longitud,
    m.link_google = u.link_google
WHERE (m.calle_id IS NULL AND u.calle_id IS NOT NULL)
   OR (m.numero_casa IS NULL AND u.numero_casa IS NOT NULL)
   OR (m.latitud IS NULL AND u.latitud IS NOT NULL);
