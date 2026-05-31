-- Estado civil y nacionalidad del socio
ALTER TABLE usuarios ADD COLUMN estado_civil ENUM(
    'SOLTERO',
    'CASADO',
    'CONVIVIENTE_CIVIL',
    'DIVORCIADO',
    'VIUDO'
) NULL AFTER fecha_nacimiento;

ALTER TABLE usuarios ADD COLUMN nacionalidad VARCHAR(80) NULL AFTER estado_civil;
