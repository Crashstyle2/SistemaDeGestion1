-- Crear tabla para registros de uso de combustible
CREATE TABLE IF NOT EXISTS uso_combustible (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    fecha DATE NOT NULL,
    vehiculo VARCHAR(100) NOT NULL,
    kilometraje DECIMAL(10,2) NOT NULL,
    litros DECIMAL(10,2) NOT NULL,
    observaciones TEXT,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear índices para mejorar el rendimiento de las consultas
CREATE INDEX idx_uso_combustible_fecha ON uso_combustible(fecha);
CREATE INDEX idx_uso_combustible_user ON uso_combustible(user_id);
CREATE INDEX idx_uso_combustible_vehiculo ON uso_combustible(vehiculo);

-- Crear vista para facilitar los reportes
CREATE OR REPLACE VIEW v_uso_combustible AS
SELECT 
    uc.*,
    u.nombre as nombre_usuario,
    u.rol as rol_usuario
FROM uso_combustible uc
LEFT JOIN usuarios u ON uc.user_id = u.id;

-- Agregar permisos básicos
GRANT SELECT, INSERT ON uso_combustible TO 'tecnico'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON uso_combustible TO 'supervisor'@'localhost';
GRANT SELECT ON uso_combustible TO 'administrativo'@'localhost';
GRANT ALL PRIVILEGES ON uso_combustible TO 'administrador'@'localhost';