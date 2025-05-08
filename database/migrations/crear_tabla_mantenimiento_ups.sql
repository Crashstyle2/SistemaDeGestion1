CREATE TABLE mantenimiento_ups (
    patrimonio VARCHAR(50) PRIMARY KEY,
    cadena VARCHAR(100) NOT NULL,
    sucursal VARCHAR(100) NOT NULL,
    marca VARCHAR(100) NOT NULL,
    tipo_bateria VARCHAR(100) NOT NULL,
    cantidad INT NOT NULL,
    potencia_ups VARCHAR(50) NOT NULL,
    fecha_ultimo_mantenimiento DATE NOT NULL,
    fecha_proximo_mantenimiento DATE NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);