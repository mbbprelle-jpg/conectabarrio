-- Fecha de nacimiento de hijos y personas con discapacidad del censo Navidad.
-- La edad sigue guardándose (años cumplidos al momento del registro) y se calcula desde esta fecha.
-- Ejecutar una vez en la base de datos de producción.

ALTER TABLE censo_personas
    ADD COLUMN fecha_nacimiento DATE NULL AFTER edad;
