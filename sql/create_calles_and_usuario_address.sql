-- Migración: calles + dirección del socio
-- Ejecutar en la BD de producción (conectabarrio).
--
-- Opción A — phpMyAdmin / Adminer: pegar y ejecutar solo el bloque 1 primero.
-- Opción B — terminal del servidor:
--   mysql -u USUARIO -p conectabarrio < sql/create_calles_and_usuario_address.sql

USE conectabarrio;

-- ========== BLOQUE 1 (obligatorio) — crea tabla calles ==========
CREATE TABLE IF NOT EXISTS calles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_calle_junta (junta_id, nombre),
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ========== BLOQUE 2 (si la BD es antigua) — columnas en usuarios ==========
-- Si alguna columna ya existe, MySQL dirá "Duplicate column" → ignorar esa línea y seguir.

ALTER TABLE usuarios ADD COLUMN id_socio INT NULL AFTER junta_id;
ALTER TABLE usuarios ADD COLUMN apellido_paterno VARCHAR(100) NULL AFTER nombre;
ALTER TABLE usuarios ADD COLUMN apellido_materno VARCHAR(100) NULL AFTER apellido_paterno;
ALTER TABLE usuarios ADD COLUMN calle_id INT NULL AFTER telefono;
ALTER TABLE usuarios ADD COLUMN numero_casa VARCHAR(20) NULL AFTER calle_id;
ALTER TABLE usuarios ADD COLUMN fecha_inicio DATE NULL AFTER numero_casa;

-- FK calle_id (omitir si ya existe fk_usuario_calle)
ALTER TABLE usuarios
    ADD CONSTRAINT fk_usuario_calle
    FOREIGN KEY (calle_id) REFERENCES calles(id) ON DELETE SET NULL;
