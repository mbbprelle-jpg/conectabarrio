-- Fecha desde la cual la organización debe pagar suscripción ConectaBarrio
-- (distinto de mes_inicio = inicio de actividades / cierres / cuotas internas)

ALTER TABLE juntas_vecinos
ADD COLUMN mes_inicio_suscripcion VARCHAR(7) NULL AFTER precio_anual;

UPDATE juntas_vecinos
SET mes_inicio_suscripcion = DATE_FORMAT(created_at, '%Y-%m')
WHERE mes_inicio_suscripcion IS NULL;
