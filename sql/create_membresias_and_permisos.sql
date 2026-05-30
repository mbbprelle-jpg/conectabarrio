-- Membresías multi-organización + cargos y permisos delegados
CREATE TABLE IF NOT EXISTS usuario_membresias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    junta_id INT NOT NULL,
    rol ENUM('admin', 'socio') NOT NULL,
    cargo ENUM('SECRETARIO', 'TESORERO', 'DIRECTOR') NULL,
    permiso_gestion_socios TINYINT(1) NOT NULL DEFAULT 0,
    permiso_registro_pagos TINYINT(1) NOT NULL DEFAULT 0,
    permiso_todos TINYINT(1) NOT NULL DEFAULT 0,
    id_socio INT NULL,
    estado TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_usuario_junta (usuario_id, junta_id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT IGNORE INTO usuario_membresias (usuario_id, junta_id, rol, id_socio, estado)
SELECT id, junta_id, rol, id_socio, estado
FROM usuarios
WHERE junta_id IS NOT NULL AND rol IN ('admin', 'socio');
