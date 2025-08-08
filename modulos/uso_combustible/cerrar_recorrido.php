<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../config/database.php';
include_once '../../models/UsoCombustible.php';
include_once '../../models/RegistroActividad.php';

$db = new Database();
$conn = $db->getConnection();
$usoCombustible = new UsoCombustible($conn);
$registroActividad = new RegistroActividad($conn);

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$id = $input['id'] ?? 0;
$motivo = $input['motivo'] ?? '';

header('Content-Type: application/json');

if ($action === 'cerrar') {
    // Solo técnicos pueden cerrar sus propios recorridos
    if ($_SESSION['user_rol'] !== 'tecnico') {
        echo json_encode(['success' => false, 'message' => 'Solo los técnicos pueden cerrar recorridos']);
        exit;
    }
    
    // Verificar que el recorrido pertenece al técnico
    $query = "SELECT user_id, estado_recorrido FROM uso_combustible WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro) {
        echo json_encode(['success' => false, 'message' => 'Recorrido no encontrado']);
        exit;
    }
    
    if ($registro['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'No puede cerrar recorridos de otros técnicos']);
        exit;
    }
    
    if ($registro['estado_recorrido'] === 'cerrado') {
        echo json_encode(['success' => false, 'message' => 'El recorrido ya está cerrado']);
        exit;
    }
    
    if ($usoCombustible->cerrarRecorrido($id, $_SESSION['user_id'])) {
        // Registrar actividad
        $registroActividad->registrar(
            $_SESSION['user_id'],
            'uso_combustible',
            'cerrar_recorrido',
            "Recorrido ID: {$id} cerrado por técnico"
        );
        
        echo json_encode(['success' => true, 'message' => 'Recorrido cerrado exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al cerrar el recorrido']);
    }
    
} elseif ($action === 'reabrir') {
    // Solo administradores pueden reabrir recorridos
    if ($_SESSION['user_rol'] !== 'administrador') {
        echo json_encode(['success' => false, 'message' => 'Solo los administradores pueden reabrir recorridos']);
        exit;
    }
    
    if (empty($motivo)) {
        echo json_encode(['success' => false, 'message' => 'Debe proporcionar un motivo para la reapertura']);
        exit;
    }
    
    if ($usoCombustible->reabrirRecorrido($id, $_SESSION['user_id'], $motivo)) {
        // Registrar actividad
        $registroActividad->registrar(
            $_SESSION['user_id'],
            'uso_combustible',
            'reabrir_recorrido',
            "Recorrido ID: {$id} reabierto por administrador. Motivo: {$motivo}"
        );
        
        echo json_encode(['success' => true, 'message' => 'Recorrido reabierto exitosamente']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al reabrir el recorrido']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
}
?>