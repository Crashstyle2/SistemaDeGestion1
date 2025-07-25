<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Sesión expirada. Por favor, inicie sesión nuevamente.', 'error_type' => 'auth']);
    exit;
}

require_once '../../config/database.php';
require_once '../../models/UsoCombustible.php';

if($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Validar campos requeridos
        $camposRequeridos = ['fecha', 'conductor', 'chapa', 'numero_voucher', 'litros_cargados', 'tipo_vehiculo', 'documento', 'fecha_carga', 'hora_carga'];
        $camposFaltantes = [];
        
        foreach($camposRequeridos as $campo) {
            if(empty($_POST[$campo])) {
                $camposFaltantes[] = $campo;
            }
        }
        
        if(!empty($camposFaltantes)) {
            throw new Exception('Campos requeridos faltantes: ' . implode(', ', $camposFaltantes), 400);
        }
        
        // Validar formato de datos
        if(!is_numeric($_POST['litros_cargados']) || $_POST['litros_cargados'] <= 0) {
            throw new Exception('Los litros cargados deben ser un número mayor a 0', 400);
        }
        
        if(!in_array($_POST['tipo_vehiculo'], ['particular', 'movil_retail'])) {
            throw new Exception('Tipo de vehículo no válido', 400);
        }
        
        // Validar recorridos
        if(!isset($_POST['origen']) || !isset($_POST['destino']) || !isset($_POST['km_sucursales']) ||
           !is_array($_POST['origen']) || !is_array($_POST['destino']) || !is_array($_POST['km_sucursales'])) {
            throw new Exception('Debe agregar al menos un recorrido válido con sus kilómetros', 400);
        }
        
        if(count($_POST['origen']) !== count($_POST['destino']) || count($_POST['origen']) !== count($_POST['km_sucursales'])) {
            throw new Exception('Error en los datos de recorridos. Verifique que cada origen tenga su destino y kilómetros correspondientes', 400);
        }
        
        // Validar kilómetros
        foreach($_POST['km_sucursales'] as $km) {
            if(!is_numeric($km) || $km < 0) {
                throw new Exception('Los kilómetros deben ser números válidos mayores o iguales a 0', 400);
            }
        }
        
        $database = new Database();
        $db = $database->getConnection();
        
        if(!$db) {
            throw new Exception('Error de conexión a la base de datos. Contacte al administrador del sistema', 500);
        }
        
        $usoCombustible = new UsoCombustible($db);

        // Mapear campos del formulario a la BD
        $usoCombustible->fecha = $_POST['fecha'];
        $usoCombustible->conductor = $_POST['conductor'];
        $usoCombustible->chapa = $_POST['chapa'];
        $usoCombustible->numero_voucher = $_POST['numero_voucher'];
        $usoCombustible->tarjeta = $_POST['tarjeta'] ?? '0000';
        $usoCombustible->litros_cargados = $_POST['litros_cargados'];
        $usoCombustible->tipo_vehiculo = $_POST['tipo_vehiculo'];
        $usoCombustible->documento = $_POST['documento'];
        $usoCombustible->fecha_carga = $_POST['fecha_carga'];
        $usoCombustible->hora_carga = $_POST['hora_carga'];
        $usoCombustible->usuario_id = $_SESSION['user_id'];
        $usoCombustible->user_id = $_SESSION['user_id'];

        // Crear registro principal
        $registro_id = $usoCombustible->crear();

        if(!$registro_id) {
            throw new Exception('Error al guardar el registro principal en la base de datos. Verifique los datos e intente nuevamente', 500);
        }

        // Guardar recorridos con kilómetros y comentarios
        $recorridosGuardados = 0;
        $origenes = $_POST['origen'];
        $destinos = $_POST['destino'];
        $kilometros = $_POST['km_sucursales'];
        $comentarios = $_POST['comentarios_sector'] ?? [];
        
        for($i = 0; $i < count($origenes); $i++) {
            if(!empty(trim($origenes[$i])) && !empty(trim($destinos[$i])) && is_numeric($kilometros[$i])) {
                $comentario = isset($comentarios[$i]) ? trim($comentarios[$i]) : null;
                $comentario = empty($comentario) ? null : $comentario;
                
                $resultado = $usoCombustible->agregarRecorrido(
                    $registro_id,
                    trim($origenes[$i]),
                    trim($destinos[$i]),
                    floatval($kilometros[$i]),
                    $comentario
                );
                
                if($resultado) {
                    $recorridosGuardados++;
                } else {
                    throw new Exception('Error al guardar el recorrido ' . ($i + 1) . ': ' . trim($origenes[$i]) . ' → ' . trim($destinos[$i]) . ' (' . $kilometros[$i] . ' km)', 500);
                }
            }
        }
        
        if($recorridosGuardados === 0) {
            throw new Exception('No se pudo guardar ningún recorrido. Verifique que los campos de origen, destino y kilómetros no estén vacíos', 400);
        }

        // Registrar actividad
        if(file_exists('../../config/ActivityLogger.php')) {
            require_once '../../config/ActivityLogger.php';
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'uso_combustible',
                'crear',
                "Registro de combustible creado - Conductor: {$usoCombustible->conductor}, Chapa: {$usoCombustible->chapa}, Recorridos: {$recorridosGuardados}"
            );
        }

        header('Content-Type: application/json');
        echo json_encode([
            'success' => true, 
            'message' => 'Registro guardado correctamente',
            'data' => [
                'registro_id' => $registro_id,
                'recorridos_guardados' => $recorridosGuardados
            ]
        ]);

    } catch(Exception $e) {
        $errorCode = $e->getCode() ?: 500;
        $errorType = $errorCode >= 400 && $errorCode < 500 ? 'validation' : 'server';
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => $e->getMessage(),
            'error_type' => $errorType,
            'error_code' => $errorCode
        ]);
    }
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false, 
        'message' => 'Método de solicitud no permitido. Use POST.',
        'error_type' => 'method'
    ]);
}
?>