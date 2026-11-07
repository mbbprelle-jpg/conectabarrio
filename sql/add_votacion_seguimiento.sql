-- Pregunta de seguimiento por opción y resultados parciales "al votar"
ALTER TABLE votacion_opciones
    ADD COLUMN seguimiento_pregunta VARCHAR(500) NULL AFTER orden,
    ADD COLUMN seguimiento_tipo ENUM('ninguno','texto','opciones') NOT NULL DEFAULT 'ninguno' AFTER seguimiento_pregunta;

CREATE TABLE IF NOT EXISTS votacion_seguimiento_opciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    opcion_id INT NOT NULL,
    texto VARCHAR(500) NOT NULL,
    orden INT NOT NULL DEFAULT 0,
    KEY idx_seg_opcion (opcion_id),
    CONSTRAINT fk_seg_opcion FOREIGN KEY (opcion_id) REFERENCES votacion_opciones(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE votacion_respuestas
    ADD COLUMN seguimiento_texto TEXT NULL AFTER respuesta_texto,
    ADD COLUMN seguimiento_opcion_id INT NULL AFTER seguimiento_texto;

ALTER TABLE votacion_respuestas
    ADD CONSTRAINT fk_resp_seguimiento FOREIGN KEY (seguimiento_opcion_id)
    REFERENCES votacion_seguimiento_opciones(id) ON DELETE SET NULL;

ALTER TABLE votaciones
    MODIFY COLUMN resultados_visibilidad ENUM('directiva','todos','al_votar') NOT NULL DEFAULT 'directiva';

ALTER TABLE votaciones
    MODIFY COLUMN titulo VARCHAR(500) NOT NULL;
