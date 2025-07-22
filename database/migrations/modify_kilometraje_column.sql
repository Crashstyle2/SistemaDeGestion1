-- Modificar la columna kilometraje para hacerla opcional
ALTER TABLE uso_combustible
MODIFY COLUMN kilometraje DECIMAL(10,2) NULL DEFAULT NULL;