    -- Censo / registro público familiar (padres, hijos, discapacidad, embarazo)
    -- Ejecutar una vez en la base de datos de producción.
    -- Los registros se asocian a juntas_vecinos.id (campaña actual: junta id = 6).

    CREATE TABLE IF NOT EXISTS censo_links (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        junta_id INT NOT NULL,
        token VARCHAR(64) NOT NULL,
        activo TINYINT(1) NOT NULL DEFAULT 1,
        titulo VARCHAR(160) NOT NULL DEFAULT 'Registro para juguetes de Navidad 2026',
        created_by INT NULL,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_censo_links_token (token),
        KEY idx_censo_links_junta (junta_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS censo_registros (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        junta_id INT NOT NULL,
        link_id INT UNSIGNED NULL,
        rut VARCHAR(12) NOT NULL,
        nombre VARCHAR(180) NOT NULL,
        calle_id INT NULL,
        direccion_texto VARCHAR(255) NULL,
        telefono VARCHAR(20) NOT NULL,
        registra_hijos TINYINT(1) NOT NULL DEFAULT 0,
        registra_discapacidad TINYINT(1) NOT NULL DEFAULT 0,
        registra_embarazo TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_censo_reg_junta (junta_id),
        KEY idx_censo_reg_rut (rut),
        KEY idx_censo_reg_link (link_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS censo_personas (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        registro_id INT UNSIGNED NOT NULL,
        tipo ENUM('hijo', 'discapacidad', 'embarazo') NOT NULL,
        rut VARCHAR(12) NOT NULL,
        nombre_completo VARCHAR(180) NOT NULL,
        sexo VARCHAR(20) NOT NULL,
        edad TINYINT UNSIGNED NULL,
        fecha_parto DATE NULL,
        usa_datos_adulto TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_censo_per_reg (registro_id),
        KEY idx_censo_per_rut (rut),
        KEY idx_censo_per_tipo (tipo),
        CONSTRAINT fk_censo_personas_registro
            FOREIGN KEY (registro_id) REFERENCES censo_registros(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Link público inicial para la junta 6 (atajo: /publico/registro_familiar)
    INSERT INTO censo_links (junta_id, token, activo, titulo)
    SELECT 6, REPLACE(UUID(), '-', ''), 1, 'Registro para juguetes de Navidad 2026'
    FROM DUAL
    WHERE EXISTS (SELECT 1 FROM juntas_vecinos WHERE id = 6)
    AND NOT EXISTS (SELECT 1 FROM censo_links WHERE junta_id = 6 AND activo = 1);
