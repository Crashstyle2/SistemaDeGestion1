-- Agregar nuevo rol administrativo
ALTER TABLE usuarios
MODIFY COLUMN rol ENUM('administrador', 'tecnico', 'supervisor', 'administrativo') NOT NULL DEFAULT 'tecnico';

-- Crear tabla para registro de uso de combustible
CREATE TABLE IF NOT EXISTS uso_combustible (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    tipo_movil ENUM('particular', 'empresa') NOT NULL,
    conductor VARCHAR(100) NOT NULL,
    chapa VARCHAR(20) NOT NULL,
    fecha_baucher DATE NOT NULL,
    hora_baucher TIME NOT NULL,
    numero_tarjeta VARCHAR(50) NOT NULL,
    origen VARCHAR(255) NOT NULL,
    destino VARCHAR(255) NOT NULL,
    kilometros_recorridos DECIMAL(10,2) NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ultima_modificacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES usuarios(id)
);

-- Crear tabla para permisos de modificación
CREATE TABLE IF NOT EXISTS permisos_modificacion_combustible (
    id INT AUTO_INCREMENT PRIMARY KEY,
    registro_id INT NOT NULL,
    user_id INT NOT NULL,
    puede_modificar BOOLEAN DEFAULT TRUE,
    fecha_limite TIMESTAMP,
    FOREIGN KEY (registro_id) REFERENCES uso_combustible(id),
    FOREIGN KEY (user_id) REFERENCES usuarios(id)
);