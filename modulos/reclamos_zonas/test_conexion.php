<?php
require_once '../../config/database.php';
header('Content-Type: application/json');

try {
    // Probar conexión
    $stmt = $conn->query("SELECT COUNT(*) as total FROM reclamos_zonas");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Conexión exitosa',
        'total_registros' => $result['total']
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error de conexión: ' . $e->getMessage()
    ]);
}
?>