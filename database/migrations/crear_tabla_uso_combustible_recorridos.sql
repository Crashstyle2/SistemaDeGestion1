-- Crear tabla para registrar los recorridos de uso de combustible
CREATE TABLE IF NOT EXISTS uso_combustible_recorridos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uso_combustible_id INT NOT NULL,
    origen VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    km_inicial DECIMAL(10,2) NOT NULL,
    km_final DECIMAL(10,2) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (uso_combustible_id) REFERENCES uso_combustible(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Crear índices para mejorar el rendimiento
CREATE INDEX idx_uso_combustible_recorridos_uso_combustible ON uso_combustible_recorridos(uso_combustible_id);

-- Agregar permisos
GRANT SELECT, INSERT ON uso_combustible_recorridos TO 'tecnico'@'localhost';
GRANT SELECT, INSERT, UPDATE, DELETE ON uso_combustible_recorridos TO 'supervisor'@'localhost';
GRANT SELECT ON uso_combustible_recorridos TO 'administrativo'@'localhost';
GRANT ALL PRIVILEGES ON uso_combustible_recorridos TO 'administrador'@'localhost';