CREATE TABLE acuse_recibo (
    id INT AUTO_INCREMENT PRIMARY KEY,
    local VARCHAR(100) NOT NULL,
    sector VARCHAR(100) NOT NULL,
    documento VARCHAR(255) NOT NULL,
    foto MEDIUMBLOB,
    jefe_encargado VARCHAR(100) NOT NULL,
    observaciones TEXT,
    firma_digital TEXT,
    fecha_creacion DATETIME DEFAULT CURRENT_TIMESTAMP,
    tecnico_id INT NOT NULL,
    FOREIGN KEY (tecnico_id) REFERENCES usuarios(id)
);