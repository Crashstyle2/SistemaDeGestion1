<?php
require_once 'config/session_config.php';
session_start();

// Verificar timeout de sesión
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header("Location: login.php?timeout=1");
    exit;
}
$_SESSION['last_activity'] = time();

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel Principal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Sistema de Gestión</a>
            
            <div class="mx-auto">
                <a class="navbar-brand">
                    <i class="fas fa-user mr-2"></i>
                    Bienvenido, <?php echo isset($_SESSION['nombre']) ? htmlspecialchars($_SESSION['nombre']) : 'Usuario'; ?>
                </a>
            </div>
            
            <div class="navbar-nav">
                <a href="mi_password.php" class="btn btn-warning">
                    <i class="fas fa-key"></i> Cambiar Contraseña
                </a>
                <a href="logout.php" class="btn btn-danger">
                    <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <h1 class="mb-4">Panel de Control</h1>
        <div class="row">
            <!-- Módulo UPS - Oculto para rol administrativo -->
            <?php if($_SESSION['user_rol'] !== 'administrativo'): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="fas fa-bolt fa-3x text-primary"></i>
                        </div>
                        <h5 class="card-title text-center">Mantenimiento de UPS</h5>
                        <p class="card-text text-center flex-grow-1">Gestión y seguimiento de mantenimientos de UPS</p>
                        <a href="modulos/ups/index.php" class="btn btn-primary mt-auto">
                            <i class="fas fa-folder-open mr-2"></i>Acceder
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Módulo Usuarios - Solo visible para administradores -->
            <?php if($_SESSION['user_rol'] === 'administrador'): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="fas fa-users fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title text-center">Gestión de Usuarios</h5>
                        <p class="card-text text-center flex-grow-1">Administración de usuarios del sistema</p>
                        <a href="modulos/usuarios/index.php" class="btn btn-success mt-auto">
                            <i class="fas fa-folder-open mr-2"></i>Acceder
                        </a>
                    </div>
                </div>
            </div>

            <!-- Nuevo Módulo Reporte de Cierres - Solo para administradores -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="fas fa-chart-line fa-3x text-warning"></i>
                        </div>
                        <h5 class="card-title text-center">Reporte de Cierres</h5>
                        <p class="card-text text-center flex-grow-1">Control y seguimiento de cierres por técnico</p>
                        <a href="modulos/reporte_cierres/index.php" class="btn btn-warning mt-auto">
                            <i class="fas fa-folder-open mr-2"></i>Acceder
                        </a>
                    </div>
                </div>
            </div>

            <!-- Nuevo Módulo Reclamos por Zonas -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="fas fa-map-marked-alt fa-3x text-danger"></i>
                        </div>
                        <h5 class="card-title text-center">Reclamos por Zonas</h5>
                        <p class="card-text text-center flex-grow-1">Control y seguimiento de reclamos por zona</p>
                        <a href="modulos/reclamos_zonas/index.php" class="btn btn-danger mt-auto">
                            <i class="fas fa-folder-open mr-2"></i>Acceder
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Módulos visibles para todos los usuarios -->
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="fas fa-clipboard-list fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title text-center">Informe Técnico</h5>
                        <p class="card-text text-center flex-grow-1">Gestión de informes técnicos y registro de trabajos realizados</p>
                        <a href="modulos/informe_tecnico/index.php" class="btn btn-info mt-auto">
                            <i class="fas fa-folder-open mr-2"></i>Acceder
                        </a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="fas fa-receipt fa-3x text-success"></i>
                        </div>
                        <h5 class="card-title text-center">Acuse de Recibo</h5>
                        <p class="card-text text-center flex-grow-1">Registro y control de documentos entregados</p>
                        <a href="modulos/acuse_recibo/index.php" class="btn btn-success mt-auto">
                            <i class="fas fa-folder-open mr-2"></i>Acceder
                        </a>
                    </div>
                </div>
            </div>

            <!-- Módulo Uso de Combustible - Visible para todos los roles autorizados -->
            <?php if(in_array($_SESSION['user_rol'], ['administrador', 'tecnico', 'supervisor', 'administrativo'])): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100 shadow-sm">
                    <div class="card-body d-flex flex-column">
                        <div class="text-center mb-3">
                            <i class="fas fa-gas-pump fa-3x text-info"></i>
                        </div>
                        <h5 class="card-title text-center">Uso de Combustible</h5>
                        <p class="card-text text-center flex-grow-1">
                            <?php if($_SESSION['user_rol'] === 'administrativo'): ?>
                                Consulta de registros de combustible (Solo lectura)
                            <?php else: ?>
                                Registro y control de uso de combustible
                            <?php endif; ?>
                        </p>
                        <a href="modulos/uso_combustible/index.php" class="btn btn-info mt-auto">
                            <i class="fas fa-folder-open mr-2"></i>Acceder
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if($_SESSION['user_rol'] === 'administrador'): ?>
            <div class="text-right mt-4 mb-3">
                <a href="modulos/registro_actividades/index.php" class="btn btn-secondary">
                    <i class="fas fa-history mr-2"></i>Registro de Actividades de Usuarios
                </a>
            </div>
        <?php endif; ?>
    </div>

    <footer class="footer">
        <div class="container">
            <p>Propiedad Intelectual del Ing. Juan Caceres © 2025</p>
        </div>
    </footer>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Advertencia 5 minutos antes de que expire la sesión
    setTimeout(function() {
        alert('Su sesión expirará en 5 minutos por inactividad. Por favor, guarde sus cambios.');
    }, 1500000); // 25 minutos (1500000 ms)

    // Redirigir cuando expire la sesión
    setTimeout(function() {
        window.location.href = 'logout.php';
    }, 1800000); // 30 minutos (1800000 ms)
    </script>
</body>
</html>