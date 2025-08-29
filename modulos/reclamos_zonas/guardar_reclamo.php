<?php
ob_start();
require_once '../../config/session_config.php';
require_once '../../config/Database.php';
session_start();

header('Content-Type: application/json; charset=utf-8');

if(!isset($_SESSION['user_id']) || !in_array($_SESSION['user_rol'], ['administrador', 'supervisor'])) {
    echo json_encode(['success' => false, 'mensaje' => 'No autorizado']);
    exit;
}

try {
    // Crear conexión a la base de datos con manejo de errores mejorado
    try {
        $database = new Database();
        $conn = $database->getConnection();
    } catch (PDOException $e) {
        error_log("Error de conexión: " . $e->getMessage());
        echo json_encode(['success' => false, 'mensaje' => 'Error de conexión a la base de datos']);
        exit;
    }

    if (!$conn) {
        throw new Exception('No se pudo establecer la conexión con la base de datos');
    }

    // Obtener y validar datos del POST
    $zona = isset($_POST['zona']) ? trim($_POST['zona']) : '';
    $mes = isset($_POST['mes']) ? intval($_POST['mes']) : 0;
    $anio = isset($_POST['anio']) ? intval($_POST['anio']) : 0;
    $cantidad = isset($_POST['cantidad']) ? intval($_POST['cantidad']) : 0;

    // Validación de datos
    if (empty($zona)) {
        throw new Exception('La zona es requerida');
    }
    if ($mes < 1 || $mes > 12) {
        throw new Exception('El mes no es válido');
    }
    if ($anio < 2024) {
        throw new Exception('El año no es válido');
    }
    if ($cantidad < 0) {
        throw new Exception('La cantidad no puede ser negativa');
    }

    // Verificar si la tabla existe
    $stmt = $conn->query("SHOW TABLES LIKE 'reclamos_zonas'");
    if ($stmt->rowCount() == 0) {
        // Crear la tabla si no existe
        $conn->exec("CREATE TABLE IF NOT EXISTS reclamos_zonas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            zona VARCHAR(50) NOT NULL,
            mes INT NOT NULL,
            anio INT NOT NULL,
            cantidad_reclamos INT NOT NULL,
            UNIQUE KEY unique_registro (zona, mes, anio)
        )");
    }

    $stmt = $conn->prepare("INSERT INTO reclamos_zonas (zona, mes, anio, cantidad_reclamos) 
                           VALUES (?, ?, ?, ?) 
                           ON DUPLICATE KEY UPDATE cantidad_reclamos = VALUES(cantidad_reclamos)");
    
    $resultado = $stmt->execute([$zona, $mes, $anio, $cantidad]);

    if ($resultado) {
        // Add activity logging
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'reclamos_zonas',
            'crear',
            "Nuevo reclamo registrado - Zona: $zona, Mes: $mes/$anio, Cantidad: $cantidad"
        );

        echo json_encode(['success' => true, 'mensaje' => 'Registro guardado correctamente']);
    } else {
        throw new Exception('Error al guardar en la base de datos');
    }

} catch (PDOException $e) {
    error_log("Error PDO: " . $e->getMessage());
    echo json_encode(['success' => false, 'mensaje' => 'Error de base de datos: ' . $e->getMessage()]);
} catch (Exception $e) {
    error_log("Error General: " . $e->getMessage());
    echo json_encode(['success' => false, 'mensaje' => $e->getMessage()]);
}