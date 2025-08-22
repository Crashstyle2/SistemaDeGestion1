<?php
require_once '../../config/Database.php';
require_once '../../models/UsoCombustible.php';
require_once '../../config/ActivityLogger.php';

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

// Configurar el output buffering
ob_start();

// Configurar el manejo de errores
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '../../logs/php_errors.log');

// Función para logging detallado
function logDebug($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    if ($data !== null) {
        $logMessage .= " - Data: " . json_encode($data);
    }
    $logMessage .= "\n";
    file_put_contents('../../logs/debug_cerrar_recorrido.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Función para enviar respuesta JSON
function sendJsonResponse($data) {
    ob_clean();
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    echo json_encode($data);
    exit;
}

// Manejador de errores personalizado - solo para errores críticos
set_error_handler(function($severity, $message, $file, $line) {
    // Solo manejar errores críticos que impidan la ejecución
    if ($severity & (E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR)) {
        error_log("Error crítico: $message en $file línea $line");
        sendJsonResponse([
            'success' => false,
            'message' => 'Error interno del servidor'
        ]);
    } else {
        // Para warnings y notices, solo registrar en el log sin interrumpir
        error_log("Warning/Notice: $message en $file línea $line");
        return false; // Permitir que PHP maneje el error normalmente
    }
});

// Manejador de excepciones
set_exception_handler(function($exception) {
    error_log("Excepción no capturada: " . $exception->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
});

// Log inicio de petición
logDebug('=== INICIO PETICIÓN ===', [
    'method' => $_SERVER['REQUEST_METHOD'],
    'user_id' => $_SESSION['user_id'] ?? 'no_session',
    'user_rol' => $_SESSION['user_rol'] ?? 'no_session'
]);

// Verificar que sea una petición POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    logDebug('ERROR: Método no permitido', $_SERVER['REQUEST_METHOD']);
    sendJsonResponse([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}

// Obtener datos JSON
$rawInput = file_get_contents('php://input');
logDebug('Raw input recibido', $rawInput);

$input = json_decode($rawInput, true);
logDebug('Input decodificado', $input);

if (!$input) {
    logDebug('ERROR: Datos JSON inválidos', $rawInput);
    sendJsonResponse([
        'success' => false,
        'message' => 'Datos inválidos'
    ]);
}

$id = $input['id'] ?? null;
$action = $input['action'] ?? null;

logDebug('Parámetros extraídos', ['id' => $id, 'action' => $action]);

if (!$id || !$action) {
    logDebug('ERROR: Parámetros faltantes', ['id' => $id, 'action' => $action]);
    sendJsonResponse([
        'success' => false,
        'message' => 'Parámetros requeridos faltantes'
    ]);
}

try {
    logDebug('Iniciando conexión a base de datos');
    $database = new Database();
    $conn = $database->getConnection();
    logDebug('Conexión establecida, creando objetos');
    $usoCombustible = new UsoCombustible($conn);
    $activityLogger = new ActivityLogger($conn);
    logDebug('Objetos creados exitosamente');
    
    if ($action === 'cerrar') {
        logDebug('=== PROCESANDO ACCIÓN CERRAR ===');
        // Verificar permisos para cerrar
        $rol = $_SESSION['user_rol'];
        logDebug('Verificando permisos', ['rol' => $rol]);
        if (!in_array($rol, ['tecnico', 'supervisor', 'administrador'])) {
            logDebug('ERROR: Sin permisos para cerrar', ['rol' => $rol]);
            sendJsonResponse([
                'success' => false,
                'message' => 'No tiene permisos para cerrar recorridos'
            ]);
        }
        
        // Verificar que el usuario sea el propietario del registro (excepto administradores)
        if ($rol !== 'administrador') {
            $query = "SELECT user_id FROM uso_combustible WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->execute([$id]);
            $registro = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$registro || $registro['user_id'] != $_SESSION['user_id']) {
                sendJsonResponse([
                    'success' => false,
                    'message' => 'Solo puede cerrar sus propios recorridos'
                ]);
            }
        }
        
        // Cerrar el recorrido
        logDebug('Intentando cerrar recorrido', ['id' => $id, 'user_id' => $_SESSION['user_id']]);
        $resultado = $usoCombustible->cerrarRecorrido($id, $_SESSION['user_id']);
        logDebug('Resultado de cerrar recorrido', ['resultado' => $resultado]);
        
        if ($resultado) {
            // Registrar actividad
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'uso_combustible',
                'cerrar_recorrido',
                "Recorrido cerrado - ID: $id"
            );
            
            logDebug('Recorrido cerrado exitosamente', ['id' => $id]);
            sendJsonResponse([
                'success' => true,
                'message' => 'Recorrido cerrado exitosamente'
            ]);
        } else {
            logDebug('ERROR: No se pudo cerrar el recorrido', ['id' => $id]);
            sendJsonResponse([
                'success' => false,
                'message' => 'No se pudo cerrar el recorrido'
            ]);
        }
        
    } elseif ($action === 'reabrir') {
        logDebug('=== PROCESANDO ACCIÓN REABRIR ===');
        // Solo administradores pueden reabrir
        logDebug('Verificando permisos de administrador', ['rol' => $_SESSION['user_rol']]);
        if ($_SESSION['user_rol'] !== 'administrador') {
            logDebug('ERROR: Sin permisos de administrador para reabrir');
            sendJsonResponse([
                'success' => false,
                'message' => 'Solo los administradores pueden reabrir recorridos'
            ]);
        }
        
        $motivo = $input['motivo'] ?? null;
        logDebug('Motivo recibido', ['motivo' => $motivo]);
        if (!$motivo || trim($motivo) === '') {
            logDebug('ERROR: Motivo vacío o faltante');
            sendJsonResponse([
                'success' => false,
                'message' => 'El motivo es requerido para reabrir un recorrido'
            ]);
        }
        
        // Reabrir el recorrido
        logDebug('Intentando reabrir recorrido', ['id' => $id, 'user_id' => $_SESSION['user_id'], 'motivo' => $motivo]);
        $resultado = $usoCombustible->reabrirRecorrido($id, $_SESSION['user_id'], $motivo);
        logDebug('Resultado de reabrir recorrido', ['resultado' => $resultado]);
        
        if ($resultado) {
            // Registrar actividad
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'uso_combustible',
                'reabrir_recorrido',
                "Recorrido reabierto - ID: $id, Motivo: $motivo"
            );
            
            sendJsonResponse([
                'success' => true,
                'message' => 'Recorrido reabierto exitosamente'
            ]);
        } else {
            sendJsonResponse([
                'success' => false,
                'message' => 'No se pudo reabrir el recorrido'
            ]);
        }
        
    } else {
        sendJsonResponse([
            'success' => false,
            'message' => 'Acción no válida'
        ]);
    }
    
} catch (Exception $e) {
    logDebug('=== EXCEPCIÓN CAPTURADA ===', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);
    error_log("Error en cerrar_recorrido.php: " . $e->getMessage());
    sendJsonResponse([
        'success' => false,
        'message' => 'Error interno del servidor'
    ]);
}
?>