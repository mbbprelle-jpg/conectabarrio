-- Creación de la base de datos
CREATE DATABASE IF NOT EXISTS conectabarrio CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE conectabarrio;

-- 1. Tabla de Juntas de Vecinos
CREATE TABLE IF NOT EXISTS juntas_vecinos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    rut_junta VARCHAR(20) NOT NULL UNIQUE,
    direccion VARCHAR(255) NOT NULL,
    comuna VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Tabla de Calles (jurisdicción por junta)
CREATE TABLE IF NOT EXISTS calles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    nombre VARCHAR(150) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_calle_junta (junta_id, nombre),
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 3. Tabla de Usuarios (Maestros, Admins, Socios)
CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NULL,
    id_socio INT NULL,
    rut VARCHAR(12) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido_paterno VARCHAR(100) NULL,
    apellido_materno VARCHAR(100) NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    rol ENUM('maestro', 'admin', 'socio') NOT NULL,
    telefono VARCHAR(20) NULL,
    calle_id INT NULL,
    numero_casa VARCHAR(20) NULL,
    fecha_inicio DATE NULL,
    estado TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE SET NULL,
    FOREIGN KEY (calle_id) REFERENCES calles(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 4. Tabla de Configuración de Cuotas (Valores históricos y vigencia)
CREATE TABLE IF NOT EXISTS configuracion_cuotas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    monto INT NOT NULL,
    mes_inicio VARCHAR(7) NOT NULL, -- Formato: YYYY-MM (ej. 2026-05)
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 5. Tabla de Transacciones (Manejo unificado de Caja: Cuotas, Ingresos Generales y Egresos)
CREATE TABLE IF NOT EXISTS transacciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    tipo ENUM('ingreso', 'egreso') NOT NULL,
    categoria VARCHAR(100) NOT NULL, -- e.g., 'Cuota Socio', 'Donación', 'Subsidio', 'Evento', 'Gasto de Luz', 'Reparaciones'
    monto INT NOT NULL,
    descripcion TEXT NULL,
    fecha DATE NOT NULL,
    comprobante_url VARCHAR(255) NULL,
    socio_id INT NULL, -- Registrado si es tipo 'ingreso' y categoria 'Cuota Socio'
    mes_pagado VARCHAR(7) NULL, -- Formato: YYYY-MM (solo aplica a Cuota Socio)
    registrado_por INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
    FOREIGN KEY (socio_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (registrado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- 6. Tabla de Reuniones
CREATE TABLE IF NOT EXISTS reuniones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    titulo VARCHAR(150) NOT NULL,
    descripcion TEXT NULL,
    fecha_reunion DATETIME NOT NULL,
    estado ENUM('programada', 'realizada', 'cancelada') DEFAULT 'programada',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. Tabla de Asistencias a Reuniones
CREATE TABLE IF NOT EXISTS asistencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reunion_id INT NOT NULL,
    socio_id INT NOT NULL,
    asistio TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_asistencia (reunion_id, socio_id),
    FOREIGN KEY (reunion_id) REFERENCES reuniones(id) ON DELETE CASCADE,
    FOREIGN KEY (socio_id) REFERENCES usuarios(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 8. Tabla de Reportes Enviados a la Municipalidad
CREATE TABLE IF NOT EXISTS reportes_municipalidad (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    fecha_envio TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    tipo_reporte VARCHAR(50) NOT NULL, -- e.g., 'Padron de Socios', 'Estado Financiero', 'Consolidado General'
    datos_json LONGTEXT NOT NULL,
    enviado_por INT NOT NULL,
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE,
    FOREIGN KEY (enviado_por) REFERENCES usuarios(id)
) ENGINE=InnoDB;

-- =========================================================================
-- DATOS SEMILLA PARA PRUEBAS (Contraseñas: maestro123 y admin123)
-- =========================================================================

-- 1. Insertar Usuario Maestro inicial
INSERT INTO usuarios (rut, nombre, email, password, rol, telefono, estado) 
VALUES (
    '1-9', 
    'Maestro ConectaBarrio', 
    'maestro@conectabarrio.cl', 
    '$2y$10$7ldDbnKH1qvmD4itXlC5k.bQDnRWEJknzChSfH7qnEGk9YErwC1kq', -- clave: maestro123
    'maestro', 
    '+56900000000', 
    1
);

-- 2. Insertar Junta de Vecinos semilla
INSERT INTO juntas_vecinos (id, nombre, rut_junta, direccion, comuna) 
VALUES (
    1, 
    'Junta de Vecinos El Progreso', 
    '70.123.456-7', 
    'Av. Libertador 123', 
    'Santiago'
);

-- 3. Insertar Administrador semilla para la Junta 1
INSERT INTO usuarios (junta_id, rut, nombre, email, password, rol, telefono, estado) 
VALUES (
    1, 
    '11.111.111-1', 
    'Ana Gómez (Administradora)', 
    'admin@progreso.cl', 
    '$2y$10$TbWQSmM5oCJE1qdqUkyvr.Ab/mS28oezLKHc/PDot.zvsjB8IguXm', -- clave: admin123
    'admin', 
    '+56911111111', 
    1
);

-- 4. Insertar Configuración de Cuota Inicial para la Junta 1 ($5.000 desde Enero 2026)
INSERT INTO configuracion_cuotas (junta_id, monto, mes_inicio) 
VALUES (
    1, 
    5000, 
    '2026-01'
);

-- 5. Insertar Socios semilla para la Junta 1
INSERT INTO usuarios (junta_id, rut, nombre, email, password, rol, telefono, estado) 
VALUES 
(
    1, 
    '22.222.222-2', 
    'Juan Pérez', 
    'juan@perez.cl', 
    '$2y$10$TbWQSmM5oCJE1qdqUkyvr.Ab/mS28oezLKHc/PDot.zvsjB8IguXm', -- clave: admin123
    'socio', 
    '+56922222222', 
    1
),
(
    1, 
    '33.333.333-3', 
    'María Carrasco', 
    'maria@carrasco.cl', 
    '$2y$10$TbWQSmM5oCJE1qdqUkyvr.Ab/mS28oezLKHc/PDot.zvsjB8IguXm', -- clave: admin123
    'socio', 
    '+56933333333', 
    1
);

-- 6. Insertar Transacciones iniciales para poblar el flujo de caja
INSERT INTO transacciones (junta_id, tipo, categoria, monto, descripcion, fecha, socio_id, mes_pagado, registrado_por) 
VALUES 
-- Ingresos por cuota
(1, 'ingreso', 'Cuota Socio', 5000, 'Pago cuota correspondiente a Enero 2026', '2026-01-15', 3, '2026-01', 2),
(1, 'ingreso', 'Cuota Socio', 5000, 'Pago cuota correspondiente a Febrero 2026', '2026-02-12', 3, '2026-02', 2),
(1, 'ingreso', 'Cuota Socio', 5000, 'Pago cuota correspondiente a Enero 2026', '2026-01-20', 4, '2026-01', 2),
-- Otros ingresos
(1, 'ingreso', 'Subsidio Municipal', 250000, 'Subsidio para mantención de áreas verdes', '2026-03-01', NULL, NULL, 2),
(1, 'ingreso', 'Donación', 50000, 'Donación anónima de comerciante local', '2026-03-10', NULL, NULL, 2),
-- Egresos
(1, 'egreso', 'Reparaciones', 45000, 'Compra de focos LED para la plaza', '2026-03-15', NULL, NULL, 2),
(1, 'egreso', 'Gasto Oficina', 12000, 'Compra de resmas de papel y carpetas', '2026-04-05', NULL, NULL, 2),
(1, 'egreso', 'Evento', 80000, 'Gastos de coctelería para reunión vecinal anual', '2026-04-20', NULL, NULL, 2);

-- 7. Insertar Reuniones iniciales
INSERT INTO reuniones (id, junta_id, titulo, descripcion, fecha_reunion, estado) 
VALUES 
(1, 1, 'Asamblea Extraordinaria de Presupuesto', 'Reunión para definir los proyectos del primer semestre y votar el ajuste de cuotas.', '2026-04-20 19:30:00', 'realizada'),
(2, 1, 'Reunión Mensual de Seguridad Vial', 'Discusión sobre instalación de lomos de toro en calle principal.', '2026-06-15 19:00:00', 'programada');

-- 8. Insertar Asistencias de la reunión 1
INSERT INTO asistencia (reunion_id, socio_id, asistio) 
VALUES 
(1, 3, 1), -- Juan Pérez asistió
(1, 4, 0); -- María Carrasco no asistió
