-- Solo tabla finanzas_conceptos (si ya aplicó saldo_inicial en juntas_vecinos)
CREATE TABLE IF NOT EXISTS finanzas_conceptos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    orden INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_concepto_junta (junta_id, tipo, nombre),
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;
