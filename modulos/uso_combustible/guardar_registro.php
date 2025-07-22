<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

include_once '../../config/database.php';
include_once '../../models/UsoCombustible.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Validar campos requeridos
    $required_fields = ['tipoVehiculo', 'chapa', 'nombreConductor', 'documento', 'fecha', 'numeroBaucher', 'litrosCargados', 'origen', 'destino'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            echo json_encode(['success' => false, 'message' => 'El campo ' . $field . ' es requerido']);
            exit;
        }
    }

    // Validar que los arrays tengan la misma longitud
    if (count($_POST['origen']) !== count($_POST['destino'])) {
        echo json_encode(['success' => false, 'message' => 'Los datos de recorrido están incompletos o son inválidos']);
        exit;
    }

    try {
        $database = new Database();
        $db = $database->getConnection();
        $combustible = new UsoCombustible($db);

        // Sanitizar y asignar datos principales
        $combustible->tipo_vehiculo = htmlspecialchars(strip_tags($_POST['tipoVehiculo']));
        $combustible->chapa = htmlspecialchars(strip_tags($_POST['chapa']));
        $combustible->nombre_conductor = htmlspecialchars(strip_tags($_POST['nombreConductor']));
        $combustible->documento = htmlspecialchars(strip_tags($_POST['documento']));
        $combustible->fecha = htmlspecialchars(strip_tags($_POST['fecha']));
        $combustible->numero_baucher = htmlspecialchars(strip_tags($_POST['numeroBaucher']));
        $combustible->litros_cargados = floatval($_POST['litrosCargados']);
        $combustible->usuario_id = $_SESSION['user_id'];

        // Validar formato de fecha
        if (!strtotime($combustible->fecha)) {
            throw new Exception('Formato de fecha inválido');
        }

        // Validar litros cargados
        if ($combustible->litros_cargados <= 0) {
            throw new Exception('La cantidad de litros debe ser mayor a 0');
        }

        // Iniciar transacción
        $db->beginTransaction();

        // Guardar registro principal
        $registro_id = $combustible->crear();

        if ($registro_id) {
            // Procesar recorridos
            $origenes = array_map('strip_tags', $_POST['origen']);
            $destinos = array_map('strip_tags', $_POST['destino']);
            $kms_iniciales = isset($_POST['kmInicial']) ? array_map('floatval', $_POST['kmInicial']) : array_fill(0, count($origenes), null);
            $kms_finales = isset($_POST['kmFinal']) ? array_map('floatval', $_POST['kmFinal']) : array_fill(0, count($origenes), null);

            for ($i = 0; $i < count($origenes); $i++) {
                // Validar que el km final sea mayor al inicial solo si ambos están presentes
                if (isset($kms_iniciales[$i]) && isset($kms_finales[$i]) && $kms_finales[$i] <= $kms_iniciales[$i]) {
                    throw new Exception('El kilómetro final debe ser mayor al inicial en el recorrido ' . ($i + 1));
                }

                $recorrido = [
                    'uso_combustible_id' => $registro_id,
                    'origen' => $origenes[$i],
                    'destino' => $destinos[$i],
                    'km_inicial' => $kms_iniciales[$i],
                    'km_final' => $kms_finales[$i]
                ];

                if (!$combustible->guardarRecorrido($recorrido)) {
                    throw new Exception('Error al guardar el recorrido ' . ($i + 1));
                }
            }

            // Registrar la actividad
            require_once '../../config/ActivityLogger.php';
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'uso_combustible',
                'crear',
                "Registro de combustible creado - Chapa: {$combustible->chapa}"
            );

            // Confirmar transacción
            $db->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Registro guardado exitosamente',
                'registro_id' => $registro_id
            ]);
        } else {
            throw new Exception('Error al guardar el registro principal');
        }
    } catch (Exception $e) {
        // Revertir transacción en caso de error
        if (isset($db) && $db->inTransaction()) {
            $db->rollBack();
        }
        
        error_log('Error en guardar_registro.php: ' . $e->getMessage());
        
        echo json_encode([
            'success' => false,
            'message' => 'Error al procesar el registro: ' . $e->getMessage()
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);
}