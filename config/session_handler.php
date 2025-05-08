<?php
session_start();

// Tiempo máximo de inactividad (5 minutos)
define('INACTIVE_TIME', 300);

// Verificar si existe última actividad
if (isset($_SESSION['last_activity'])) {
    $inactive_time = time() - $_SESSION['last_activity'];
    
    // Si el tiempo de inactividad supera el límite
    if ($inactive_time >= INACTIVE_TIME) {
        // Registrar el cierre de sesión por inactividad
        if (isset($_SESSION['user_id'])) {
            require_once __DIR__ . '/database.php';
            require_once __DIR__ . '/../models/RegistroActividad.php';
            
            $database = new Database();
            $db = $database->getConnection();
            $registro = new RegistroActividad($db);
            
            $registro->registrar(
                $_SESSION['user_id'],
                'Cierre de sesión',
                'Cierre automático por inactividad',
                'Sistema'
            );
        }
        
        // Destruir la sesión
        session_unset();
        session_destroy();
        
        // Redireccionar al login
        header("Location: /MantenimientodeUPS/login.php");
        exit;
    }
}

// Actualizar el tiempo de última actividad
$_SESSION['last_activity'] = time();