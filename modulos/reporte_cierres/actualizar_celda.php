<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../config/database.php';
include_once '../../models/ReporteCierres.php';

$database = new Database();
$db = $database->getConnection();
$reporte = new ReporteCierres($db);

try {
    $data = [
        'tecnico_id' => $_POST['tecnico_id'] ?? '',
        'mes' => $_POST['mes'] ?? '',
        'anio' => $_POST['anio'] ?? '',
        'cantidad' => $_POST['cantidad'] ?? '0',
        'justificacion' => $_POST['justificacion'] ?? 'N',
        'comentario' => $_POST['comentario'] ?? ''
    ];

    $result = $reporte->actualizarCierre(
        $data['tecnico_id'],
        $data['mes'],
        $data['anio'],
        $data['cantidad'],
        $data['justificacion'],
        $data['comentario']
    );

    if ($result) {
        // Add activity logging
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'reporte_cierres',
            'actualizar_celda',
            "Celda actualizada - Técnico: {$data['tecnico_id']}, Mes: {$data['mes']}, Año: {$data['anio']}, Cantidad: {$data['cantidad']}"
        );

        header('Content-Type: application/json');
        echo json_encode(['success' => $result]);
    }

} catch (Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}