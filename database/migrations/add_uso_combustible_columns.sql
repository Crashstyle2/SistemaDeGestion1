-- Agregar nuevas columnas a la tabla uso_combustible
ALTER TABLE uso_combustible
ADD COLUMN nombre_conductor VARCHAR(255) NOT NULL AFTER fecha,
ADD COLUMN chapa VARCHAR(50) NOT NULL AFTER nombre_conductor,
ADD COLUMN numero_baucher VARCHAR(100) NOT NULL AFTER chapa,
ADD COLUMN tarjeta VARCHAR(4) NOT NULL AFTER numero_baucher,
ADD COLUMN litros_cargados DECIMAL(10,2) NOT NULL AFTER tarjeta,
ADD COLUMN tipo_vehiculo ENUM('particular', 'movil_retail') NOT NULL AFTER litros_cargados,
ADD COLUMN fecha_carga DATE NOT NULL AFTER tipo_vehiculo,
ADD COLUMN documento VARCHAR(100) NOT NULL AFTER fecha_carga,
ADD COLUMN hora_carga TIME NOT NULL AFTER documento;

-- Crear tabla para los recorridos
CREATE TABLE IF NOT EXISTS uso_combustible_recorridos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uso_combustible_id INT NOT NULL,
    origen VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    km_inicial DECIMAL(10,2),
    km_final DECIMAL(10,2),
    FOREIGN KEY (uso_combustible_id) REFERENCES uso_combustible(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;