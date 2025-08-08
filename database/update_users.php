<?php
include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Primero crear la tabla si no existe
    $db->exec("CREATE TABLE IF NOT EXISTS usuarios (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        nombre VARCHAR(100) NOT NULL,
        rol VARCHAR(20) NOT NULL
    )");
    
    // Limpiar tabla
    $db->exec("TRUNCATE TABLE usuarios");
    
    // Insertar usuarios
    $query = "INSERT INTO usuarios (username, password, nombre, rol) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    
    $usuarios = [
        ['jucaceres', 'Macross0', 'Juan Caceres', 'administrador'],
        ['macarballo', 'Temporal1', 'Mauro Carballo', 'tecnico'],
        ['jubarrios', 'Temporal1', 'Julio Barrios', 'tecnico'],
        ['vaguero', 'Temporal1', 'Victor Agüero', 'tecnico']
    ];
    
    foreach($usuarios as $usuario) {
        $stmt->execute($usuario);
    }
    
    echo "Usuarios actualizados correctamente";
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>