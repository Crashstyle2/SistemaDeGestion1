<?php
require_once '../../config/database.php';
require_once '../../models/ReporteCierres.php';

// Prevent any output before JSON
ob_start();

$database = new Database();
$db = $database->getConnection();
$reporte = new ReporteCierres($db);

$response = ['success' => false];

try {
    if (!isset($_POST['tecnico_id'], $_POST['mes'], $_POST['anio'])) {
        throw new Exception('Datos incompletos');
    }

    $datos = $reporte->obtenerRegistroExistente(
        $_POST['tecnico_id'],
        $_POST['mes'],
        $_POST['anio']
    );

    if ($datos) {
        $response = [
            'success' => true,
            'cantidad_cierres' => $datos['cantidad_cierres'],
            'justificacion' => $datos['justificacion'],
            'comentario' => $datos['comentario_medida'],
            'cod_tec' => $datos['cod_tec']
        ];
    } else {
        $response['mensaje'] = 'No se encontraron datos para este registro';
    }
} catch (Exception $e) {
    $response['success'] = false;
    $response['mensaje'] = $e->getMessage();
}

// Clean any output buffers
ob_clean();

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

// Return JSON response
echo json_encode($response, JSON_UNESCAPED_UNICODE);
exit;