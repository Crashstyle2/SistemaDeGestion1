<?php
session_start();
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    echo json_encode(['success' => false, 'message' => 'Acceso no autorizado']);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/InformeTecnico.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $informe = new InformeTecnico($db);
        
        $id = intval($_POST['id']);
        
        // Verificar si el ID existe
        $result = $informe->eliminar($id);
        
        if($result) {
            // Agregar registro de actividad
            require_once '../../config/ActivityLogger.php';
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'informe_tecnico',
                'eliminar',
                "Informe técnico eliminado - ID: {$id}"
            );
            
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No se encontró el informe o no se pudo eliminar']);
        }
    } catch (Exception $e) {
        error_log("Error al eliminar informe: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Error interno al procesar la solicitud']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método de solicitud no válido']);
}