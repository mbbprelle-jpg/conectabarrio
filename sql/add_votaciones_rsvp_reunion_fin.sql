-- Votaciones y encuestas
CREATE TABLE IF NOT EXISTS votaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    descripcion TEXT NULL,
    tipo ENUM('votacion', 'encuesta') NOT NULL DEFAULT 'votacion',
    creado_por INT NOT NULL,
    token_publico VARCHAR(64) NOT NULL,
    audiencia_tipo ENUM('directiva', 'seleccionados', 'todos_socios') NOT NULL DEFAULT 'todos_socios',
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    resultados_visibilidad ENUM('directiva', 'todos') NOT NULL DEFAULT 'directiva',
    estado ENUM('borrador', 'activa', 'cerrada', 'cancelada') NOT NULL DEFAULT 'borrador',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_votacion_token (token_publico),
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
    FOREIGN KEY (creado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votacion_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    texto VARCHAR(500) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votacion_electores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    UNIQUE KEY uk_votacion_usuario (votacion_id, usuario_id),
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votacion_respuestas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    votacion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    opcion_id INT NULL,
    respuesta_texto TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_votacion_respuesta_usuario (votacion_id, usuario_id),
    FOREIGN KEY (votacion_id) REFERENCES votaciones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (opcion_id) REFERENCES votacion_opciones(id) ON DELETE SET NULL
) ENGINE=InnoDB;

ALTER TABLE usuario_membresias
    ADD COLUMN permiso_votaciones TINYINT(1) NOT NULL DEFAULT 0;

-- RSVP convocatorias
ALTER TABLE reunion_convocados
    ADD COLUMN rsvp_estado ENUM('pendiente', 'confirmado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    ADD COLUMN rsvp_at DATETIME NULL,
    ADD COLUMN rsvp_token VARCHAR(64) NULL;

-- Hora de término en minuta
ALTER TABLE reuniones
    ADD COLUMN hora_fin_real DATETIME NULL;
