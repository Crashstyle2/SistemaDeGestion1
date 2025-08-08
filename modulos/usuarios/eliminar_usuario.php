<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header("Location: ../../dashboard.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/Usuario.php';

if(isset($_GET['id'])) {
    $database = new Database();
    $db = $database->getConnection();
    $usuario = new Usuario($db);
    
    if($usuario->eliminar($_GET['id'])) {
        // Agregar registro de actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'usuarios',
            'eliminar',
            "Usuario eliminado - ID: {$_GET['id']}"
        );

        header("Location: index.php?mensaje=Usuario eliminado correctamente");
    } else {
        header("Location: index.php?error=Error al eliminar el usuario");
    }
} else {
    header("Location: index.php");
}
exit;