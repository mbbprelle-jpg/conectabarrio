-- Agrega mes_periodo a payments para control mensual por organización
-- Ejecutar una sola vez en el servidor

ALTER TABLE payments ADD COLUMN mes_periodo VARCHAR(7) NULL AFTER org_id;

UPDATE payments SET mes_periodo = DATE_FORMAT(due_date, '%Y-%m') WHERE mes_periodo IS NULL;

ALTER TABLE payments ADD UNIQUE KEY unique_org_mes (org_id, mes_periodo);
