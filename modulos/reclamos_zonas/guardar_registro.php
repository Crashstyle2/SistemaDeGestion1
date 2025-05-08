<?php
header('Content-Type: application/json');
require_once '../../config/Database.php';

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $zona = trim($_POST['zona']);
    $mes = intval($_POST['mes']);
    $anio = intval($_POST['anio']);
    $cantidad_reclamos = intval($_POST['cantidad_reclamos']);

    // Validaciones básicas
    if (empty($zona) || $mes < 1 || $mes > 12 || $anio < 2000 || $anio > 2100) {
        throw new Exception('Datos inválidos');
    }

    // Usar una única consulta con ON DUPLICATE KEY UPDATE
    $query = "INSERT INTO reclamos_zonas (zona, mes, anio, cantidad_reclamos) 
              VALUES (:zona, :mes, :anio, :cantidad)
              ON DUPLICATE KEY UPDATE 
              cantidad_reclamos = VALUES(cantidad_reclamos)";
    
    $stmt = $conn->prepare($query);
    $result = $stmt->execute([
        ':zona' => $zona,
        ':mes' => $mes,
        ':anio' => $anio,
        ':cantidad' => $cantidad_reclamos
    ]);

    if($result) {
        echo json_encode(['success' => true]);
    } else {
        throw new Exception('Error al guardar el registro');
    }

} catch (Exception $e) {
    error_log("Error en guardar_registro.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'mensaje' => $e->getMessage()
    ]);
}
?>