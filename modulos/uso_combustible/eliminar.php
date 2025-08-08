<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado. Solo los administradores pueden eliminar registros.']);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/UsoCombustible.php';

if($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if(!$db) {
            throw new Exception('Error de conexión a la base de datos');
        }
        
        $usoCombustible = new UsoCombustible($db);
        $id = $_POST['id'];
        
        // Obtener información del registro antes de eliminarlo para el log
        $query = "SELECT nombre_conductor, chapa, fecha FROM uso_combustible WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':id', $id);
        $stmt->execute();
        $registro = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if(!$registro) {
            throw new Exception('Registro no encontrado');
        }
        
        if($usoCombustible->eliminar($id)) {
            // Registrar actividad
            if(file_exists('../../config/ActivityLogger.php')) {
                require_once '../../config/ActivityLogger.php';
                ActivityLogger::logAccion(
                    $_SESSION['user_id'],
                    'uso_combustible',
                    'eliminar',
                    "Registro de combustible eliminado - Conductor: {$registro['nombre_conductor']}, Chapa: {$registro['chapa']}, Fecha: {$registro['fecha']}"
                );
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Registro eliminado correctamente'
            ]);
        } else {
            throw new Exception('Error al eliminar el registro');
        }
        
    } catch(Exception $e) {
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage()
        ]);
    }
} else {
    echo json_encode([
        'success' => false, 
        'message' => 'Método de solicitud no válido o ID no proporcionado'
    ]);
}
?>