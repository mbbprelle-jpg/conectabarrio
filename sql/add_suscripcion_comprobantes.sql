-- Comprobantes internos de suscripción ConectaBarrio (correlativo por registro de pago)
CREATE TABLE IF NOT EXISTS suscripcion_comprobantes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    correlativo VARCHAR(20) NOT NULL,
    org_id INT NOT NULL,
    fecha_pago DATE NOT NULL,
    metodo_pago ENUM('transferencia', 'efectivo', 'webpay') NULL,
    total_amount INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_correlativo (correlativo),
    KEY idx_org_id (org_id),
    FOREIGN KEY (org_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
) ENGINE=InnoDB;

ALTER TABLE payments
ADD COLUMN comprobante_id INT NULL,
ADD KEY idx_comprobante_id (comprobante_id);
