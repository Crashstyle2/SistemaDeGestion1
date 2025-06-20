ALTER TABLE usuarios
ADD COLUMN estado ENUM('activo', 'inactivo') NOT NULL DEFAULT 'activo';

-- Actualizar los registros existentes
UPDATE usuarios SET estado = 'activo';

-- Agregar índice para mejorar el rendimiento de las consultas
ALTER TABLE usuarios ADD INDEX idx_estado (estado);