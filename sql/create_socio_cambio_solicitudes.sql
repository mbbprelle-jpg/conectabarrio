-- Solicitudes de cambio de datos del socio (requerido para "Actualizar mis datos")
-- Ejecutar en la MISMA base de datos que usa la app (no asume nombre conectabarrio).

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

-- Si el índice ya existe, ignore el error o comente la línea siguiente.
CREATE INDEX idx_cambio_junta_status ON socio_cambio_solicitudes (junta_id, status);
