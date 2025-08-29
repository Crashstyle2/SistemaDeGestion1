<?php
// Función para mostrar rol amigable
function obtenerRolAmigable($rol) {
    switch($rol) {
        case 'administrador':
            return 'Administrador';
        case 'analista':
        return 'Analista';
        case 'tecnico':
            return 'Técnico';
        case 'supervisor':
            return 'Supervisor';
        default:
            return ucfirst($rol);
    }
}

// Función para mostrar mensaje de bienvenida completo
function mostrarBienvenida() {
    $nombre = isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario';
    $rol = obtenerRolAmigable($_SESSION['user_rol']);
    return "Bienvenido, {$nombre} - Rol: {$rol}";
}
?>