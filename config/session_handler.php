<?php
session_start();

// Tiempo máximo de inactividad (5 minutos)
define('INACTIVE_TIME', 300);

// Registrar la actividad actual
if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/ActivityLogger.php';
    
    // Registrar todas las acciones POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $currentPath = $_SERVER['REQUEST_URI'];
        $currentModule = basename(dirname($currentPath));
        
        // Registrar la actividad
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            $currentModule,
            'POST',
            'Datos modificados en: ' . basename($currentPath)
        );
    }
    
    // Registrar eliminaciones
    if (isset($_GET['delete']) || isset($_GET['eliminar'])) {
        $currentPath = $_SERVER['REQUEST_URI'];
        $currentModule = basename(dirname($currentPath));
        
        ActivityLogger::logAccion(
            $_SESSION['user_id'],
            $currentModule,
            'Eliminación',
            'Eliminación de registro en: ' . basename($currentPath)
        );
    }
}

// Verificar si existe última actividad
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    
    if ($inactive_time >= INACTIVE_TIME) {
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/ActivityLogger.php';
            ActivityLogger::logLogin($_SESSION['user_id'], 'Cierre');
        }
        session_unset();
        session_destroy();
        header("Location: /MantenimientodeUPS/login.php");
        exit;
    }
}

$_SESSION['last_activity'] = time();