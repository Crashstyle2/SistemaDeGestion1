<?php
session_start();
require_once '../../config/Database.php';
require_once '../../models/ReclamosZonas.php';

// Configurar el registro de errores en un archivo local
ini_set('error_log', __DIR__ . '/error_log.txt');
error_log('Datos recibidos: ' . print_r($_POST, true));

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Usuario no autenticado');
    }

    // Inicializar la conexión y el modelo
    $database = new Database();
    $conn = $database->getConnection();
    $reclamos = new ReclamosZonas($conn);

    // Validación de datos de entrada
    if (!isset($_POST['zona']) || !isset($_POST['mes']) || !isset($_POST['anio']) || !isset($_POST['valor'])) {
        throw new Exception('Faltan datos requeridos');
    }

    $zona = trim($_POST['zona']);
    $mes = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];
    $valor = (int)$_POST['valor'];

    // Usar el método del modelo para actualizar
    $resultado = $reclamos->actualizarReclamos(
        $zona, 
        $mes, 
        $anio, 
        $valor,
        $_SESSION['user_id']
    );

    echo json_encode($resultado);

} catch (Exception $e) {
    error_log('Error en guardar_valor.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar: ' . $e->getMessage()
    ]);
}