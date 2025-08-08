CREATE TABLE reclamos_zonas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    zona VARCHAR(50) NOT NULL,
    mes INT NOT NULL,
    anio INT NOT NULL,
    cantidad_reclamos INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY zona_mes_anio (zona, mes, anio)
);