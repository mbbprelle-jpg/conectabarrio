-- Certificados de residencia. La aplicación también crea estas tablas al abrir el módulo.
-- folio_certificado es el último folio ya usado. Si van en el 129, el próximo impreso será el 130.

ALTER TABLE juntas_vecinos
    ADD COLUMN folio_certificado INT NOT NULL DEFAULT 129,
    ADD COLUMN certificado_token VARCHAR(64) NULL;

CREATE TABLE IF NOT EXISTS certificados_residencia (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    origen ENUM('socio','publico') NOT NULL,
    para ENUM('si_mismo','domicilio','externo') NOT NULL,
    solicitante_usuario_id INT NULL,
    beneficiario_nombre VARCHAR(200) NOT NULL,
    beneficiario_rut VARCHAR(20) NOT NULL,
    calle_id INT NULL,
    numero_casa VARCHAR(20) NULL,
    direccion VARCHAR(255) NOT NULL,
    motivo VARCHAR(500) NOT NULL,
    es_tarifa_socio TINYINT(1) NOT NULL DEFAULT 0,
    monto INT NOT NULL,
    estado ENUM('pendiente','emitido','rechazado') NOT NULL DEFAULT 'pendiente',
    fecha_certificado DATE NULL,
    correlativo INT NULL,
    metodo_pago ENUM('efectivo','transferencia') NULL,
    revisado_por INT NULL,
    emitido_at DATETIME NULL,
    motivo_rechazo VARCHAR(300) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_cert_junta (junta_id, estado),
    UNIQUE KEY uk_cert_folio (junta_id, correlativo)
) ENGINE=InnoDB;
