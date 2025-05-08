<?php
session_start();

// Verificar si el usuario está logueado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /MantenimientodeUPS/login.php');
    exit();
}

// Configuración de zona horaria
date_default_timezone_set('America/Asuncion');

// Tiempo de expiración de sesión (30 minutos)
if (isset($_SESSION['LAST_ACTIVITY']) && (time() - $_SESSION['LAST_ACTIVITY'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: /MantenimientodeUPS/login.php');
    exit();
}
$_SESSION['LAST_ACTIVITY'] = time();