<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if(!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

require_once '../../config/database.php';
require_once '../../models/RegistroActividad.php';

// Update session last activity
$_SESSION['last_activity'] = time();

$database = new Database();
$db = $database->getConnection();

function verificarPermiso($usuario, $permiso_requerido) {
    if($_SESSION['user_rol'] === 'administrador') {
        return true;
    }
    return $usuario->tienePermiso($permiso_requerido);
}