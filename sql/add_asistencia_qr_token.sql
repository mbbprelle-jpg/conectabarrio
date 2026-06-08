-- Token único por usuario para registro rápido de asistencia vía QR
ALTER TABLE usuarios
ADD COLUMN asistencia_qr_token VARCHAR(64) NULL DEFAULT NULL AFTER telefono;

CREATE UNIQUE INDEX idx_usuarios_asistencia_qr_token ON usuarios (asistencia_qr_token);
