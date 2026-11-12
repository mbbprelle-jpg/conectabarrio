CREATE TABLE IF NOT EXISTS socio_accesos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    usuario_id INT NOT NULL,
    tipo ENUM('ingreso', 'seccion') NOT NULL,
    seccion VARCHAR(80) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_socio_accesos_junta (junta_id, created_at),
    INDEX idx_socio_accesos_usuario (usuario_id, tipo, created_at),
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;
