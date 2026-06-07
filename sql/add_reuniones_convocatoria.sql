-- Convocatorias: temas, resultados, minuta y destinatarios
ALTER TABLE reuniones
ADD COLUMN temas_tratar TEXT NULL AFTER descripcion,
ADD COLUMN resultados TEXT NULL,
ADD COLUMN hora_inicio_real DATETIME NULL,
ADD COLUMN convocada_por INT NULL,
ADD COLUMN email_convocatoria TINYINT(1) NOT NULL DEFAULT 0;

ALTER TABLE usuario_membresias
ADD COLUMN permiso_reuniones TINYINT(1) NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS reunion_convocados (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    usuario_id INT NOT NULL,
    notificado_email TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_reunion_convocado (reunion_id, usuario_id),
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_convocado_usuario (usuario_id)
) ENGINE=InnoDB;
