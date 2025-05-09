<?php
require_once '../../config/Database.php';
require_once '../../models/RegistroActividad.php';

// Configurar el registro de errores en un archivo local
ini_set('error_log', __DIR__ . '/error_log.txt');
error_log('Datos recibidos: ' . print_r($_POST, true));

header('Content-Type: application/json');

try {
    // Inicializar la conexión usando la clase Database existente
    $database = new Database();
    $conn = $database->getConnection();

    // Validación de datos de entrada
    if (!isset($_POST['zona']) || !isset($_POST['mes']) || !isset($_POST['anio']) || !isset($_POST['valor'])) {
        throw new Exception('Faltan datos requeridos: ' . implode(', ', array_keys($_POST)));
    }

    $zona = trim($_POST['zona']);
    $mes = (int)$_POST['mes'];
    $anio = (int)$_POST['anio'];
    $valor = (int)$_POST['valor'];

    // Debug: Guardar los datos procesados
    error_log("Datos procesados - Zona: $zona, Mes: $mes, Año: $anio, Valor: $valor");

    $sql = "INSERT INTO reclamos_zonas (zona, mes, anio, cantidad_reclamos) 
            VALUES (:zona, :mes, :anio, :valor) 
            ON DUPLICATE KEY UPDATE cantidad_reclamos = :valor";

    $stmt = $conn->prepare($sql);
    
    $stmt->bindParam(':zona', $zona, PDO::PARAM_STR);
    $stmt->bindParam(':mes', $mes, PDO::PARAM_INT);
    $stmt->bindParam(':anio', $anio, PDO::PARAM_INT);
    $stmt->bindParam(':valor', $valor, PDO::PARAM_INT);
    
    if($stmt->execute()) {
        // Solo intentar registrar la actividad si la operación principal fue exitosa
        try {
            $registro = new RegistroActividad($conn);
            $registro->registrar(
                isset($_SESSION['usuario_id']) ? $_SESSION['usuario_id'] : 0,
                'reclamos_zonas',
                'actualizar_reclamo',
                "Actualización de reclamos - Zona: $zona, Mes: $mes/$anio, Valor: $valor"
            );
        } catch (Exception $logError) {
            // Si falla el registro de actividad, solo lo registramos en el log
            // pero no afectamos la operación principal
            error_log("Error al registrar actividad: " . $logError->getMessage());
        }

        echo json_encode([
            'success' => true,
            'message' => 'Datos guardados correctamente',
            'data' => [
                'zona' => $zona,
                'mes' => $mes,
                'anio' => $anio,
                'valor' => $valor
            ]
        ]);
    }

} catch (Exception $e) {
    error_log('Error en guardar_valor.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Error al guardar: ' . $e->getMessage(),
        'debug_info' => [
            'post_data' => $_POST,
            'error_details' => $e->getMessage()
        ]
    ]);
}