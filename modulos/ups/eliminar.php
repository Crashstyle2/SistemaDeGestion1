<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

include_once '../../config/database.php';
include_once '../../models/MantenimientoUPS.php';

$database = new Database();
$db = $database->getConnection();
$mantenimiento = new MantenimientoUPS($db);

if(isset($_GET['id'])) {
    $mantenimiento->patrimonio = $_GET['id'];
    
    if($mantenimiento->eliminar()) {
        // Agregar registro de actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'ups',
            'eliminar',
            "UPS eliminado - Patrimonio: {$_GET['id']}"
        );

        header("Location: index.php");
        exit;
    } else {
        echo "Error al eliminar el registro.";
    }
} else {
    header("Location: index.php");
    exit;
}
?>