<?php
// Iniciar output buffering para capturar cualquier output no deseado
ob_start();

// Configurar manejo de errores para evitar output HTML
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);

// Función para enviar respuesta JSON limpia
function sendJsonResponse($data) {
    // Limpiar cualquier output previo
    if (ob_get_length()) {
        ob_clean();
    }
    
    // Establecer headers correctos
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Enviar respuesta y terminar
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// Manejador de errores personalizado
set_error_handler(function($severity, $message, $file, $line) {
    error_log("PHP Error: $message in $file on line $line");
    sendJsonResponse([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
});

// Manejador de excepciones
set_exception_handler(function($exception) {
    error_log("PHP Exception: " . $exception->getMessage());
    sendJsonResponse([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
});

try {
    session_start();
    require_once '../../config/Database.php';
    require_once '../../models/UsoCombustible.php';
    require_once '../../config/ActivityLogger.php';

    // Verificar que el usuario esté logueado
    if (!isset($_SESSION['user_id'])) {
        sendJsonResponse(['success' => false, 'message' => 'Usuario no autenticado']);
    }

    // Obtener datos del POST
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        sendJsonResponse(['success' => false, 'message' => 'Datos JSON inválidos']);
    }
    
    $id = $input['id'] ?? null;
    $action = $input['action'] ?? null;
    $motivo = $input['motivo'] ?? null;

    if (!$id || !$action) {
        sendJsonResponse(['success' => false, 'message' => 'Datos incompletos']);
    }

    // Crear conexión a la base de datos
    $database = new Database();
    $conn = $database->getConnection();

    // Crear instancias de los modelos
    $usoCombustible = new UsoCombustible($conn);
    $registroActividad = new ActivityLogger($conn);

    if ($action === 'cerrar') {
        // Técnicos pueden cerrar sus propios recorridos, administradores pueden cerrar cualquiera
        if ($_SESSION['user_rol'] !== 'tecnico' && $_SESSION['user_rol'] !== 'administrador') {
            sendJsonResponse(['success' => false, 'message' => 'Solo los técnicos y administradores pueden cerrar recorridos']);
        }
        
        // Verificar que el recorrido pertenece al técnico (solo para técnicos)
        $query = "SELECT user_id, estado_recorrido FROM uso_combustible WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$id]);
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$registro) {
            sendJsonResponse(['success' => false, 'message' => 'Recorrido no encontrado']);
        }
        
        // Solo los técnicos deben verificar que el recorrido les pertenece
        if ($_SESSION['user_rol'] === 'tecnico' && $registro['user_id'] != $_SESSION['user_id']) {
            sendJsonResponse(['success' => false, 'message' => 'No puede cerrar recorridos de otros técnicos']);
        }
        
        if ($registro['estado_recorrido'] === 'cerrado') {
            sendJsonResponse(['success' => false, 'message' => 'El recorrido ya está cerrado']);
        }
        
        $resultado = $usoCombustible->cerrarRecorrido($id, $_SESSION['user_id']);
        
        if ($resultado['success']) {
            // Registrar actividad
            $tipo_usuario = $_SESSION['user_rol'] === 'administrador' ? 'administrador' : 'técnico';
            $registroActividad->registrar(
                $_SESSION['user_id'],
                'uso_combustible',
                'cerrar_recorrido',
                "Recorrido ID: {$id} cerrado por {$tipo_usuario}"
            );
        }
        
        sendJsonResponse($resultado);
        
    } elseif ($action === 'reabrir') {
        // Solo administradores pueden reabrir recorridos
        if ($_SESSION['user_rol'] !== 'administrador') {
            sendJsonResponse(['success' => false, 'message' => 'Solo los administradores pueden reabrir recorridos']);
        }
        
        if (empty($motivo)) {
            sendJsonResponse(['success' => false, 'message' => 'Debe proporcionar un motivo para la reapertura']);
        }
        
        $resultado = $usoCombustible->reabrirRecorrido($id, $_SESSION['user_id'], $motivo);
        
        if ($resultado['success']) {
            // Registrar actividad
            $registroActividad->registrar(
                $_SESSION['user_id'],
                'uso_combustible',
                'reabrir_recorrido',
                "Recorrido ID: {$id} reabierto por administrador. Motivo: {$motivo}"
            );
        }
        
        sendJsonResponse($resultado);
        
    } else {
        sendJsonResponse(['success' => false, 'message' => 'Acción no válida']);
    }
    
} catch (Exception $e) {
    error_log("Error en cerrar_recorrido.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false, 
        'message' => 'Error interno del servidor'
    ]);
}
?>