-- Número de personalidad jurídica (opcional) y documentos legales de la organización
ALTER TABLE juntas_vecinos
ADD COLUMN personalidad_juridica_num VARCHAR(50) NULL DEFAULT NULL AFTER rut_junta;

CREATE TABLE IF NOT EXISTS junta_documentos_legales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL DEFAULT 'Documento legal',
    archivo_path VARCHAR(500) NOT NULL,
    archivo_nombre_original VARCHAR(255) NOT NULL,
    mime_type VARCHAR(100) NULL,
    tamano_bytes INT NULL,
    subido_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
    FOREIGN KEY (subido_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;
