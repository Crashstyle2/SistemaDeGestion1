<?php
require_once '../../config/Database.php';

// Iniciar sesión sin redirecciones automáticas
session_start();

// Verificar autenticación para API
if (!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'No autenticado']);
    exit();
}

// Configuración de zona horaria
date_default_timezone_set('America/Asuncion');

// Verificar expiración de sesión
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header('Content-Type: application/json');
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesión expirada']);
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();

// Función para enviar respuesta JSON
function sendJsonResponse($data) {
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($data);
    exit;
}

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJsonResponse([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}

// Obtener datos JSON
$rawInput = file_get_contents('php://input');
$input = json_decode($rawInput, true);

if (!$input || !isset($input['id'])) {
    sendJsonResponse([
        'success' => false,
        'message' => 'ID requerido'
    ]);
}

$id = $input['id'];

try {
    $database = new Database();
    $conn = $database->getConnection();
    
    // Consultar el estado actual del registro
    $query = "SELECT estado_recorrido, user_id FROM uso_combustible WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$id]);
    $registro = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$registro) {
        sendJsonResponse([
            'success' => false,
            'message' => 'Registro no encontrado'
        ]);
    }
    
    sendJsonResponse([
        'success' => true,
        'estado' => $registro['estado_recorrido'],
        'user_id' => $registro['user_id']
    ]);
    
} catch (Exception $e) {
    error_log("Error en verificar_estado.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>