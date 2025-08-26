<?php
// Script para eliminar datos de prueba
require_once '../../config/database.php';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Eliminando datos de prueba</h2>";
    
    // Eliminar registros de prueba
    $sql = "DELETE FROM uso_combustible WHERE nombre_conductor LIKE '%PRUEBA%' OR chapa = 'TEST123' OR numero_baucher = 'PRUEBA001'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    
    $deletedRows = $stmt->rowCount();
    
    if ($deletedRows > 0) {
        echo "<p>✅ Se eliminaron $deletedRows registros de prueba.</p>";
    } else {
        echo "<p>ℹ️ No se encontraron registros de prueba para eliminar.</p>";
    }
    
    echo "<p><a href='ver_registros.php' class='btn btn-primary'>Ver Registros</a></p>";
    echo "<p><a href='crear_datos_prueba.php' class='btn btn-success'>Crear Nuevos Datos de Prueba</a></p>";
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>