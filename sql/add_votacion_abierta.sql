-- Encuesta abierta y registro de niños. La aplicación también crea estas columnas al guardar.

ALTER TABLE votaciones
    ADD COLUMN participacion ENUM('socios','abierta') NOT NULL DEFAULT 'socios' AFTER audiencia_tipo,
    ADD COLUMN inscribe_ninos TINYINT(1) NOT NULL DEFAULT 0 AFTER participacion,
    ADD COLUMN bcc_correo VARCHAR(150) NULL AFTER inscribe_ninos;

CREATE TABLE IF NOT EXISTS votacion_participaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    usuario_id INT NULL,
    es_socio TINYINT(1) NOT NULL DEFAULT 0,
    nombre VARCHAR(200) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(30) NOT NULL,
    cantidad_ninos INT NOT NULL DEFAULT 0,
    opcion_id INT NULL,
    respuesta_texto TEXT NULL,
    seguimiento_texto TEXT NULL,
    seguimiento_opcion_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_votacion_part_email (votacion_id, email),
    KEY idx_part_votacion (votacion_id),
    KEY idx_part_usuario (votacion_id, usuario_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votacion_ninos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    participacion_id INT NOT NULL,
    orden INT NOT NULL DEFAULT 1,
    edad TINYINT UNSIGNED NOT NULL,
    genero ENUM('femenino','masculino','no_responder') NOT NULL,
    KEY idx_nino_part (participacion_id)
) ENGINE=InnoDB;
