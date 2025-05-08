<?php
if(!isset($_SESSION['user_id'])) {
    header("Location: /MantenimientodeUPS/login.php");
    exit;
}
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/MantenimientodeUPS/dashboard.php">
            <i class="fas fa-home"></i> Inicio
        </a>
        <div class="navbar-nav ml-auto">
            <div class="btn-group">
                <a href="/MantenimientodeUPS/cambiar_password.php" class="btn btn-outline-light mr-2">
                    <i class="fas fa-key"></i> Cambiar Contraseña
                </a>
                <a href="/MantenimientodeUPS/logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </div>
</nav>