<?php
session_start();

// Verificar si el usuario está autenticado y tiene permisos
if (!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    $_SESSION['error'] = "No tiene permisos para realizar esta acción";
    header("Location: ../../index.php");
    exit();
}

require_once '../../config/database.php';
require_once '../../models/Usuario.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);

    $accion = isset($_GET['accion']) ? $_GET['accion'] : '';
    $id = isset($_GET['id']) ? $_GET['id'] : null;

    if (empty($accion)) {
        throw new Exception("Acción no especificada");
    }

    // Debug de sesión
    error_log("Usuario actual: " . $_SESSION['user_id'] . " - Rol: " . $_SESSION['user_rol']);
    error_log("Procesando acción: " . $accion . " para ID: " . $id);
    
    $resultado = $usuario->procesarAccion($accion, $id);
    
    if ($resultado === false) {
        error_log("Error en el resultado de la acción");
        throw new Exception("Error al procesar la acción");
    }

    $_SESSION['mensaje'] = "Operación exitosa";
    header("Location: index.php");
    exit();

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    error_log("Error en procesar_usuario.php: " . $e->getMessage());
    header("Location: index.php");
    exit();
}
?>