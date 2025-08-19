<?php
session_start();
require_once 'ActivityLogger.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['user_id'])) {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'log') {
        $modulo = $_POST['modulo'] ?? '';
        $accion = $_POST['accion'] ?? '';
        $detalle = $_POST['detalle'] ?? '';
        
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            $modulo,
            $accion,
            $detalle
        );
        
        echo json_encode(['success' => true]);
    }
}
?>