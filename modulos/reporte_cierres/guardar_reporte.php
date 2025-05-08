<?php
session_start();
require_once '../../config/database.php';
require_once '../../models/ReporteCierres.php';

header('Content-Type: application/json');

try {
    if (empty($_POST['tecnico_id']) || empty($_POST['mes']) || empty($_POST['anio']) || !isset($_POST['cantidad'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $database = new Database();
    $db = $database->getConnection();
    $reporte = new ReporteCierres($db);

    $result = $reporte->actualizarCierre(
        intval($_POST['tecnico_id']),
        intval($_POST['mes']),
        intval($_POST['anio']),
        intval($_POST['cantidad']),
        $_POST['justificacion'] ?? 'N',
        $_POST['comentario'] ?? '',
        $_POST['cod_tec'] ?? ''
    );

    echo json_encode($result);

} catch (Exception $e) {
    error_log("Error en guardar_reporte.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'mensaje' => 'Error: ' . $e->getMessage()
    ]);
}