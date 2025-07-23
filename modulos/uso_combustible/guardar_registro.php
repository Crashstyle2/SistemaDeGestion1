<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/UsoCombustible.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        $usoCombustible = new UsoCombustible($db);

        // Mapear campos del formulario a la BD
        $usoCombustible->fecha = $_POST['fecha'];
        $usoCombustible->nombre_conductor = $_POST['conductor'] ?? $_POST['nombreConductor'];
        $usoCombustible->chapa = $_POST['chapa'];
        $usoCombustible->numero_baucher = $_POST['numero_voucher'] ?? $_POST['numeroBaucher'];
        $usoCombustible->litros_cargados = $_POST['litros_cargados'] ?? $_POST['litrosCargados'];
        $usoCombustible->tipo_vehiculo = $_POST['tipo_vehiculo'] ?? $_POST['tipoVehiculo'];
        $usoCombustible->documento = $_POST['documento'];
        $usoCombustible->fecha_carga = $_POST['fecha_carga'];
        $usoCombustible->hora_carga = $_POST['hora_carga'];
        $usoCombustible->usuario_id = $_SESSION['user_id'];
        $usoCombustible->user_id = $_SESSION['user_id'];
        $usoCombustible->observaciones = $_POST['observaciones'] ?? '';
        $usoCombustible->tarjeta = $_POST['tarjeta'] ?? '';

        // Crear registro principal
        $registro_id = $usoCombustible->crear();

        if($registro_id) {
            // Guardar recorridos SIN kilómetros
            if(isset($_POST['origen']) && isset($_POST['destino'])) {
                $origenes = $_POST['origen'];
                $destinos = $_POST['destino'];
                
                for($i = 0; $i < count($origenes); $i++) {
                    if(!empty($origenes[$i]) && !empty($destinos[$i])) {
                        $usoCombustible->agregarRecorrido(
                            $registro_id,
                            $origenes[$i],
                            $destinos[$i]
                        );
                    }
                }
            }

            // Registrar actividad
            if(file_exists('../../config/ActivityLogger.php')) {
                require_once '../../config/ActivityLogger.php';
                ActivityLogger::logAccion(
                    $_SESSION['user_id'],
                    'uso_combustible',
                    'crear',
                    "Registro de combustible creado - Conductor: {$usoCombustible->nombre_conductor}, Chapa: {$usoCombustible->chapa}"
                );
            }

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'message' => 'Registro guardado correctamente']);
        } else {
            throw new Exception('Error al guardar el registro');
        }

    } catch(Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
}
?>