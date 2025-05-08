<?php
// Mostrar errores para debug
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Verificar archivo de configuración
if (!file_exists('config/database.php')) {
    die('Error: Archivo config/database.php no encontrado');
}

include_once 'config/database.php';

try {
    echo "<h2>Información de conexión:</h2>";
    echo "PHP Version: " . phpversion() . "<br>";
    echo "Document Root: " . $_SERVER['DOCUMENT_ROOT'] . "<br>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Mostrar configuración de la base de datos
    echo "<h3>Configuración de la base de datos:</h3>";
    echo "Driver: " . $db->getAttribute(PDO::ATTR_DRIVER_NAME) . "<br>";
    echo "Server Info: " . $db->getAttribute(PDO::ATTR_SERVER_INFO) . "<br>";
    
    $query = "SELECT * FROM usuarios";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    echo "<h3>Usuarios encontrados:</h3>";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<pre>";
        print_r($row);
        echo "</pre>";
    }
    
} catch(PDOException $e) {
    echo "<h3>Error:</h3>";
    echo $e->getMessage();
}
?>