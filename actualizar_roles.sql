-- Script para actualizar los roles en la base de datos
-- Cambiar 'administrativo' por 'analista' y mantener 'supervisor'

-- Paso 1: Actualizar los registros existentes de 'administrativo' a 'analista'
UPDATE usuarios SET rol = 'analista' WHERE rol = 'administrativo';

-- Paso 2: Modificar la estructura de la tabla para cambiar el enum
ALTER TABLE usuarios MODIFY COLUMN rol enum('administrador','tecnico','supervisor','analista') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'tecnico';

-- Verificar los cambios
SELECT id, username, nombre, rol FROM usuarios ORDER BY rol, nombre;

-- Mostrar la estructura actualizada de la tabla
DESCRIBE usuarios;