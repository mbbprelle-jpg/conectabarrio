-- Perfil ampliado del socio (registro por invitación)
USE conectabarrio;

ALTER TABLE usuarios ADD COLUMN genero ENUM('MASCULINO','FEMENINO','NO ESPECIFICAR') NULL;
ALTER TABLE usuarios ADD COLUMN fecha_nacimiento DATE NULL;
