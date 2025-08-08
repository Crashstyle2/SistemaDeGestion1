<?php
header('Content-Type: application/json; charset=utf-8');

session_start();
require_once '../../config/Database.php';

// Verificar autenticación
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['error' => 'No autenticado']);
    exit;
}

// Obtener ID del registro
$input = json_decode(file_get_contents('php://input'), true);
$id = $input['id'] ?? null;

if (!$id) {
    echo json_encode(['error' => 'ID requerido']);
    exit;
}

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    $query = "SELECT estado_recorrido, fecha_cierre, fecha_reapertura FROM uso_combustible WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($registro) {
        echo json_encode([
            'estado' => $registro['estado_recorrido'],
            'fecha_cierre' => $registro['fecha_cierre'],
            'fecha_reapertura' => $registro['fecha_reapertura']
        ]);
    } else {
        echo json_encode(['error' => 'Registro no encontrado']);
    }
    
} catch (Exception $e) {
    echo json_encode(['error' => 'Error del servidor']);
}
?>