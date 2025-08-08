<?php
require_once 'ActivityLogger.php';

// Interceptar acciones POST, PUT, DELETE
if ($_SERVER['REQUEST_METHOD'] !== 'GET' && isset($_SESSION['user_id'])) {
    $currentPath = $_SERVER['REQUEST_URI'];
    
    // Detectar módulo y acción
    if (strpos($currentPath, 'ups') !== false) {
        if (isset($_POST['patrimonio']) || isset($_GET['patrimonio'])) {
            $patrimonio = $_POST['patrimonio'] ?? $_GET['patrimonio'];
            $accion = ($_SERVER['REQUEST_METHOD'] === 'POST') ? 'Crear/Actualizar' : 'Eliminar';
            ActivityLogger::logAccion(
                $_SESSION['user_id'],
                'UPS',
                $accion,
                "UPS Patrimonio: $patrimonio"
            );
        }
    }
    
    if (strpos($currentPath, 'usuarios/cambiar_password') !== false) {
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            'Usuarios',
            'Cambio contraseña',
            'Usuario modificó su contraseña'
        );
    }
}

// Registrar inicio/cierre de sesión
if (strpos($_SERVER['REQUEST_URI'], 'login.php') !== false) {
    if (isset($_SESSION['user_id'])) {
        ActivityLogger::logLogin($_SESSION['user_id'], 'Inicio');
    }
}

if (strpos($_SERVER['REQUEST_URI'], 'logout.php') !== false) {
    ActivityLogger::logLogin($_SESSION['user_id'], 'Cierre');
}