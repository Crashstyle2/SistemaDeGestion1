<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../config/database.php';
include_once '../../models/ReporteCierres.php';

$database = new Database();
$db = $database->getConnection();
$reporte = new ReporteCierres($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = $_POST['id'];
    
    if ($reporte->eliminar($id)) {
        // Add activity logging
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'reporte_cierres',
            'eliminar',
            "Reporte eliminado - ID: {$id}"
        );

        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error al eliminar el registro']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Solicitud inválida']);
}