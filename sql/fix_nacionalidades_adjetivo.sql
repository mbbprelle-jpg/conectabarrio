-- Actualizar nacionalidades al formato en adjetivo (si existían valores con nombre de país)
UPDATE usuarios SET nacionalidad = 'Chilena' WHERE nacionalidad IN ('Chile', 'CHILE');
UPDATE usuarios SET nacionalidad = 'Boliviana' WHERE nacionalidad IN ('Bolivia', 'BOLIVIA');
UPDATE usuarios SET nacionalidad = 'Brasileña' WHERE nacionalidad IN ('Brasil', 'BRASIL');
UPDATE usuarios SET nacionalidad = 'Colombiana' WHERE nacionalidad IN ('Colombia', 'COLOMBIA');
UPDATE usuarios SET nacionalidad = 'Costarricense' WHERE nacionalidad IN ('Costa Rica', 'COSTA RICA');
UPDATE usuarios SET nacionalidad = 'Cubana' WHERE nacionalidad IN ('Cuba', 'CUBA');
UPDATE usuarios SET nacionalidad = 'Dominicana' WHERE nacionalidad IN ('República Dominicana', 'Republica Dominicana', 'REPÚBLICA DOMINICANA');
UPDATE usuarios SET nacionalidad = 'Ecuatoriana' WHERE nacionalidad IN ('Ecuador', 'ECUADOR');
UPDATE usuarios SET nacionalidad = 'Salvadoreña' WHERE nacionalidad IN ('El Salvador', 'EL SALVADOR');
UPDATE usuarios SET nacionalidad = 'Guatemalteca' WHERE nacionalidad IN ('Guatemala', 'GUATEMALA');
UPDATE usuarios SET nacionalidad = 'Haitiana' WHERE nacionalidad IN ('Haití', 'Haiti', 'HAITÍ');
UPDATE usuarios SET nacionalidad = 'Hondureña' WHERE nacionalidad IN ('Honduras', 'HONDURAS');
UPDATE usuarios SET nacionalidad = 'Mexicana' WHERE nacionalidad IN ('México', 'Mexico', 'MÉXICO');
UPDATE usuarios SET nacionalidad = 'Nicaragüense' WHERE nacionalidad IN ('Nicaragua', 'NICARAGUA');
UPDATE usuarios SET nacionalidad = 'Panameña' WHERE nacionalidad IN ('Panamá', 'Panama', 'PANAMÁ');
UPDATE usuarios SET nacionalidad = 'Paraguaya' WHERE nacionalidad IN ('Paraguay', 'PARAGUAY');
UPDATE usuarios SET nacionalidad = 'Peruana' WHERE nacionalidad IN ('Perú', 'Peru', 'PERÚ');
UPDATE usuarios SET nacionalidad = 'Uruguaya' WHERE nacionalidad IN ('Uruguay', 'URUGUAY');
UPDATE usuarios SET nacionalidad = 'Venezolana' WHERE nacionalidad IN ('Venezuela', 'VENEZUELA');
-- Valores fuera del listado actual (Belice, Guyana, etc.) quedan sin cambiar; revisar manualmente si aplica
