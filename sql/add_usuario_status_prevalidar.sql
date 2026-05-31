-- Estado intermedio para carga masiva: datos ingresados por admin/secretario,
-- pendientes de que el socio complete/confirme vía link de invitación o aprobación directa.
ALTER TABLE usuarios MODIFY COLUMN status ENUM('pending','active','prevalidar') NOT NULL DEFAULT 'active';
