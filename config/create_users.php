<?php
include_once 'database.php';

$database = new Database();
$db = $database->getConnection();

// Primero, asegurarnos que la tabla esté vacía
$db->exec("TRUNCATE TABLE usuarios");

$users = [
    ['username' => 'jucaceres', 'password' => 'Macross0', 'nombre' => 'Juan Caceres'],
    ['username' => 'macarballo', 'password' => 'Temporal1', 'nombre' => 'Mario Carballo'],
    ['username' => 'jubarrios', 'password' => 'Temporal1', 'nombre' => 'Juan Barrios'],
    ['username' => 'vaguero', 'password' => 'Temporal1', 'nombre' => 'Victor Aguero']
];

$query = "INSERT INTO usuarios (username, password, nombre) VALUES (:username, :password, :nombre)";
$stmt = $db->prepare($query);

foreach ($users as $user) {
    $hashed_password = password_hash($user['password'], PASSWORD_DEFAULT);
    
    try {
        $stmt->bindParam(':username', $user['username']);
        $stmt->bindParam(':password', $hashed_password);
        $stmt->bindParam(':nombre', $user['nombre']);
        
        if($stmt->execute()) {
            echo "Usuario {$user['username']} creado exitosamente<br>";
        } else {
            echo "Error al crear usuario {$user['username']}<br>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage() . "<br>";
    }
}

echo "Proceso completado";
?>