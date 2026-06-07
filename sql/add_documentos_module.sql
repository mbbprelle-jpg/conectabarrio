-- Módulo Documentos: habilitación por organización
ALTER TABLE juntas_vecinos
ADD COLUMN documentos_habilitado TINYINT(1) NOT NULL DEFAULT 0;

-- Permiso delegado: subir y gestionar documentos
ALTER TABLE usuario_membresias
ADD COLUMN permiso_documentos TINYINT(1) NOT NULL DEFAULT 0;

-- Categorías con visibilidad (publico = toda la org, directorio = directiva)
CREATE TABLE IF NOT EXISTS documento_categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    visibilidad ENUM('publico', 'directorio') NOT NULL DEFAULT 'publico',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_doc_cat_junta (junta_id, nombre),
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Metadatos de archivos (binarios en storage/documentos/)
CREATE TABLE IF NOT EXISTS documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    categoria_id INT NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    archivo_nombre_original VARCHAR(255) NOT NULL,
    archivo_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    tamano_bytes INT UNSIGNED NOT NULL DEFAULT 0,
    subido_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
    FOREIGN KEY (categoria_id) REFERENCES documento_categorias(id) ON DELETE RESTRICT,
    FOREIGN KEY (subido_por) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_documentos_junta (junta_id),
    INDEX idx_documentos_categoria (categoria_id)
) ENGINE=InnoDB;
