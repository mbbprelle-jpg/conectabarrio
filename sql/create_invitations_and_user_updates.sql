CREATE TABLE IF NOT EXISTS invitations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    junta_id INT NOT NULL,
    token VARCHAR(64) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    status ENUM('pending','accepted','revoked') DEFAULT 'pending',
    FOREIGN KEY (junta_id) REFERENCES juntas_vecinos(id) ON DELETE CASCADE
);

-- Add status column to usuarios to track pending/active
ALTER TABLE usuarios ADD COLUMN status ENUM('pending','active') NOT NULL DEFAULT 'active';

-- Add invitation_id foreign key to usuarios for pending registrations
ALTER TABLE usuarios ADD COLUMN invitation_id INT NULL;
ALTER TABLE usuarios ADD CONSTRAINT fk_invitation_user FOREIGN KEY (invitation_id) REFERENCES invitations(id) ON DELETE SET NULL;

-- Índice único id_socio por organización (ejecutar solo si no existe idx_junta_id_socio)
-- ALTER TABLE usuarios DROP INDEX idx_id_socio;
-- ALTER TABLE usuarios ADD UNIQUE INDEX idx_junta_id_socio (junta_id, id_socio);
