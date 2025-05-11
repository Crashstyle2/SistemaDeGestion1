<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_rol'] !== 'administrador') {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/AcuseRecibo.php';

$database = new Database();
$db = $database->getConnection();
$acuse = new AcuseRecibo($db);

if(isset($_GET['id'])) {
    if($acuse->eliminar($_GET['id'])) {
        // Registrar actividad
        require_once '../../config/ActivityLogger.php';
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'acuse_recibo',
            'eliminar',
            "Acuse eliminado - ID: {$_GET['id']}"
        );
        
        header("Location: index.php?mensaje=Acuse de recibo eliminado exitosamente");
    } else {
        header("Location: index.php?error=Error al eliminar el acuse de recibo");
    }
} else {
    header("Location: index.php");
}
exit;